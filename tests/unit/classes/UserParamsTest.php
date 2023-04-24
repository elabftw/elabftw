<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\ImproperActionException;

class UserParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testValidUntilEmpty(): void
    {
        $params = new UserParams('valid_until', '');
        $this->assertEquals('3000-01-01', $params->getContent());
    }

    public function testValidUntil(): void
    {
        $input = '2023-02-03';
        $params = new UserParams('valid_until', $input);
        $this->assertEquals($input, $params->getContent());
    }

    public function testInvalidOrcid(): void
    {
        $invalidOrcid = '1234-5678-1212-000X';
        $params = new UserParams('orcid', $invalidOrcid);
        $this->expectException(ImproperActionException::class);
        $params->getContent();
        $invalidOrcid = '1234-5678-1212-0001';
        $params = new UserParams('orcid', $invalidOrcid);
        $this->expectException(ImproperActionException::class);
        $params->getContent();
    }
}
