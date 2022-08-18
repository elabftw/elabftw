<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ControllerInterface;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
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

    protected string $endpoint;

    // used by backupzip to get the period
    protected string $param;

    public function __construct(protected Users $Users, protected Request $Request, protected bool $canWrite = false)
    {
    }

    protected function parseReq(): array
    {
        // Check if the Authorization Token was sent along
        if (!$this->Request->server->has('HTTP_AUTHORIZATION')) {
            throw new ImproperActionException('No access token provided!');
        }
        if ($this->canWrite === false && $this->Request->getMethod() !== Request::METHOD_GET) {
            throw new ImproperActionException('You are using a read-only key to execute a write action.');
        }
        /**
         * so we receive the request already split in two by nginx
         * first part is "req" and then if there is any query string it ends up in "args"
         * generate an array with the request that looks like this
         * for /api/v1/experiments/1:
         *   array(5) {
         *   [0]=>
         *   string(0) ""
         *   [1]=>
         *   string(3) "api"
         *   [2]=>
         *   string(2) "v1"
         *   [3]=>
         *   string(11) "experiments"
         *   [4]=>
         *   string(1) "1"
         *   }
         */
        $req = explode('/', rtrim((string) $this->Request->query->get('req'), '/'));

        // now parse the query string (part after ?)
        if ($this->Request->query->has('limit')) {
            $this->limit = (int) $this->Request->query->get('limit');
        }
        if ($this->Request->query->has('offset')) {
            $this->offset = (int) $this->Request->query->get('offset');
        }
        if ($this->Request->query->has('search')) {
            $this->search = trim((string) $this->Request->query->get('search'));
        }

        // assign the endpoint (experiments, items, uploads, items_types, status)
        // 0 is "", 1 is "api", 2 is "v1"
        $this->endpoint = $req[3];

        // assign the id if there is one
        if (Check::id((int) ($req[4] ?? 0)) !== false) {
            $this->id = (int) $req[4];
        }

        // used by backup zip only for now
        // TODO remove with apiv1
        $this->param = $req[4] ?? '';
        return $req;
    }
}
