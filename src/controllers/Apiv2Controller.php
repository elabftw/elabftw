<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Status;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use function implode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For API V2 requests
 */
class Apiv2Controller extends AbstractApiController
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    //@phpstan-ignore-next-line
    private $Entity;

    private array $allowedMethods = array('GET', 'POST', 'DELETE', 'PATCH', 'PUT');

    // experiments, items or uploads
    private string $endpoint;

    private Users $Users;

    private AbstractEntity|Config $Model;

    public function getResponse(): Response
    {
        try {
            $this->parseReq();

            if ($this->canWrite === false && $this->Request->server->get('REQUEST_METHOD') !== Request::METHOD_GET) {
                throw new ImproperActionException('You are using a read-only key to execute a write action.');
            }

            return match ($this->Request->server->get('REQUEST_METHOD')) {
                Request::METHOD_GET => new JsonResponse($this->Model->readOne(), Response::HTTP_OK),
                Request::METHOD_POST => die('POST'),
                Request::METHOD_DELETE => new JsonResponse($this->Model->destroy(), Response::HTTP_NO_CONTENT),
                Request::METHOD_PATCH => $this->handlePatch(),
                // send error 405 for Method Not Allowed, with Allow header as per spec:
                // https://tools.ietf.org/html/rfc7231#section-7.4.1
                // Note: can only be triggered with a HEAD because the allowed methods are filtered at nginx level too
                default => new Response('Invalid HTTP request method!', 405, array('Allow' => implode(', ', $this->allowedMethods)))
            };
        } catch (IllegalActionException $e) {
            $error = array(
                'code' => 403,
                'message' => 'Access Forbidden',
                'description' => $e->getMessage(),
            );
            return new JsonResponse($error, $error['code']);
        } catch (ResourceNotFoundException $e) {
            $error = array(
                'code' => 404,
                'message' => 'Resource Not Found',
                'description' => 'The resource was not found.',
            );
            return new JsonResponse($error, $error['code']);
            // must be after the catch ResourceNotFound because it's their parent
        } catch (ImproperActionException $e) {
            $error = array(
                'code' => 400,
                'message' => 'Bad Request',
                'description' => $e->getMessage(),
            );
            return new JsonResponse($error, $error['code']);
        }
    }

    /**
     * Set the id and endpoints fields
     */
    protected function parseReq(): void
    {
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

        // assign the id if there is one
        if (Check::id((int) end($req)) !== false) {
            $this->id = (int) end($req);
        }

        // assign the endpoint (experiments, items, uploads, items_types, status)
        // 0 is "", 1 is "api", 2 is "v1"
        $this->endpoint = $req[3];

        // verify the key and load user info
        $ApiKeys = new ApiKeys(new Users());
        $keyArr = $ApiKeys->readFromApiKey($this->Request->server->get('HTTP_AUTHORIZATION') ?? '');
        $this->Users = new Users((int) $keyArr['userid'], (int) $keyArr['team']);
        $this->canWrite = (bool) $keyArr['canWrite'];

        // load Model
        $this->Model = $this->getModel();
    }

    private function handlePatch(): Response
    {
        $reqBody = json_decode((string) $this->Request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        return new JsonResponse($this->Model->patch($reqBody), Response::HTTP_OK);
    }

    private function getModel(): AbstractEntity|Config
    {
        switch ($this->endpoint) {
            case 'config':
                // restrict Config to sysadmin users
                if ($this->Users->userData['is_sysadmin'] !== 1) {
                    throw new IllegalActionException('Non sysadmin user tried to use the config api endpoint.');
                }
                return Config::getConfig();
            case 'experiments':
            case 'items':
            case 'templates':
            case 'items_types':
                return (new EntityFactory($this->Users, $this->endpoint, $this->id))->getEntity();
            default:
                throw new ImproperActionException('Invalid endpoint.');
        }
    }
}
