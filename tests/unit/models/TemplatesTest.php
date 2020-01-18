<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class TemplatesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Templates= new Templates(new Users(1, 1));
    }

    public function testCreateNew()
    {
        $this->Templates->createNew('Test tpl', 'pwet', 1);
    }

    public function testRead()
    {
        $this->Templates->setId(1);
        $this->assertTrue(is_array($this->Templates->read()));
    }

    public function testDuplicate()
    {
        $this->Templates->setId(1);
        $this->assertIsInt($this->Templates->duplicate());
    }

    public function testReadAll()
    {
        $this->assertTrue(is_array($this->Templates->readAll()));
    }

    public function testReadFromTeam()
    {
        $this->assertTrue(is_array($this->Templates->readFromTeam()));
    }

    public function testReadCommonBody()
    {
        $this->Templates->Users->userData['use_markdown'] = 1;
        $this->assertEquals('', $this->Templates->readCommonBody());
    }

    public function testUpdateCommon()
    {
        $this->Templates->updateCommon('Plop');
    }

    public function testUpdateTpl()
    {
        $this->Templates->updateTpl(1, 'my tpl', 'Plop');
    }

    public function testDestroy()
    {
        $this->Templates->destroy();
    }
}
