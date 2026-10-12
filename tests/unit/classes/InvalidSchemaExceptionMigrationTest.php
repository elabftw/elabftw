<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <Deltablot>
 * @author Mouss <Deltablot>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\InvalidSchemaException;
use PHPUnit\Framework\TestCase;

class InvalidSchemaExceptionMigrationTest extends TestCase
{
    public function testErrorPageSubstitutesAllSchemaAndMigrationValues(): void
    {
        $exception = new InvalidSchemaException(123, 228, 7);
        $this->assertStringContainsString('Legacy schema: 123 / 228. Pending migrations: 7.', $exception->getMessage());
        $this->assertStringNotContainsString('%CURRENT_SCHEMA%', $exception->getMessage());
        $this->assertStringNotContainsString('%REQUIRED_SCHEMA%', $exception->getMessage());
        $this->assertStringNotContainsString('%PENDING_MIGRATIONS%', $exception->getMessage());
    }
}
