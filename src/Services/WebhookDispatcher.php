<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Models\Webhooks;
use Elabftw\Models\WebhooksQueue;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function array_slice;
use function hash_hmac;
use function max;
use function min;
use function parse_url;
use function sprintf;
use function strtolower;
use function time;

use const CURLOPT_RESOLVE;

/**
 * Drains the webhook queue: this is the only place that talks to the outside world.
 *
 * Run every minute by chronos. Two things follow from that: it must not run longer than a
 * minute (or the next tick starts immediately after this one ends), and it must never retry
 * inline, because a target that hangs would otherwise hold up every other target behind it.
 */
final class WebhookDispatcher
{
    public const string SIGNATURE_HEADER = 'X-Elabftw-Signature';

    public const string EVENT_HEADER = 'X-Elabftw-Event';

    public const string DELIVERY_HEADER = 'X-Elabftw-Delivery';

    /** give up on a delivery after this many attempts */
    private const int MAX_ATTEMPTS = 5;

    /** rows taken per round */
    private const int BATCH_SIZE = 20;

    /**
     * Stop taking on work after this many seconds. chronos ticks every 60, and a delivery
     * already under way may still add up to REQUEST_TIMEOUT on top.
     */
    private const int TIME_BUDGET = 45;

    private const int CONNECT_TIMEOUT = 5;

    private const int REQUEST_TIMEOUT = 10;

    private const int FIRST_RETRY_DELAY = 60;

    private const int MAX_RETRY_DELAY = 3600;

    private WebhooksQueue $Queue;

    private Webhooks $Webhooks;

    public function __construct(
        private readonly HttpGetter $httpGetter,
        private readonly WebhookUrlValidator $validator,
    ) {
        $this->Queue = new WebhooksQueue();
        $this->Webhooks = new Webhooks();
    }

    /**
     * @return int number of deliveries that succeeded
     */
    public function send(OutputInterface $output): int
    {
        $deadline = time() + self::TIME_BUDGET;
        $this->Queue->releaseStaleClaims();

        $delivered = 0;
        while (time() < $deadline) {
            $batch = $this->Queue->claim(self::BATCH_SIZE);
            if (empty($batch)) {
                break;
            }
            foreach ($batch as $index => $row) {
                // the deadline has to be checked per row, not per batch: a batch of slow
                // targets would otherwise run for batch size times the request timeout, and
                // the next chronos tick would start on top of this one
                if (time() >= $deadline) {
                    $this->Queue->release(array_slice($batch, $index));
                    break 2;
                }
                if ($this->deliver($row, $output)) {
                    $delivered++;
                }
            }
        }
        $this->Queue->pruneDelivered();

        return $delivered;
    }

    private function deliver(array $row, OutputInterface $output): bool
    {
        $id = (int) $row['id'];
        $token = (string) $row['claim_token'];
        $webhookId = (int) $row['webhook_id'];
        $body = (string) $row['body'];
        try {
            $url = (string) $row['url'];
            // the address checks are redone here, not just when the webhook was configured:
            // the row may have been queued minutes ago, and dns for a host that was public
            // then can answer with an internal address now
            $resolved = $this->getResolveList($url);
            $response = $this->httpGetter->post($url, $this->getOptions($row, $body, $resolved));
            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                if (!$this->Queue->markDelivered($id, $token)) {
                    // another drain owns this row now, its result is the one that counts
                    return false;
                }
                $this->Webhooks->recordSuccess($webhookId);
                return true;
            }
            $this->handleFailure($id, $token, $webhookId, (int) $row['attempts'], sprintf('target answered %d', $status), $output);
        } catch (Throwable $e) {
            $this->handleFailure($id, $token, $webhookId, (int) $row['attempts'], $e->getMessage(), $output);
        }
        return false;
    }

    private function handleFailure(int $id, string $token, int $webhookId, int $attempts, string $error, OutputInterface $output): void
    {
        if ($attempts < self::MAX_ATTEMPTS) {
            $this->Queue->markRetry($id, $token, $error, $this->getRetryDelay($attempts));
            return;
        }
        if (!$this->Queue->markFailed($id, $token, $error)) {
            return;
        }
        $disabled = $this->Webhooks->recordFailure($webhookId, $error);
        if ($output->isVerbose()) {
            $output->writeln(sprintf(
                'webhook %d: delivery %d gave up after %d attempts (%s)%s',
                $webhookId,
                $id,
                $attempts,
                $error,
                $disabled ? ' - webhook disabled' : '',
            ));
        }
    }

    // exponential, so a target that is down for an hour is not hammered every minute
    private function getRetryDelay(int $attempts): int
    {
        // shift rather than ** so the result stays an int, both analysers insist on that
        $steps = min(max($attempts - 1, 0), 16);
        return min(self::FIRST_RETRY_DELAY << $steps, self::MAX_RETRY_DELAY);
    }

    /**
     * @param array<int, string> $resolved
     */
    private function getOptions(array $row, string $body, array $resolved): array
    {
        return array(
            'body' => $body,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'eLabFTW-Webhook',
                // signature is over the raw body, so a receiver can verify without reparsing
                self::SIGNATURE_HEADER => 'sha256=' . hash_hmac('sha256', $body, (string) $row['secret']),
                self::EVENT_HEADER => (string) $row['event'],
                self::DELIVERY_HEADER => (string) $row['event_id'],
            ),
            // a redirect would take us to a host that never passed the address checks
            'allow_redirects' => false,
            'connect_timeout' => self::CONNECT_TIMEOUT,
            'timeout' => self::REQUEST_TIMEOUT,
            // a non 2xx answer is a failed delivery, not an exception to unwind
            'http_errors' => false,
            // pin the connection to the addresses that were just checked, otherwise a second
            // dns answer could send the request somewhere else entirely
            'curl' => array(CURLOPT_RESOLVE => $resolved),
        );
    }

    /**
     * Resolve the target and check every address it answers with, returning curl's
     * host:port:address form so the connection can be pinned to what was checked.
     *
     * @return array<int, string>
     */
    private function getResolveList(string $url): array
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return array();
        }
        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? (strtolower($parts['scheme'] ?? 'https') === 'http' ? 80 : 443);
        $list = array();
        foreach ($this->validator->resolve($host) as $address) {
            $list[] = sprintf('%s:%d:%s', $host, $port, $address);
        }
        return $list;
    }
}
