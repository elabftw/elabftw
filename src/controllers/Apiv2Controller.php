<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Elabftw\EntityParams;
use Elabftw\Elabftw\Tools;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use function implode;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For API V2 requests
 */
class Apiv2Controller extends AbstractApiController
{
    private array $allowedMethods = array('GET', 'POST', 'DELETE', 'PATCH', 'PUT');

    private RestInterface $Model;

    private array $reqBody = array();

    public function getResponse(): Response
    {
        try {
            $this->parseReq();

            return match ($this->Request->server->get('REQUEST_METHOD')) {
                Request::METHOD_GET => new JsonResponse($this->handleGet(), Response::HTTP_OK),
                Request::METHOD_POST => $this->handlePost(),
                Request::METHOD_DELETE => new JsonResponse($this->Model->destroy(), Response::HTTP_NO_CONTENT),
                Request::METHOD_PATCH => new JsonResponse($this->Model->patch($this->reqBody), Response::HTTP_OK),
                // send error 405 for Method Not Allowed, with Allow header as per spec:
                // https://tools.ietf.org/html/rfc7231#section-7.4.1
                // Note: can only be triggered with a HEAD because the allowed methods are filtered at nginx level too
                default => new Response('Invalid HTTP request method!', Response::HTTP_METHOD_NOT_ALLOWED, array('Allow' => implode(', ', $this->allowedMethods)))
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
        if ($this->Request->getContent()) {
            try {
                $this->reqBody = json_decode((string) $this->Request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new ImproperActionException('Error decoding json payload.');
            }
        }
    }

    private function handlePost(): Response
    {
        // todo make it so we don't need to cast to string!!
        $params = new EntityParams((string) ($this->reqBody['category_id'] ?? -1));
        // @phpstan-ignore-next-line
        $id = $this->Model->create($params);
        return new Response('', Response::HTTP_CREATED, array('Location' => sprintf('%s/%s%d', SITE_URL, $this->Model->getViewPage(), $id)));
    }

    private function handleGet(): array
    {
        if ($this->id !== null) {
            return $this->Model->readOne();
        }
        return $this->Model->readAll();
    }

    private function getModel(): RestInterface
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
            case 'experiments_templates':
            case 'items_types':
                return (new EntityFactory($this->Users, $this->endpoint, $this->id))->getEntity();
            default:
                throw new ImproperActionException('Invalid endpoint.');
        }
    }
}
