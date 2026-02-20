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

    public function testEntityType(): void
    {
        $this->assertSame('templates.php', EntityType::Experiments->toTemplatePage());
        $this->assertSame('experiments-categories.php', EntityType::Experiments->toCategoryPage());
        $this->assertSame('experiments-status.php', EntityType::Experiments->toStatusPage());
    }

    public function testAuthType(): void
    {
        $this->assertIsInt(AuthType::Saml->asService());
    }

    public function testCurrency(): void
    {
        $this->assertIsString(Currency::NOK->toHuman());
        $this->assertIsString(Currency::DKK->toSymbol());
        $this->assertIsArray(Currency::getAssociativeArray());
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

    public function testMessages(): void
    {
        $this->assertSame(500, Messages::CriticalError->toHttpCode());
        $this->assertSame(403, Messages::InsufficientPermissions->toHttpCode());
    }
}
