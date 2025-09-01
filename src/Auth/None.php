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

use Elabftw\Exceptions\UnauthorizedException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Interfaces\AuthResponseInterface;
use Override;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

/**
 * When you're not logged in at all, or abandon a login
 */
final class None implements AuthInterface
{
    public function __construct(FlashBagAwareSessionInterface $session)
    {
        $session->clear();
    }

    #[Override]
    public function tryAuth(): AuthResponseInterface
    {
        throw new UnauthorizedException(_('The authentication was interrupted before completion.'));
    }
}
