<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateTimeImmutable;
use Elabftw\Elabftw\App;
use ZipStream\ZipStream;

/**
 * Abstract class for creating an ELN archive
 */
abstract class AbstractMakeEln extends AbstractMakeZip
{
    protected const string HASH_ALGO = 'sha256';

    protected string $extension = '.eln';

    protected array $authors = array();

    protected array $rootParts = array();

    protected array $dataEntities = array();

    protected array $processedEntities = array();

    protected DateTimeImmutable $creationDateTime;

    // the array that will be converted to json-ld
    protected array $dataArr = array();

    // name of the folder containing everything
    protected string $root;

    public function __construct(protected ZipStream $Zip)
    {
        parent::__construct($Zip);

        $this->creationDateTime = new DateTimeImmutable();
        $this->root = $this->creationDateTime->format('Y-m-d-His') . '-export';
        // initialize the dataArr that contains the full json-ld with the context and graph
        $this->dataArr = array(
            '@context' => 'https://w3id.org/ro/crate/1.1/context',
            '@graph' => array(
                array(
                    '@id' => 'ro-crate-metadata.json',
                    '@type' => 'CreativeWork',
                    'about' => array('@id' => './'),
                    'conformsTo' => array('@id' => 'https://w3id.org/ro/crate/1.1'),
                    'dateCreated' => $this->creationDateTime->format(DateTimeImmutable::ATOM),
                    'sdPublisher' => array('@id' => '#publisher'),
                    'version' => '1.0',
                ),
            ),
        );
        $this->dataArr['@graph'][] = array(
            '@id' => 'https://www.deltablot.com',
            '@type' => 'Organization',
            'areaServed' => 'Laniakea Supercluster',
            'name' => 'Deltablot',
            'logo' => 'https://www.deltablot.com/img/logos/deltablot.svg',
            'slogan' => 'Open Source software for research labs.',
            'url' => 'https://www.deltablot.com',
        );
        $this->dataArr['@graph'][] = array(
            '@id' => '#publisher',
            '@type' => 'Organization',
            'areaServed' => 'Laniakea Supercluster',
            'name' => 'eLabFTW',
            'logo' => 'https://www.elabftw.net/img/elabftw-logo-only.svg',
            'slogan' => 'A free and open source electronic lab notebook.',
            'url' => 'https://www.elabftw.net',
            'parentOrganization' => array('@id' => 'https://www.deltablot.com'),
        );
    }

    public function getFileName(): string
    {
        return $this->root . $this->extension;
    }

    // Create Action: https://www.researchobject.org/ro-crate/1.1/provenance.html#recording-changes-to-ro-crates
    protected function getCreateActionNode(): array
    {
        return array(
            array(
                '@id' => '#ro-crate_created',
                '@type' => 'CreateAction',
                'object' => array('@id' => './'),
                'name' => 'RO-Crate created',
                'endTime' => $this->creationDateTime->format(DateTimeImmutable::ATOM),
                'instrument' => array('@id' => 'https://www.elabftw.net'),
                'actionStatus' =>  array(
                    '@id' => 'http://schema.org/CompletedActionStatus',
                ),
            ),
            array(
                '@id' => 'https://www.elabftw.net',
                '@type' => 'SoftwareApplication',
                'name' => 'eLabFTW',
                'version' => App::INSTALLED_VERSION,
                'identifier' => 'https://www.elabftw.net',
            ),
        );
    }
}
