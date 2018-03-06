<?php
namespace Elabftw\Elabftw;

class SamlTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->Saml = new Saml(new Config(), new Idps());
    }

    public function testgetSettings()
    {
        $this->assertTrue(is_array($this->Saml->getSettings(1)));
    }
}
