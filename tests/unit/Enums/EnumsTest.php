<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

use Elabftw\Exceptions\ImproperActionException;

use function array_map;

class EnumsTest extends \PHPUnit\Framework\TestCase
{
    public function testApiEndpoint(): void
    {
        $this->assertIsArray(ApiEndpoint::getCases());
    }

    public function testEntrypoint(): void
    {
        array_map(
            fn(Entrypoint $case) => $this->assertStringEndsWith('.php', $case->toPage()),
            Entrypoint::cases(),
        );
    }

    public function testLanguage(): void
    {
        $this->assertIsArray(Language::getAllHuman());
        $this->assertEquals('ca', Language::Catalan->toCalendar());
    }

    public function testMeaning(): void
    {
        $this->assertIsArray(Meaning::getAssociativeArray());
    }

    public function testScope(): void
    {
        $this->assertIsString(Scope::toIcon(Scope::User));
    }

    public function testSort(): void
    {
        $this->assertIsString(Sort::Asc->toFa());
    }

    public function testEnforeMfa(): void
    {
        $this->assertIsString(EnforceMfa::toHuman(EnforceMfa::Admins));
        $this->assertIsArray(EnforceMfa::getAssociativeArray());
    }

    public function testApiSubModels(): void
    {
        array_map(
            fn(EntityType $case) => $this->assertIsArray(
                ApiSubModels::validSubModelsForEndpoint(ApiEndpoint::from($case->value)),
            ),
            EntityType::cases(),
        );
        $this->assertIsArray(ApiSubModels::validSubModelsForEndpoint(ApiEndpoint::Teams));
        $this->assertIsArray(ApiSubModels::validSubModelsForEndpoint(ApiEndpoint::Users));
        $this->assertIsArray(ApiSubModels::validSubModelsForEndpoint(ApiEndpoint::Event));
        $this->expectException(ImproperActionException::class);
        $this->assertIsArray(ApiSubModels::validSubModelsForEndpoint(ApiEndpoint::Info));
    }
}
