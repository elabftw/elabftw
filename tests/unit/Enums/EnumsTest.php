<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

class EnumsTest extends \PHPUnit\Framework\TestCase
{
    public function testApiEndpoint(): void
    {
        $this->assertIsArray(ApiEndpoint::getCases());
    }

    public function testEntrypoint(): void
    {
        array_map(fn ($case) => $this->assertStringEndsWith('.php', $case->toPage()), Entrypoint::cases());
    }
}
