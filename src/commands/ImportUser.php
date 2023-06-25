<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Storage;
use Elabftw\Import\Eln;
use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import experiments from a .eln
 */
class ImportUser extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'users:import';

    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Import experiments from an ELN archive')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command will import experiments from a provided ELN archive. It is more reliable than using the web interface as it will not suffer from timeouts.')
            ->addArgument('userid', InputArgument::REQUIRED, 'User id')
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the file to import present in cache/elab folder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userid = (int) $input->getArgument('userid');
        $filePath = sprintf('/elabftw/cache/elab/%s', $input->getArgument('file'));
        $uploadedFile = new UploadedFile($filePath, 'input.eln', null, null, true);
        $teamid = (int) (new UsersHelper($userid))->getTeamsFromUserid()[0]['id'];
        $Eln = new Eln(new Users($userid, $teamid), sprintf('experiments:%d', $userid), BasePermissions::User->toJson(), BasePermissions::User->toJson(), $uploadedFile, Storage::CACHE->getStorage()->getFs());
        $Eln->import();

        $output->writeln(sprintf('Experiments successfully imported for user with ID %d.', $userid));

        return Command::SUCCESS;
    }
}
