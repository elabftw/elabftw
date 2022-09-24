<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Controllers;

use Elabftw\Enums\Action;
use Elabftw\Enums\ExportFormat;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\EntityFactory;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\AbstractConcreteEntity;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\ExperimentsLinks;
use Elabftw\Models\FavTags;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsLinks;
use Elabftw\Models\Notifications;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Models\Todolist;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
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

    private ?int $subId = null;

    private bool $hasSubmodel = false;

    private array $reqBody = array();

    private ExportFormat $format = ExportFormat::Json;

    private Action $action = Action::Create;

    public function getResponse(): Response
    {
        try {
            $this->parseReq();

            $this->applyRestrictions();

            return match ($this->Request->getMethod()) {
                Request::METHOD_GET => $this->handleGet(),
                Request::METHOD_POST => $this->handlePost(),
                Request::METHOD_DELETE => new JsonResponse($this->Model->destroy(), Response::HTTP_NO_CONTENT),
                Request::METHOD_PATCH => new JsonResponse($this->handlePatch()),
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
    protected function parseReq(): array
    {
        $req = parent::parseReq();
        // load Model
        $this->Model = $this->getModel();
        // load submodel
        if (!empty($req[5])) {
            $subId = (int) ($req[6] ?? '');
            $this->subId = $subId > 0 ? $subId : null;
            $this->Model = $this->getSubModel($req[5]);
            $this->hasSubmodel = true;
        }

        // FORMAT
        if ($this->Request->query->has('format') && $this->Model instanceof AbstractConcreteEntity) {
            try {
                $this->format = ExportFormat::from($this->Request->query->getAlpha('format'));
            } catch (ValueError) {
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
                $this->action = Action::tryFrom((string) ($this->reqBody['action'] ?? '')) ?? $this->action;
            } catch (JsonException) {
                throw new ImproperActionException('Error decoding json payload.');
            } catch (ValueError) {
                throw new ImproperActionException('Incorrect action value.');
            }
        }
        return $req;
    }

    private function handlePost(): Response
    {
        // special case for POST/uploads where we get the information from the "files" attribute
        if ($this->Model instanceof Uploads && $this->action === Action::Create) {
            $this->reqBody['real_name'] = $this->Request->files->get('file')->getClientOriginalName();
            $this->reqBody['filePath'] = $this->Request->files->get('file')->getPathname();
        }
        $id = $this->Model->postAction($this->action, $this->reqBody);
        return new Response('', Response::HTTP_CREATED, array('Location' => sprintf('%s/%s%d', SITE_URL, $this->Model->getPage(), $id)));
    }

    private function getArray(): array
    {
        if (($this->id !== null && !$this->hasSubmodel) || ($this->subId !== null && $this->hasSubmodel)) {
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
            default => new JsonResponse($this->getArray()),
        };
    }

    private function handlePatch(): array
    {
        // Create is the default action but there are no create patch actions, they are POST
        if ($this->action === Action::Create) {
            // set default action for patch
            $this->action = Action::Update;
        }
        return $this->Model->patch($this->action, $this->reqBody);
    }

    private function getModel(): RestInterface
    {
        switch ($this->endpoint) {
            case 'apikeys':
                return new ApiKeys($this->Users, $this->id);
            case 'config':
                return Config::getConfig();
            case 'experiments':
            case 'items':
            case 'experiments_templates':
            case 'items_types':
                return (new EntityFactory($this->Users, $this->endpoint, $this->id))->getEntity();
                // for a single event, the id is the id of the event
            case 'event':
                return new Scheduler(new Items($this->Users), $this->id);
                // otherwise it's the id of the item
            case 'events':
                $defaultStart = '2018-12-23T00:00:00+01:00';
                $defaultEnd = '2119-12-23T00:00:00+01:00';
                return new Scheduler(
                    new Items($this->Users, $this->id),
                    null,
                    (string) $this->Request->query->get('start', $defaultStart),
                    (string) $this->Request->query->get('end', $defaultEnd),
                );
            case 'favtags':
                return new FavTags($this->Users, $this->id);
            case 'team_tags':
                return new TeamTags($this->Users, $this->id);
            case 'teams':
                return new Teams($this->Users, $this->id);
            case 'todolist':
                return new Todolist($this->Users->userData['userid'], $this->id);
            case 'unfinished_steps':
                return new UnfinishedSteps($this->Users, $this->Request->query->get('scope') === 'team');
            case 'users':
                return new Users($this->id, null, $this->Users);
            default:
                throw new ImproperActionException('Invalid endpoint: available endpoints: apikeys, config, experiments, items, experiments_templates, items_types, event, events, team_tags, teams, todolist, unfinished_steps, users.');
        }
    }

    private function getSubModel(string $submodel): RestInterface
    {
        if ($this->Model instanceof AbstractEntity) {
            return match ($submodel) {
                'comments' => new Comments($this->Model, $this->subId),
                'experiments_links' => new ExperimentsLinks($this->Model, $this->subId),
                'items_links' => new ItemsLinks($this->Model, $this->subId),
                'steps' => new Steps($this->Model, $this->subId),
                'tags' => new Tags($this->Model, $this->subId),
                'uploads' => new Uploads($this->Model, $this->subId),
                default => throw new ImproperActionException('Incorrect submodel for ' . $this->Model->page . ': available models are: comments, links, steps, tags, uploads.'),
            };
        }
        if ($this->Model instanceof Teams) {
            return match ($submodel) {
                'status' => new Status($this->Model, $this->subId),
                'teamgroups' => new TeamGroups($this->Users, $this->subId),
                default => throw new ImproperActionException('Incorrect submodel for teams: available models are: status, teamgroups.'),
            };
        }
        if ($this->Model instanceof Users) {
            return match ($submodel) {
                'notifications' => new Notifications($this->Users, $this->subId),
                default => throw new ImproperActionException('Incorrect submodel for users: available models are: notifications.'),
            };
        }
        throw new ImproperActionException('Incorrect endpoint.');
    }

    private function applyRestrictions(): void
    {
        if ($this->Model instanceof Config && $this->Users->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('Non sysadmin user tried to use a restricted api endpoint.');
        }
        // allow multipart/form-data for the POST/uploads endpoint only, use str_starts_with because the actual header will also contain the boundary
        if (str_starts_with($this->Request->headers->get('content-type') ?? '', 'multipart/form-data') &&
            $this->Model instanceof Uploads &&
            $this->Request->getMethod() === Request::METHOD_POST) {
            return;
        }
        // only accept json content-type unless it's GET (also prevents csrf!)
        if ($this->Request->getMethod() !== Request::METHOD_GET && $this->Request->headers->get('content-type') !== 'application/json') {
            throw new ImproperActionException('Incorrect content-type header.');
        }
    }
}
