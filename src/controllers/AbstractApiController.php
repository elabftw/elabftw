<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;

/**
 * For API requests
 */
abstract class AbstractApiController implements ControllerInterface
{
    protected ?int $id = null;

    protected int $limit = 15;

    protected int $offset = 0;

    protected string $search = '';

    public function __construct(protected Users $Users, protected Request $Request, protected bool $canWrite = false)
    {
    }

    abstract protected function parseReq(): void;
}
