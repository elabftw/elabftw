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
use Elabftw\Make\MakeTeamEln;
use Elabftw\Models\Users;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipStream\ZipStream;

/**
 * Export a full team
 */
#[AsCommand(name: 'teams:export')]
class ExportTeams extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Export all experiments and resources from a team')
            ->setHelp('This command will generate a ELN archive with all the experiments and resources bound to a particular team.')
            ->addArgument('teamid', InputArgument::REQUIRED, 'Target team ID')
            ->addArgument('userid', InputArgument::REQUIRED, 'User executing the request');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $teamid = (int) $input->getArgument('teamid');
        $userid = (int) $input->getArgument('userid');
        $absolutePath = $this->Fs->getPath(sprintf(
            'export-%s-teamid-%d.eln',
            date('Y-m-d_H-i-s'),
            $teamid,
        ));
        $fileStream = fopen($absolutePath, 'wb');
        if ($fileStream === false) {
            throw new RuntimeException('Could not open output stream!');
        }

        $ZipStream = new ZipStream(sendHttpHeaders:false, outputStream: $fileStream);
        $user = new Users($userid, $teamid);
        $Maker = new MakeTeamEln($ZipStream, $user, $teamid);
        $Maker->getStreamZip();

        fclose($fileStream);

        $output->writeln(sprintf('Team with id %d successfully exported as ELN archive.', $teamid));
        $output->writeln('Copy the generated archive from the container to the current directory with:');
        $output->writeln(sprintf('docker cp elabftw:%s .', $absolutePath));

        return Command::SUCCESS;
    }
}
