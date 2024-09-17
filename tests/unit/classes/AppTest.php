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

class AppTest extends \PHPUnit\Framework\TestCase
{
    public function testGetWhatsnewLink(): void
    {
        $this->assertEquals('https://www.deltablot.com/posts/release-50100', App::getWhatsnewLink(50169));
        $this->assertEquals('https://www.deltablot.com/posts/release-66600', App::getWhatsnewLink(66642));
    }
}
