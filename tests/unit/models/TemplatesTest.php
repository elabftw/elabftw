<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ParamsProcessor;

class TemplatesTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Templates= new Templates(new Users(1, 1));
    }

    public function testCreate()
    {
        $this->Templates->create(new ParamsProcessor(array('name' => 'Test tpl', 'template' => 'pwet')));
    }

    public function testRead()
    {
        $this->Templates->setId(1);
        $this->assertTrue(is_array($this->Templates->read()));
    }

    public function testGetWriteableTemplatesList()
    {
        $this->assertTrue(is_array($this->Templates->getWriteableTemplatesList()));
    }

    public function testDuplicate()
    {
        $this->Templates->setId(1);
        $this->assertIsInt($this->Templates->duplicate());
    }

    public function testReadForUser()
    {
        $this->assertTrue(is_array($this->Templates->readForUser()));
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
        $this->Templates->destroy(1);
    }
}
