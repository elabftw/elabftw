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
        if ($this->canWrite === false && $this->Request->getMethod() !== Request::METHOD_GET) {
            throw new ImproperActionException('You are using a read-only key to execute a write action.');
        }
        /**
         * Nginx rewrite config (https://github.com/elabftw/elabimg/blob/4c9b4c2565323f1aa065d8ce5c87eb3e821895b4/src/nginx/common.conf#L74)
         * will put the request in 'req' query
         * example for /api/v2/experiments/42:
         *   array(5) {
         *   [0]=>
         *   string(0) ""
         *   [1]=>
         *   string(3) "api"
         *   [2]=>
         *   string(2) "v2"
         *   [3]=>
         *   string(11) "experiments"
         *   [4]=>
         *   string(1) "42"
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
        $this->endpoint = $req[3] ?? 'invalid_endpoint';

        // assign the id if there is one
        if (Check::id((int) ($req[4] ?? 0)) !== false) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset */
            $this->id = (int) $req[4];
        }
        // allow using "me" to refer to the current logged in user
        if (($req[4] ?? '') === 'me') {
            $this->id = $this->Users->userData['userid'];
        }
        // allow using "current" to refer to the current logged in team
        if (($req[4] ?? '') === 'current') {
            $this->id = $this->Users->userData['team'];
        }

        // used by backup zip only for now
        // TODO remove with apiv1
        $this->param = $req[4] ?? '';
        return $req;
    }
}
