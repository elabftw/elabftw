<?php

/**
 * @author Marcel Bolten <marcel.bolten@msl.ubc.ca>
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Services\MfaHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Command line tool to emulate a 2FA phone app. It returns a 2FA code calculated from the provided secret.
 */
#[AsCommand(name: 'dev:2fa')]
final class MfaCode extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Get a 2FA code')
            ->setHelp('This command allows you to get a 2FA code if you provide a secret token.')
            ->addArgument('secret', InputArgument::REQUIRED, 'The 2FA secret provided as text.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // remove spaces from input so we don't have to do it manually
        $secret = str_replace(' ', '', (string) $input->getArgument('secret'));
        $MfaHelper = new MfaHelper(0, $secret);

        $output->writeln(sprintf('2FA code: %s', $MfaHelper->getCode()));
        return Command::SUCCESS;
    }
}
