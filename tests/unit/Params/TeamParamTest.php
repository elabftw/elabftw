<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Params;

use Elabftw\Exceptions\ImproperActionException;

class TeamParamTest extends \PHPUnit\Framework\TestCase
{
    public function testDeletionReasonOptionsAreTrimmedStrings(): void
    {
        $params = new TeamParam('deletion_reason_options', '["  Consent withdrawn  ", "", "Used in research"]');
        $this->assertSame('["Consent withdrawn","Used in research"]', $params->getContent());
    }

    public function testDeletionReasonOptionsRejectNestedArrays(): void
    {
        $params = new TeamParam('deletion_reason_options', '["ok", ["nested"]]');
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }

    public function testDeletionReasonOptionsRejectNonList(): void
    {
        $params = new TeamParam('deletion_reason_options', '{"a": "b"}');
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }

    public function testDeletionReasonCategoriesAreCastToInts(): void
    {
        $params = new TeamParam('deletion_reason_categories', '["3", 5]');
        $this->assertSame('[3,5]', $params->getContent());
    }

    public function testEmptyDeletionReasonListIsNull(): void
    {
        $params = new TeamParam('deletion_reason_tags', '');
        $this->assertNull($params->getContent());
    }
}
