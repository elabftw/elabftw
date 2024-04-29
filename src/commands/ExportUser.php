<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Enums\EntityType;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Make\MakeEln;
use Elabftw\Models\Users;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipStream\ZipStream;

/**
 * Export experiments from user
 */
#[AsCommand(name: 'users:export')]
class ExportUser extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Export all experiments from user')
            ->setHelp('This command will generate a ELN archive with all the experiments of a particular user. It is more reliable than using the web interface as it will not suffer from timeouts.')
            ->addArgument('userid', InputArgument::REQUIRED, 'Target user ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userid = (int) $input->getArgument('userid');
        $absolutePath = $this->Fs->getPath(sprintf(
            'export-%s-userid-%d.eln',
            date('Y-m-d_H-i-s'),
            $userid,
        ));
        $fileStream = fopen($absolutePath, 'wb');
        if ($fileStream === false) {
            throw new RuntimeException('Could not open output stream!');
        }

        $ZipStream = new ZipStream(sendHttpHeaders:false, outputStream: $fileStream);
        $Entity = EntityType::Experiments->toInstance(new Users($userid));
        $Maker = new MakeEln($ZipStream, $Entity, $Entity->getIdFromUser($userid));
        $Maker->getStreamZip();

        fclose($fileStream);

        $output->writeln(sprintf('Experiments of user with ID %d successfully exported as ELN archive.', $userid));
        $output->writeln('Copy the generated archive from the container to the current directory with:');
        $output->writeln(sprintf('docker cp elabftw:%s .', $absolutePath));

        return Command::SUCCESS;
    }
}
