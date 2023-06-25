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
    protected static $defaultName = 'tools:genkey';

    protected function configure(): void
    {
        $this->setDescription('Generate the secret key for the application')
            ->setHelp('The secret key is used to encrypt smtp password among other things. It needs to be in a particular format.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(Key::createNewRandomKey()->saveToAsciiSafeString());
        return Command::SUCCESS;
    }
}
