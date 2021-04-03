<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Exceptions\ResourceNotFoundException;

class PrivacyPolicyTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->PrivacyPolicy = new PrivacyPolicy(new Config());
    }

    public function testReadEmpty(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->PrivacyPolicy->read();
    }

    public function testUpdate(): void
    {
        $txt = 'Some privacy policy';
        $this->PrivacyPolicy->update($txt);
        $this->setUp();
        $this->assertEquals($txt, $this->PrivacyPolicy->read());
    }

    public function testClear(): void
    {
        $this->PrivacyPolicy->clear();
        $this->setUp();
        $this->expectException(ResourceNotFoundException::class);
        $this->PrivacyPolicy->read();
    }
}
