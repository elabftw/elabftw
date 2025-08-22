<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Enums\Language;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Override;

/**
 * Anonymous auth service
 */
final class Anon implements AuthInterface
{
    public function __construct(bool $isAnonAllowed, private int $team, private Language $lang)
    {
        if (!$isAnonAllowed) {
            throw new IllegalActionException('Cannot login as anon because it is not allowed by sysadmin!');
        }
    }

    /**
     * Not much to do here because anonymous user can't be authenticated!
     */
    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        return new AnonAuthResponse($this->team, $this->lang);
    }
}
