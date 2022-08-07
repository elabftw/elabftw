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
use Elabftw\Enums\Action;
use Elabftw\Enums\ExportFormat;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\Config;
use Elabftw\Models\Users;
use function implode;
use JsonException;
use const SITE_URL;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

/**
 * For API V2 requests
 */
class Apiv2Controller extends AbstractApiController
{
    private array $allowedMethods = array('GET', 'POST', 'DELETE', 'PATCH', 'PUT');

    private RestInterface $Model;

    private array $reqBody = array();

    private ExportFormat $format = ExportFormat::Json;

    private Action $action = Action::Create;

    public function getResponse(): Response
    {
        try {
            // only accept json content-type unless it's GET (also prevents csrf!)
            if ($this->Request->server->get('REQUEST_METHOD') !== Request::METHOD_GET && $this->Request->headers->get('content-type') !== 'application/json') {
                throw new ImproperActionException('Incorrect content-type header.');
            }
            $this->parseReq();

            return match ($this->Request->server->get('REQUEST_METHOD')) {
                Request::METHOD_GET => $this->handleGet(),
                Request::METHOD_POST => $this->handlePost(),
                Request::METHOD_DELETE => new JsonResponse($this->Model->destroy(), Response::HTTP_NO_CONTENT),
                Request::METHOD_PATCH => new JsonResponse($this->handlePatch(), Response::HTTP_OK),
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

        // FORMAT
        if ($this->Request->query->has('format') && $this->Model instanceof AbstractConcreteEntity) {
            try {
                $this->format = ExportFormat::from($this->Request->query->getAlpha('format'));
            } catch (ValueError $e) {
                throw new ImproperActionException('Incorrect format value.');
            }
            $this->Request->query->set('type', $this->Model->type);
            $this->Request->query->set('id', $this->id);
        }
        if ($this->Request->getContent()) {
            try {
                // SET REQBODY
                $this->reqBody = json_decode((string) $this->Request->getContent(), true, 512, JSON_THROW_ON_ERROR);
                // SET ACTION
                $this->action = Action::tryFrom((string) $this->reqBody['action']) ?? $this->action;
            } catch (JsonException $e) {
                throw new ImproperActionException('Error decoding json payload.');
            } catch (ValueError $e) {
                throw new ImproperActionException('Incorrect action value.');
            }
        }
    }

    private function handlePost(): Response
    {
        $id = $this->Model->postAction($this->action, $this->reqBody);
        return new Response('', Response::HTTP_CREATED, array('Location' => sprintf('%s/%s%d', SITE_URL, $this->Model->getViewPage(), $id)));
    }

    private function getArray(): array
    {
        if ($this->id !== null) {
            return $this->Model->readOne();
        }
        return $this->Model->readAll();
    }

    private function handleGet(): Response
    {
        return match ($this->format) {
            ExportFormat::Csv,
            ExportFormat::Eln,
            ExportFormat::QrPdf,
            ExportFormat::Pdf,
            ExportFormat::PdfA,
            ExportFormat::Zip => (new MakeController($this->Users, $this->Request))->getResponse(),
            default => new JsonResponse($this->getArray(), Response::HTTP_OK),
        };
    }

    private function handlePatch(): array
    {
        // Create is the default action but there are no create patch actions, they are POST
        if ($this->action !== Action::Create) {
            return $this->Model->patchAction($this->action);
        }
        return $this->Model->patch($this->reqBody);
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
            case 'users':
                return new Users($this->id, null, $this->Users);
            default:
                throw new ImproperActionException('Invalid endpoint.');
        }
    }
}
