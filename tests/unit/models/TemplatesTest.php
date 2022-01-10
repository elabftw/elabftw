<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Elabftw\EntityParams;

class TemplatesTest extends \PHPUnit\Framework\TestCase
{
    private Templates $Templates;

    protected function setUp(): void
    {
        $this->Templates= new Templates(new Users(1, 1));
    }

    public function testCreate(): void
    {
        $this->Templates->create(new EntityParams('Test tpl', '', array('body' => 'pwet')));
    }

    public function testRead(): void
    {
        $this->Templates->setId(1);
        $this->assertTrue(is_array($this->Templates->read(new ContentParams())));
    }

    public function testGetWriteableTemplatesList(): void
    {
        $this->assertTrue(is_array($this->Templates->getWriteableTemplatesList()));
    }

    public function testDuplicate(): void
    {
        $this->Templates->setId(1);
        $this->assertIsInt($this->Templates->duplicate());
    }

    public function testReadForUser(): void
    {
        $this->assertTrue(is_array($this->Templates->readForUser()));
    }

    public function testUpdate(): void
    {
        $this->Templates->setId(1);
        $this->Templates->update(new EntityParams('Database item 1', 'title'));
        $this->Templates->update(new EntityParams('pwet', 'body'));
    }

    public function testDestroy(): void
    {
        $this->Templates->setId(1);
        $this->Templates->destroy();
    }
}
