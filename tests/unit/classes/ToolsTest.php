<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

class ToolsTest extends \PHPUnit\Framework\TestCase
{
    public function testFormatBytes(): void
    {
        $this->assertEquals('0.98 KiB', Tools::formatBytes(1000));
        $this->assertEquals('1.66 KiB', Tools::formatBytes(1699));
        $this->assertEquals('5.08 MiB', Tools::formatBytes(5323423));
        $this->assertEquals('4.96 GiB', Tools::formatBytes(5323423344));
        $this->assertEquals('21.40 TiB', Tools::formatBytes(23534909234464));
    }

    public function testGetExt(): void
    {
        $this->assertEquals('gif', Tools::getExt('myfile.gif'));
        $this->assertEquals('gif', Tools::getExt('/path/to/myfile.gif'));
        $this->assertEquals('unknown', Tools::getExt('/path/to/myfilegif'));
    }

    public function testMd2html(): void
    {
        $md = '[a link](https://www.elabftw.net) **in bold** _in italic_';
        $html = '<p><a href="https://www.elabftw.net">a link</a> <strong>in bold</strong> <em>in italic</em></p>';
        $this->assertEquals($html, Tools::md2html($md));
    }

    public function testError(): void
    {
        $this->assertEquals(Tools::error(), 'An error occurred!');
        $this->assertEquals(Tools::error(true), 'This section is out of your reach!');
    }

    public function testGetCalendarLang(): void
    {
        $this->assertEquals('ca', Tools::getCalendarLang('ca_ES'));
    }

    public function testgetLangsArr(): void
    {
        $langsArr = Tools::getLangsArr();
        $this->assertTrue(is_array($langsArr));
        $this->assertEquals('German', $langsArr['de_DE']);
    }

    public function testPrintArr(): void
    {
        $arr = array('Blah' => 42, array('Pwet', 1337));
        $out = '<ul><li><span style="color:red;">Blah</span><b> => </b><span style="color:blue;">42</span></li><li><span style="color:red;">0</span><b> => </b><span style="color:blue;"><ul><li><span style="color:red;">0</span><b> => </b><span style="color:blue;">Pwet</span></li><li><span style="color:red;">1</span><b> => </b><span style="color:blue;">1337</span></li></ul></span></li></ul>';
        $this->assertEquals($out, Tools::printArr($arr));
    }

    public function testShowStar(): void
    {
        $out = "<i style='color:#54aa08' class='fas fa-star' title='☻'></i><i style='color:#54aa08' class='fas fa-star' title='☻'></i><i style='color:gray' class='fas fa-star' title='☺'></i><i style='color:gray' class='fas fa-star' title='☺'></i><i style='color:gray' class='fas fa-star' title='☺'></i>";
        $this->assertEquals($out, Tools::showStars(2));
    }

    public function testQFilter(): void
    {
        $input = array(
            'tags' => array('some tag', 'another tag'),
            'q' => '',
            'cat' => '2',
            'mode' => 'show',
            'sort' => 'asc',
            'order' => 'date',
            'limit' => '15',
        );
        $output = '&tags[]=some tag&tags[]=another tag&q=&cat=2&mode=show&sort=asc&order=date&limit=15';
        $this->assertEquals($output, Tools::qFilter($input));
    }
}
