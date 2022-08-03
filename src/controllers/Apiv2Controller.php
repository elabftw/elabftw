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
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use function implode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For API V2 requests
 */
class Apiv2Controller extends AbstractApiController
{
    private array $allowedMethods = array('GET', 'POST', 'DELETE', 'PATCH', 'PUT');

    private AbstractEntity | Config $Model;

    public function getResponse(): Response
    {
        try {
            $this->parseReq();

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
        parent::parseReq();
        // load Model
        $this->Model = $this->getModel();
    }

    private function handlePatch(): Response
    {
        $reqBody = json_decode((string) $this->Request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        return new JsonResponse($this->Model->patch($reqBody), Response::HTTP_OK);
    }

    private function getModel(): AbstractEntity | Config
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
