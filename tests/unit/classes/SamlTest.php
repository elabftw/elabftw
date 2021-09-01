<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\Config;
use Elabftw\Models\Idps;

class SamlTest extends \PHPUnit\Framework\TestCase
{
    private Saml $Saml;

    protected function setUp(): void
    {
        $this->Saml = new Saml(Config::getConfig(), new Idps());
    }

    public function testgetSettings(): void
    {
        $this->assertIsArray($this->Saml->getSettings(1));
    }
}
