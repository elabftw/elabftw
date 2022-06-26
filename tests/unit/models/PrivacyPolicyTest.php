<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\ContentParams;
use Elabftw\Exceptions\ResourceNotFoundException;

class PrivacyPolicyTest extends \PHPUnit\Framework\TestCase
{
    private PrivacyPolicy $PrivacyPolicy;

    protected function setUp(): void
    {
        $this->PrivacyPolicy = new PrivacyPolicy(Config::getConfig());
    }

    public function testReadEmpty(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->PrivacyPolicy->readAll();
    }

    public function testUpdate(): void
    {
        $txt = 'Some privacy policy';
        $this->PrivacyPolicy->update(new ContentParams($txt));
        $this->setUp();
        $this->assertEquals($txt, $this->PrivacyPolicy->readAll()[0]);
    }

    public function testClear(): void
    {
        $this->PrivacyPolicy->destroy();
        $this->setUp();
        $this->expectException(ResourceNotFoundException::class);
        $this->PrivacyPolicy->readOne();
    }
}
