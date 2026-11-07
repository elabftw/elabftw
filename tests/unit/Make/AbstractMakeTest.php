<?php

declare(strict_types=1);

namespace Elabftw\Make;

use Override;

use function rawurlencode;

class AbstractMakeTest extends \PHPUnit\Framework\TestCase
{
    public function testResponseEncodesUnicodeFilename(): void
    {
        $filename = '2026-10-11-研究-🧪-%20.pdf';
        $response = $this->getMaker($filename)->getResponse();
        $header = $response->headers->get('Content-Disposition');

        $this->assertNotNull($header);
        $this->assertStringStartsWith('inline; filename=', $header);
        $this->assertStringContainsString("filename*=utf-8''" . rawurlencode($filename), $header);
        $this->assertMatchesRegularExpression('/^[\x20-\x7E]+$/D', $header);
        $this->assertSame('Export content', $response->getContent());
    }

    public function testResponsePreservesAsciiFilename(): void
    {
        $response = $this->getMaker('export-elabftw.pdf')->getResponse();
        $this->assertSame('inline; filename=export-elabftw.pdf', $response->headers->get('Content-Disposition'));
    }

    private function getMaker(string $filename): AbstractMake
    {
        return new class ($filename) extends AbstractMake {
            public function __construct(private string $filename) {}

            #[Override]
            public function getFileName(): string
            {
                return $this->filename;
            }

            #[Override]
            public function getFileContent(): string
            {
                return 'Export content';
            }
        };
    }
}
