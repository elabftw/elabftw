<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Commands;

use Elabftw\Elabftw\i18n4Js;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate the translation files for typescript/javascript
 */
#[AsCommand(name: 'dev:i18n4js')]
class GenI18n4Js extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Generate translation files for javascript')
            ->setHelp('Generate translation files for javascript (i18next library) in ts/langs folder');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $i18n4Js = new i18n4Js();
        $i18n4Js->generate();
        return Command::SUCCESS;
    }
}
