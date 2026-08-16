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

class ToolsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetUuidv4(): void
    {
        $this->assertIsString(Tools::getUuidv4());
    }

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

    public function testMd2htmlPreservesDisplayMath(): void
    {
        $markdown = <<<'MARKDOWN'
            **matrix**

            $$
            \begin{bmatrix}
            H & \mathbf{1} \\
            \mathbf{1}^{T} & 0
            \end{bmatrix}
            $$

            \[
            \begin{align}
            a &= b \\
            c &= d
            \end{align}
            \]
            MARKDOWN;
        $html = Tools::md2html($markdown);

        $matrix = <<<'HTML'
            <div>$$
            \begin{bmatrix}
            H &amp; \mathbf{1} \\
            \mathbf{1}^{T} &amp; 0
            \end{bmatrix}
            $$</div>
            HTML;
        $align = <<<'HTML'
            <div>\[
            \begin{align}
            a &amp;= b \\
            c &amp;= d
            \end{align}
            \]</div>
            HTML;

        $this->assertStringContainsString('<strong>matrix</strong>', $html);
        $this->assertStringContainsString($matrix, $html);
        $this->assertStringContainsString($align, $html);
        $this->assertStringNotContainsString("$$\n\n", $html);
        $this->assertStringNotContainsString("\\[\n\n", $html);
        $this->assertStringNotContainsString('<br', $html);
    }

    public function testMd2htmlDoesNotParseDisplayMathInFencedCode(): void
    {
        $markdown = <<<'MARKDOWN'
            ```latex
            $$
            a & b \\
            c & d
            $$
            ```
            MARKDOWN;
        $html = Tools::md2html($markdown);

        $this->assertStringContainsString('<pre><code class="language-latex">$$', $html);
        $this->assertStringNotContainsString('<div>$$', $html);
    }

    public function testMd2htmlStripsRawHtml(): void
    {
        $html = Tools::md2html('<span onclick="alert(1)">text</span> **safe**');

        $this->assertSame('<p>text <strong>safe</strong></p>', $html);
    }

    public function testGetShortElabid(): void
    {
        $this->assertEquals('7995340c', Tools::getShortElabid('20220627-7995340c1921f38fd833c447be50b7101e4f852c'));
        $this->assertIsString(Tools::getShortElabid(''));
    }
}
