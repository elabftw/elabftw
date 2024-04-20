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

use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Storage;
use Elabftw\Import\Eln;
use Elabftw\Interfaces\StorageInterface;
use Elabftw\Models\Users;
use Elabftw\Services\UsersHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Import items from a .eln
 */
#[AsCommand(name: 'items:import')]
class ImportResources extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import resources from an ELN archive')
            ->setHelp('This command will import resources from a provided ELN archive. It is more reliable than using the web interface as it will not suffer from timeouts.')
            ->addArgument('category_id', InputArgument::REQUIRED, 'Target category to import to')
            ->addArgument('userid', InputArgument::REQUIRED, 'User executing the request')
            ->addArgument('file', InputArgument::REQUIRED, 'Name of the file to import present in cache/elab folder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categoryId = (int) $input->getArgument('category_id');
        $userid = (int) $input->getArgument('userid');
        $filePath = $this->Fs->getPath($input->getArgument('file'));
        $uploadedFile = new UploadedFile($filePath, 'input.eln', null, null, true);
        $teamid = (int) (new UsersHelper($userid))->getTeamsFromUserid()[0]['id'];
        $Eln = new Eln(new Users($userid, $teamid), sprintf('items:%d', $categoryId), BasePermissions::Team->toJson(), BasePermissions::User->toJson(), $uploadedFile, Storage::CACHE->getStorage()->getFs());
        $Eln->import();

        $output->writeln(sprintf('Items successfully imported in category with ID %d.', $categoryId));

        return Command::SUCCESS;
    }
}
