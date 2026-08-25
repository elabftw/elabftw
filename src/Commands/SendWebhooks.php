<?php

/**
 * @author Moritz IHLER
 * @copyright 2026 Moritz IHLER
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Env;
use Elabftw\Models\Config;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\WebhookDispatcher;
use Elabftw\Services\WebhookUrlValidator;
use GuzzleHttp\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

use function sprintf;

/**
 * Deliver the queued webhook events
 */
#[AsCommand(name: 'webhooks:send')]
final class SendWebhooks extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Send the queued webhook events')
            ->setHelp('Look for all webhook deliveries that are due and POST them to their target. Run every minute by chronos.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // a development instance necessarily talks to a receiver on a private address,
        // so the address checks are relaxed there, exactly like tls verification is
        $strict = !Env::asBool('DEV_MODE');
        $Config = Config::getConfig();
        $httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $strict);
        $Dispatcher = new WebhookDispatcher($httpGetter, new WebhookUrlValidator($strict));
        $count = $Dispatcher->send($output);
        if ($output->isVerbose()) {
            $output->writeln(sprintf('Delivered %d webhook events', $count));
        }

        return Command::SUCCESS;
    }
}
