<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Enums\GhsSignalWord;
use Elabftw\Enums\Storage;

use function array_map;
use function json_encode;

class CompoundTest extends \PHPUnit\Framework\TestCase
{
    public function testFromPugViewGhs(): void
    {
        $json = Storage::FIXTURES->getStorage()->getFs()->read('cid-3345.json');
        $compound = Compound::fromPugView($json);
        $this->assertSame(GhsSignalWord::Danger, $compound->ghsSignalWord);
        $this->assertCount(37, $compound->ghsStatements);
        $this->assertSame('H300', $compound->ghsStatements[0]);
        $this->assertContains('H413', $compound->ghsStatements);
        $this->assertContains('P203', $compound->ghsStatements);
        $this->assertContains('P301+P316', $compound->ghsStatements);
        $this->assertContains('P403+P233', $compound->ghsStatements);
        // the pictograms are still read
        $this->assertTrue($compound->isToxic);
        $this->assertTrue($compound->isSeriousHealthHazard);
        $this->assertFalse($compound->isFlammable);
        $this->assertSame('Danger', $compound->toArray()['ghsSignalWord']);
    }

    public function testFromPugViewSeveralSources(): void
    {
        $string = static fn(string ...$strings): array => array(
            'StringWithMarkup' => array_map(static fn(string $s): array => array('String' => $s), $strings),
        );
        $record = array('Record' => array(
            'RecordNumber' => 1,
            'RecordTitle' => 'Test compound',
            'Section' => array(array(
                'TOCHeading' => 'Safety and Hazards',
                'Section' => array(array(
                    'TOCHeading' => 'Hazards Identification',
                    'Section' => array(array(
                        'TOCHeading' => 'GHS Classification',
                        'Information' => array(
                            array('Name' => 'Pictogram(s)', 'Value' => array('StringWithMarkup' => array(array('String' => '  ', 'Markup' => array(array('Extra' => 'Flammable')))))),
                            array('Name' => 'Signal', 'Value' => $string('Warning')),
                            array('Name' => 'GHS Hazard Statements', 'Value' => $string(
                                'H225 (100%): Highly Flammable liquid and vapor [Danger Flammable liquids]',
                                'EUH066: Repeated exposure may cause skin dryness or cracking',
                            )),
                            array('Name' => 'Precautionary Statement Codes', 'Value' => $string('P210, P233, and P301+P330+P331', '(The corresponding statement to each P-code can be found at the GHS Classification page.)')),
                            // a second source
                            array('Name' => 'Signal', 'Value' => $string('Danger')),
                            array('Name' => 'GHS Hazard Statements', 'Value' => $string('H360FD (50%): May damage fertility; May damage the unborn child', 'Not Classified', 'H225 (90%): Highly flammable liquid and vapour')),
                            array('Name' => 'Signal', 'Value' => $string('Warning')),
                        ),
                    )),
                )),
            )),
        ));
        $compound = Compound::fromPugView((string) json_encode($record));
        $this->assertSame(GhsSignalWord::Danger, $compound->ghsSignalWord);
        $this->assertSame(array('H225', 'EUH066', 'P210', 'P233', 'P301+P330+P331', 'H360FD'), $compound->ghsStatements);
        $this->assertTrue($compound->isFlammable);
        $this->assertSame('Test compound', $compound->name);
    }

    public function testFromPugViewWithoutGhs(): void
    {
        $compound = Compound::fromPugView((string) json_encode(array('Record' => array('RecordNumber' => 2, 'RecordTitle' => 'Inert', 'Section' => array()))));
        $this->assertNull($compound->ghsSignalWord);
        $this->assertSame(array(), $compound->ghsStatements);
    }

    public function testExtractGhsCodes(): void
    {
        $this->assertSame(
            array('H225', 'H319', 'P210', 'P233', 'H300+H310+H330', 'EUH066', 'H360Fd', 'P370+P380+P375[+P378]'),
            Compound::extractGhsCodes('H225, H319;P210 P233 H300+H310+H330 EUH066 H360Fd P370+P380+P375[+P378] H225 PH300 (The corresponding P-code)'),
        );
        $this->assertSame(array(), Compound::extractGhsCodes(''));
    }

    public function testMostSevereSignalWord(): void
    {
        $this->assertSame(GhsSignalWord::Danger, GhsSignalWord::mostSevere(GhsSignalWord::Warning, GhsSignalWord::Danger));
        $this->assertSame(GhsSignalWord::Danger, GhsSignalWord::mostSevere(GhsSignalWord::Danger, null));
        $this->assertSame(GhsSignalWord::Warning, GhsSignalWord::mostSevere(null, GhsSignalWord::Warning));
        $this->assertNull(GhsSignalWord::mostSevere(null, null));
    }
}
