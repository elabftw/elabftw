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

use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Models\Config;
use Symfony\Component\HttpFoundation\Request;

class AuthTest extends \PHPUnit\Framework\TestCase
{
    private Auth $Auth;

    protected function setUp(): void
    {
        $this->Auth = new Auth(Config::getConfig(), Request::createFromGlobals());
    }

    public function testTryAuth(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->Auth->tryAuth();
    }
}
