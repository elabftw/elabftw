<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Enums\Storage;

class i18n4JsTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerate(): void
    {
        $fs = Storage::MEMORY->getStorage()->getFs();
        $i18n4Js = new i18n4Js($fs);
        $i18n4Js->generate();
        $this->assertTrue($fs->fileExists('fr_FR.ts'));
    }
}
