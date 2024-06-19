<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Interfaces\StorageInterface;
use Elabftw\Make\Exports;
use Elabftw\Models\UltraAdmin;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trigger the process of export requests
 */
#[AsCommand(name: 'export:process')]
class ExportCommand extends Command
{
    public function __construct(private StorageInterface $fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Process export requests')
            ->setHelp('Without an ID as argument, this command will process all pending export requests.')
            ->addArgument('id', InputArgument::OPTIONAL, 'The export id to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $Exports = new Exports(new UltraAdmin(), $this->fs);
        if ($input->getArgument('id')) {
            $Exports->setId((int) $input->getArgument('id'));
            return $Exports->process();
        }

        return $Exports->processPending();
    }
}
