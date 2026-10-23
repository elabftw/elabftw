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

use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Params\DisplayParams;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_map;
use function bin2hex;
use function json_encode;
use function random_bytes;
use function strtolower;
use function substr;

class EntitySearchTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public static function entityTypes(): array
    {
        return array_map(static fn(EntityType $type): array => array($type), EntityType::cases());
    }

    /**
     * @dataProvider entityTypes
     */
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

    /**
     * @dataProvider entityTypes
     */
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
}
