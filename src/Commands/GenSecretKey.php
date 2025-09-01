<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Defuse\Crypto\Key;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Override;

/**
 * Generate secret key locally
 */
#[AsCommand(name: 'tools:genkey')]
final class GenSecretKey extends Command
{
    #[Override]
    protected function configure(): void
    {
        $this->setDescription('Generate the secret key for the application')
            ->setHelp('The secret key is used to encrypt smtp password among other things. It needs to be in a particular format.');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(Key::createNewRandomKey()->saveToAsciiSafeString());
        return Command::SUCCESS;
    }
}
