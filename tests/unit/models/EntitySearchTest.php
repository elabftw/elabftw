<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Codeception\Attribute\DataProvider;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Factories\LinksFactory;
use Elabftw\Params\DisplayParams;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Elabftw\Traits\TestsUtilsTrait;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function bin2hex;
use function json_encode;
use function random_bytes;
use function random_int;
use function strtolower;
use function substr;

class EntitySearchTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    #[DataProvider('entityTypes')]
    public function testCustomFieldValues(EntityType $entityType): void
    {
        $user = $this->getRandomUserInTeam(1, admin: 1);
        $Entity = $entityType->toInstance($user);
        $suffix = bin2hex(random_bytes(8));
        $title = 'Title' . $suffix;
        $value = 'Catalog' . $suffix;
        $multiValue = 'Selected' . $suffix;
        $ignored = 'Configuration' . $suffix;
        $metadata = json_encode(array('extra_fields' => array(
            'Catalog' => array('type' => 'text', 'value' => $value),
            'Selection' => array(
                'type' => 'select',
                'value' => array('Other', $multiValue),
                'options' => array('Other', $multiValue, $ignored),
                'allow_multi_values' => true,
            ),
            $ignored => array('type' => 'text', 'description' => $ignored, 'value' => ''),
            'Temperature' => array('type' => 'number', 'value' => '37'),
            'Notes' => array('type' => 'text', 'value' => "First line\nMultiline$suffix\tTabbed$suffix"),
        )), JSON_THROW_ON_ERROR);
        $id = $Entity->create(title: $title, metadata: $metadata);
        $Entity->setId($id);

        try {
            $search = static function (string $query) use ($entityType, $user): array {
                // Each request gets a fresh model, as readShow() collects bind values.
                return $entityType->toInstance($user)->readAll(new DisplayParams(
                    $user,
                    $entityType,
                    new InputBag(array('q' => $query, 'scope' => Scope::Everything->value)),
                ));
            };

            foreach (array(
                strtolower($value),
                'Catalog' . substr($suffix, 0, 8),
                $multiValue,
                'Multiline' . $suffix,
                'Tabbed' . $suffix,
                '37',
                $title . ' ' . $value,
                $value . ' ' . $multiValue,
                '"' . $value . '"',
                $value . ' AND title:' . $title,
            ) as $query) {
                $results = $search($query);
                $this->assertContains($id, array_column($results, 'id'), $query);
                foreach ($results as $result) {
                    $this->assertArrayNotHasKey('metadata_search', $result);
                }
            }

            $this->assertNotContains($id, array_column($search($ignored), 'id'));
            $this->assertNotContains($id, array_column($search('!' . $value), 'id'));
            $this->assertArrayNotHasKey('metadata_search', $Entity->readOne());

            $updated = 'Updated' . $suffix;
            $Entity->patch(Action::UpdateMetadataField, array('Catalog' => $updated));
            $this->assertContains($id, array_column($search($updated), 'id'));
            $this->assertNotContains($id, array_column($search($value), 'id'));

            $Entity->patch(Action::Update, array('metadata' => '{}'));
            $this->assertNotContains($id, array_column($search($updated), 'id'));
            $this->assertNotContains($id, array_column($search($multiValue), 'id'));
        } finally {
            $Entity->destroy();
        }
    }

    #[DataProvider('entityTypes')]
    public function testCustomFieldSearchRespectsPermissions(EntityType $entityType): void
    {
        $owner = $this->getUserInTeam(2, admin: 1);
        $reader = $this->getUserInTeam(1);
        $Entity = $entityType->toInstance($owner);
        $value = 'Private' . bin2hex(random_bytes(8));
        $id = $Entity->create(
            canreadBase: BasePermissions::UserOnly,
            metadata: json_encode(array('extra_fields' => array(
                'Catalog' => array('type' => 'text', 'value' => $value),
            )), JSON_THROW_ON_ERROR),
        );
        $Entity->setId($id);

        try {
            $results = $entityType->toInstance($reader)->readAll(new DisplayParams(
                $reader,
                $entityType,
                new InputBag(array('q' => $value, 'scope' => Scope::Everything->value)),
            ));
            $this->assertNotContains($id, array_column($results, 'id'));
        } finally {
            $Entity->destroy();
        }
    }

    // a CAS number like 81-30-1 must not match an entry with custom_id 81 (#5655)
    #[DataProvider('concreteEntityTypes')]
    public function testCasNumberDoesNotMatchCustomId(EntityType $entityType): void
    {
        $user = $this->getRandomUserInTeam(1, admin: 1);
        $Entity = $entityType->toInstance($user);
        $id = $Entity->create();
        $Entity->setId($id);
        $customId = random_int(100000, 9999999);
        $Entity->patch(Action::Update, array('custom_id' => $customId));

        try {
            $search = static function (string $query) use ($entityType, $user): array {
                return array_column($entityType->toInstance($user)->readAll(new DisplayParams(
                    $user,
                    $entityType,
                    new InputBag(array('q' => $query, 'scope' => Scope::Everything->value)),
                )), 'id');
            };
            $cas = $customId . '-30-1';
            // simple search
            $this->assertNotContains($id, $search($cas));
            // advanced search
            $this->assertNotContains($id, $search('"' . $cas . '"'));
            // an integer still finds the entry by its custom_id
            $this->assertContains($id, $search((string) $customId));
            $this->assertContains($id, $search('"' . $customId . '"'));
        } finally {
            $Entity->destroy();
        }
    }

    #[DataProvider('entityTypes')]
    public function testCompoundFilter(EntityType $entityType): void
    {
        $user = $this->getRandomUserInTeam(1, admin: 1);
        $Compounds = new Compounds(new HttpGetter(new Client()), $user, new NullFingerprinter(), false);
        $compoundId = $Compounds->create(name: 'Compound' . bin2hex(random_bytes(8)));
        $Linked = $entityType->toInstance($user);
        $linkedId = $Linked->create();
        $Linked->setId($linkedId);
        LinksFactory::getCompoundsLinks($Linked, $compoundId)->postAction(Action::Create, array());
        $Unlinked = $entityType->toInstance($user);
        $Unlinked->setId($Unlinked->create());

        try {
            $results = $entityType->toInstance($user)->readAll(new DisplayParams(
                $user,
                $entityType,
                new InputBag(array('compound' => $compoundId, 'scope' => Scope::Everything->value)),
            ));
            $this->assertSame(array($linkedId), array_column($results, 'id'));
        } finally {
            $Linked->destroy();
            $Unlinked->destroy();
        }
    }

    #[DataProvider('concreteEntityTypes')]
    public function testCompoundFilterNeedsReadAccessToTheCompound(EntityType $entityType): void
    {
        $owner = $this->getRandomUserInTeam(1, admin: 1);
        $Compounds = new Compounds(new HttpGetter(new Client()), $owner, new NullFingerprinter(), false);
        $compoundId = $Compounds->create(name: 'Private compound' . bin2hex(random_bytes(8)), canreadBase: BasePermissions::UserOnly);
        // an entry everyone can read, linked to a compound only its owner can read
        $Linked = $entityType->toInstance($owner);
        $Linked->setId($Linked->create());
        $Linked->patch(Action::Update, array('canread_base' => BasePermissions::Organization->value));
        LinksFactory::getCompoundsLinks($Linked, $compoundId)->postAction(Action::Create, array());
        $reader = $this->getUserInTeam(2);
        $this->assertNotSame($owner->userid, $reader->userid);

        $search = fn($user): array => array_column($entityType->toInstance($user)->readAll(new DisplayParams(
            $user,
            $entityType,
            new InputBag(array('compound' => $compoundId, 'scope' => Scope::Everything->value)),
        )), 'id');
        try {
            $this->assertSame(array($Linked->id), $search($owner));
            // the entry is readable, but the filter must not reveal its link to the compound
            $this->assertSame(array(), $search($reader));
        } finally {
            $Linked->destroy();
        }
    }
}
