<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Enums\EntityType;
use Elabftw\Make\MakeEln;
use Elabftw\Models\Users;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipStream\ZipStream;

/**
 * Export experiments from user
 */
class ExportUser extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'users:export';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Export all experiments from user')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command will generate a ELN archive with all the experiments of a particular user. It is more reliable than using the web interface as it will not suffer from timeouts.')
            ->addArgument('userid', InputArgument::REQUIRED, 'Target user ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userid = (int) $input->getArgument('userid');
        $outputFilename = sprintf('export-%s-userid-%d.eln', date('Y-m-d_H-i-s'), $userid);
        $fileStream = fopen('/elabftw/cache/elab/' . $outputFilename, 'wb');
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
        $output->writeln(sprintf('docker cp elabftw:cache/elab/%s .', $outputFilename));

        return Command::SUCCESS;
    }
}
