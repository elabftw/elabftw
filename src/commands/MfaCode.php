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
use InvalidArgumentException;
use function is_array;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command line tool to emulate a 2FA phone app. It returns a 2FA code calculated from the provided secret.
 */
class MfaCode extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:2fa';

    /**
     * Set the help messages
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Get a 2FA code')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to get a 2FA code if you provide a secret token.')

            // The secret token input
            ->addArgument('secret', InputArgument::REQUIRED, 'Please provide the 2FA secret.');
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // remove spaces from input so we don't have to do it manually
        $secret = str_replace(' ', '', $input->getArgument('secret'));

        if (is_array($secret)) {
            throw new InvalidArgumentException();
        }
        $MfaHelper = new MfaHelper(0, $secret);
        $code = $MfaHelper->getCode();

        $output->writeln(array(
            'Secret: ' . (string) $secret,
            '2FA code: ' . $code,
        ));

        return 0;
    }
}
