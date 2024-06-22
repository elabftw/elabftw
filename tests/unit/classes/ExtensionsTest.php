<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class ExtensionsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetIconFromExtension(): void
    {
        $this->assertEquals('fa-file-archive', Extensions::getIconFromExtension('zip'));
        $this->assertEquals('fa-file-code', Extensions::getIconFromExtension('py'));
        $this->assertEquals('fa-file-excel', Extensions::getIconFromExtension('xls'));
        $this->assertEquals('fa-file-video', Extensions::getIconFromExtension('avi'));
        $this->assertEquals('fa-file-powerpoint', Extensions::getIconFromExtension('ppt'));
        $this->assertEquals('fa-file-pdf', Extensions::getIconFromExtension('pdf'));
        $this->assertEquals('fa-file-image', Extensions::getIconFromExtension('jpg'));
        $this->assertEquals('fa-file-word', Extensions::getIconFromExtension('docx'));
        $this->assertEquals('fa-file', Extensions::getIconFromExtension('elab'));
    }
}
