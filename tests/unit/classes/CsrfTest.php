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

use Elabftw\Exceptions\InvalidCsrfTokenException;
use Symfony\Component\HttpFoundation\Request;

class CsrfTest extends \PHPUnit\Framework\TestCase
{
    public function testGetToken(): void
    {
        $RequestMock = $this->createMock(Request::class);
        $Csrf = new Csrf($RequestMock);
        $this->assertIsString($Csrf->getToken());
    }

    public function testValidateGet(): void
    {
        $Request = Request::create('/', 'GET');
        $Csrf = new Csrf($Request);
        $Csrf->validate();
    }

    public function testValidateAjaxFail(): void
    {
        $Request = Request::create('/', 'POST');
        $Request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $Csrf = new Csrf($Request);
        $this->expectException(InvalidCsrfTokenException::class);
        $Csrf->validate();
    }

    public function testValidateFormFail(): void
    {
        $Request = Request::create('/', 'POST');
        $Csrf = new Csrf($Request);
        $this->expectException(InvalidCsrfTokenException::class);
        $Csrf->validate();
    }

    public function testValidateForm(): void
    {
        $Request = Request::create('/', 'POST', array('csrf' => 'fake-token'));
        $Csrf = new Csrf($Request);
        $Csrf->setToken('fake-token');
        $Csrf->validate();
    }
}
