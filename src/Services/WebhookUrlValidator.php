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

use Elabftw\Exceptions\ImproperActionException;

use function array_unique;
use function array_values;
use function dns_get_record;
use function filter_var;
use function gethostbynamel;
use function inet_pton;
use function intdiv;
use function is_array;
use function ord;
use function parse_url;
use function sprintf;
use function str_contains;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

use const DNS_A;
use const DNS_AAAA;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const PHP_URL_FRAGMENT;

/**
 * A webhook target is a URL supplied by a user and fetched by the server, which is the exact
 * shape of an SSRF. This class is the guard: it rejects anything that is not a public https
 * endpoint, and it resolves the host so the caller can pin the connection to the addresses
 * that were actually checked (otherwise DNS could answer differently the second time).
 *
 * In DEV_MODE the address checks are relaxed, because a development instance talks to a
 * receiver on a private address by definition.
 */
final class WebhookUrlValidator
{
    public const int MAX_URL_LENGTH = 512;

    /** @var array<int, array{0: string, 1: int}> network/prefix pairs that must never be reached */
    private const array BLOCKED_V4 = array(
        array('0.0.0.0', 8),
        array('10.0.0.0', 8),
        array('100.64.0.0', 10),
        array('127.0.0.0', 8),
        array('169.254.0.0', 16),
        array('172.16.0.0', 12),
        array('192.0.0.0', 24),
        array('192.0.2.0', 24),
        array('192.88.99.0', 24),
        array('192.168.0.0', 16),
        array('198.18.0.0', 15),
        array('198.51.100.0', 24),
        array('203.0.113.0', 24),
        array('224.0.0.0', 4),
        array('240.0.0.0', 4),
    );

    /** @var array<int, array{0: string, 1: int}> */
    private const array BLOCKED_V6 = array(
        array('::', 128),
        array('::1', 128),
        array('64:ff9b::', 96),
        array('100::', 64),
        array('2001::', 32),
        array('2001:db8::', 32),
        array('fc00::', 7),
        array('fe80::', 10),
        array('ff00::', 8),
    );

    public function __construct(private readonly bool $strict = true) {}

    /**
     * Check a URL before it is stored. Throws if it can never be a legitimate target.
     * DNS is resolved here too, so an obviously internal name is refused at configuration
     * time rather than silently failing later.
     */
    public function validate(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > self::MAX_URL_LENGTH) {
            throw new ImproperActionException(sprintf('Webhook URL must be between 1 and %d characters.', self::MAX_URL_LENGTH));
        }
        $parts = parse_url($url);
        if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
            throw new ImproperActionException('Could not parse webhook URL.');
        }
        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'https' && !($scheme === 'http' && !$this->strict)) {
            throw new ImproperActionException('Webhook URL must use https.');
        }
        // credentials in the URL are a way to smuggle a different host past a naive parser
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new ImproperActionException('Webhook URL must not contain credentials.');
        }
        if (parse_url($url, PHP_URL_FRAGMENT) !== null) {
            throw new ImproperActionException('Webhook URL must not contain a fragment.');
        }
        // resolving here means an internal name is refused at configuration time rather than
        // silently failing later. Skipped in dev, where the target is private by definition
        // and dns may not even be reachable
        if ($this->strict) {
            $this->resolve($parts['host']);
        }
        return $url;
    }

    /**
     * Resolve a host to the addresses that passed the checks.
     * The caller pins the connection to these, so what was checked is what gets connected to.
     *
     * @return array<int, string>
     */
    public function resolve(string $host): array
    {
        $host = strtolower(trim($host, '[]'));
        // an address literal needs no resolution, but still needs checking
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->guardAddress($host);
            return array($host);
        }
        $addresses = $this->lookup($host);
        if (empty($addresses)) {
            if (!$this->strict) {
                // nothing to pin, let the http client resolve it itself
                return array();
            }
            throw new ImproperActionException(sprintf('Could not resolve webhook host: %s', $host));
        }
        foreach ($addresses as $address) {
            $this->guardAddress($address);
        }
        return $addresses;
    }

    /**
     * @return array<int, string>
     */
    private function lookup(string $host): array
    {
        $addresses = array();
        // the @ is deliberate: a failing lookup is reported as "could not resolve", not as a warning
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                $address = $record['ip'] ?? $record['ipv6'] ?? null;
                if ($address !== null) {
                    $addresses[] = (string) $address;
                }
            }
        }
        // dns_get_record is not always available inside containers, fall back to the resolver
        if (empty($addresses)) {
            $fallback = @gethostbynamel($host);
            if (is_array($fallback)) {
                $addresses = $fallback;
            }
        }
        return array_values(array_unique($addresses));
    }

    private function guardAddress(string $address): void
    {
        if ($this->strict === false) {
            return;
        }
        // ipv4 written as ipv6, unwrap it so the v4 rules apply
        if (str_contains($address, '.') && str_contains($address, ':')) {
            $address = substr($address, (int) strrpos($address, ':') + 1);
        }
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $this->guardAgainst($address, self::BLOCKED_V4);
            return;
        }
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $this->guardAgainst($address, self::BLOCKED_V6);
            return;
        }
        throw new ImproperActionException(sprintf('Webhook host resolves to an invalid address: %s', $address));
    }

    /**
     * @param array<int, array{0: string, 1: int}> $blocked
     */
    private function guardAgainst(string $address, array $blocked): void
    {
        foreach ($blocked as [$network, $prefix]) {
            if ($this->isInNetwork($address, $network, $prefix)) {
                throw new ImproperActionException(sprintf(
                    'Webhook URL points to a reserved address (%s). Only public addresses are allowed.',
                    $address,
                ));
            }
        }
    }

    private function isInNetwork(string $address, string $network, int $prefix): bool
    {
        $addressBin = inet_pton($address);
        $networkBin = inet_pton($network);
        if ($addressBin === false || $networkBin === false || strlen($addressBin) !== strlen($networkBin)) {
            return false;
        }
        $fullBytes = intdiv($prefix, 8);
        if ($fullBytes > 0 && substr($addressBin, 0, $fullBytes) !== substr($networkBin, 0, $fullBytes)) {
            return false;
        }
        $remainingBits = $prefix % 8;
        if ($remainingBits === 0) {
            return true;
        }
        $mask = 0xff << (8 - $remainingBits) & 0xff;
        return (ord($addressBin[$fullBytes]) & $mask) === (ord($networkBin[$fullBytes]) & $mask);
    }
}
