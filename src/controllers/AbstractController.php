<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Override;

abstract class AbstractController implements ControllerInterface
{
    public function __construct(protected Users $requester, protected Request $Request) {}

    #[Override]
    abstract public function getResponse(): Response;
}
