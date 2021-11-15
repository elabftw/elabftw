<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Defuse\Crypto\Key;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate secret key locally
 */
class GenSecretKey extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'genkey';

    /**
     * Set the help messages
     */
    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Generate the secret key for the application')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('The secret key is used to encrypt smtp password among other things. It needs to be in a particular format.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(Key::createNewRandomKey()->saveToAsciiSafeString());
        return 0;
    }
}
