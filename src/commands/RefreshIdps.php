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

use DOMDocument;
use Elabftw\Models\Idps;
use Elabftw\Models\IdpsSources;
use Elabftw\Models\UltraAdmin;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\Url2Xml;
use Elabftw\Services\Xml2Idps;
use GuzzleHttp\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Refresh Idps sources and refresh associated Idps
 */
#[AsCommand(name: 'idps:refresh')]
class RefreshIdps extends Command
{
    public function __construct(private string $proxy)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('For each defined Idp source that is auto-refreshable, fetch the latest version and update metadata of associated Idps')
            ->setHelp('Refresh Idps associated with an URL source and auto-refreshable');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $requester = new UltraAdmin();
        $IdpsSources = new IdpsSources($requester);
        $Idps = new Idps($requester);
        $getter = new HttpGetter(new Client(), $this->proxy);
        $sources = $IdpsSources->readAllAutoRefreshable();
        foreach ($sources as $source) {
            $IdpsSources->setId($source['id']);
            $Url2Xml = new Url2Xml($getter, $source['url'], new DOMDocument());
            $dom = $Url2Xml->getXmlDocument();
            $Xml2Idps = new Xml2Idps($dom, Idps::SSO_BINDING, Idps::SLO_BINDING);
            $IdpsSources->refresh($Xml2Idps, $Idps);
        }
        return Command::SUCCESS;
    }
}
