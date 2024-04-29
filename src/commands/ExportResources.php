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
use Elabftw\Services\UsersHelper;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipStream\ZipStream;

/**
 * Export a category of resources
 */
#[AsCommand(name: 'items:export')]
class ExportResources extends Command
{
    public function __construct(private StorageInterface $Fs)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Export all items with a given category')
            ->setHelp('This command will generate a ELN archive with all the items of a particular category. It is more reliable than using the web interface as it will not suffer from timeouts.')
            ->addArgument('category_id', InputArgument::REQUIRED, 'Target category ID')
            ->addArgument('userid', InputArgument::REQUIRED, 'User executing the request');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $categoryId = (int) $input->getArgument('category_id');
        $userid = (int) $input->getArgument('userid');
        $teamid = (int) (new UsersHelper($userid))->getTeamsFromUserid()[0]['id'];
        $absolutePath = $this->Fs->getPath(sprintf(
            'export-%s-category_id-%d.eln',
            date('Y-m-d_H-i-s'),
            $categoryId,
        ));
        $fileStream = fopen($absolutePath, 'wb');
        if ($fileStream === false) {
            throw new RuntimeException('Could not open output stream!');
        }

        $ZipStream = new ZipStream(sendHttpHeaders:false, outputStream: $fileStream);
        $Entity = EntityType::Items->toInstance(new Users($userid, $teamid));
        $Maker = new MakeEln($ZipStream, $Entity, $Entity->getIdFromCategory($categoryId));
        $Maker->getStreamZip();

        fclose($fileStream);

        $output->writeln(sprintf('Items of category with ID %d successfully exported as ELN archive.', $categoryId));
        $output->writeln('Copy the generated archive from the container to the current directory with:');
        $output->writeln(sprintf('docker cp elabftw:%s .', $absolutePath));

        return Command::SUCCESS;
    }
}
