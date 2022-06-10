<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\CreateApikey;
use function mb_strlen;

class ApiKeysTest extends \PHPUnit\Framework\TestCase
{
    private ApiKeys $ApiKeys;

    protected function setUp(): void
    {
        $this->ApiKeys = new ApiKeys(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $params = new CreateApikey('test key', '', 1);
        $this->ApiKeys->create($params);
        $this->assertTrue(mb_strlen($params->getKey()) === 84);
    }

    public function testReadAll(): void
    {
        $res = $this->ApiKeys->readAll();
        $this->assertIsArray($res);
        $this->assertTrue($res[1]['name'] === 'test key');
        $this->assertTrue($res[1]['can_write'] === '1');
    }

    public function testReadFromApiKey(): void
    {
        $params = new CreateApikey('my key', '', 0);
        $this->ApiKeys->create($params);
        $res = $this->ApiKeys->readFromApiKey($params->getKey());
        $this->assertTrue($res['userid'] === '1');
        $this->assertTrue($res['canWrite'] === '0');
    }

    public function testDestroy(): void
    {
        $this->ApiKeys->setId(2);
        $this->assertTrue($this->ApiKeys->destroy());
    }
}
