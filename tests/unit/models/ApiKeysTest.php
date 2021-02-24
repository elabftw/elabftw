<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class ApiKeysTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->ApiKeys = new ApiKeys(new Users(1, 1));
        $this->key = $this->ApiKeys->create('my key', 0);
    }

    public function testCreate()
    {
        $this->assertTrue(\mb_strlen($this->key) === 84);
    }

    public function testReadAll()
    {
        $res = $this->ApiKeys->readAll();
        $this->assertIsArray($res);
        $this->assertTrue($res[1]['name'] === 'my key');
        $this->assertTrue($res[1]['can_write'] === '0');
    }

    public function testReadFromApiKey()
    {
        $res = $this->ApiKeys->readFromApiKey($this->key);
        $this->assertTrue($res['userid'] === '1');
        $this->assertTrue($res['canWrite'] === '0');
    }

    public function testDestroy()
    {
        $this->assertTrue($this->ApiKeys->destroy(2));
    }
}
