<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Controllers;

use Elabftw\Enums\Action;
use Elabftw\Enums\ApiEndpoint;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\ExportFormat;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsLinks;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ExtraFieldsKeys;
use Elabftw\Models\FavTags;
use Elabftw\Models\Idps;
use Elabftw\Models\Info;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsLinks;
use Elabftw\Models\ItemsStatus;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Models\Notifications\UserNotifications;
use Elabftw\Models\ProcurementRequests;
use Elabftw\Models\RequestActions;
use Elabftw\Models\Revisions;
use Elabftw\Models\Scheduler;
use Elabftw\Models\SigKeys;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Models\Todolist;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Uploads;
use Elabftw\Models\UserRequestActions;
use Elabftw\Models\Users;
use Exception;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;

/**
 * For API V2 requests
 */
class Apiv2Controller extends AbstractApiController
{
    private array $allowedMethods = array('GET', 'POST', 'DELETE', 'PATCH');

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
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($e->getPrevious() !== null) {
                $message .= ' ' . $e->getPrevious()->getMessage();
            }
            $error = array(
                'code' => 500,
                'message' => 'Unexpected error',
                'description' => $message,
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
        if ($this->Request->query->has('format')) {
            try {
                $this->format = ExportFormat::from($this->Request->query->getAlpha('format'));
            } catch (ValueError) {
                throw new ImproperActionException('Incorrect format value.');
            }
            // fit the request with what makecontroller expects
            if ($this->Model instanceof AbstractEntity) {
                $this->Request->query->set('type', $this->Model->type);
                $this->Request->query->set('id', $this->id);
            }
        }
        if ($this->Request->getContent()) {
            try {
                // SET REQBODY
                $this->reqBody = json_decode($this->Request->getContent(), true, 512, JSON_THROW_ON_ERROR);
                // SET ACTION
                $this->action = Action::tryFrom((string) ($this->reqBody['action'] ?? '')) ?? $this->action;
            } catch (JsonException $e) {
                throw new ImproperActionException(sprintf('Error decoding json payload: %s', $e->getMessage()));
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
            $this->reqBody['comment'] = $this->Request->request->get('comment');
        }
        $id = $this->Model->postAction($this->action, $this->reqBody);
        return new Response('', Response::HTTP_CREATED, array('Location' => sprintf('%s/%s%d', Config::fromEnv('SITE_URL'), $this->Model->getPage(), $id)));
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
            ExportFormat::Binary => (
                function () {
                    if ($this->Model instanceof Uploads) {
                        return $this->Model->readBinary();
                    }
                    throw new ImproperActionException('Incorrect format (binary): only available for uploads endpoint.');
                }
            )(),
            ExportFormat::Csv,
            ExportFormat::Eln,
            ExportFormat::QrPdf,
            ExportFormat::QrPng,
            ExportFormat::Pdf,
            ExportFormat::PdfA,
            ExportFormat::ZipA,
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
        return match ($this->endpoint) {
            ApiEndpoint::ApiKeys => new ApiKeys($this->Users, $this->id),
            ApiEndpoint::Config => Config::getConfig(),
            ApiEndpoint::Idps => new Idps($this->id),
            ApiEndpoint::Info => new Info(),
            ApiEndpoint::Experiments,
            ApiEndpoint::Items,
            ApiEndpoint::ExperimentsTemplates,
            ApiEndpoint::ItemsTypes => EntityType::from($this->endpoint->value)->toInstance($this->Users, $this->id),
            // for a single event, the id is the id of the event
            ApiEndpoint::Event => new Scheduler(new Items($this->Users), $this->id),
            // otherwise it's the id of the item
            ApiEndpoint::Events => new Scheduler(
                new Items($this->Users, $this->id),
                null,
                $this->Request->query->getString('start', Scheduler::EVENT_START),
                $this->Request->query->getString('end', Scheduler::EVENT_END),
                $this->Request->query->getInt('cat'),
            ),
            ApiEndpoint::ExtraFieldsKeys => new ExtraFieldsKeys(
                $this->Users,
                trim($this->Request->query->getString('q')),
                $this->Request->query->getInt('limit'),
            ),
            ApiEndpoint::FavTags => new FavTags($this->Users, $this->id),
            ApiEndpoint::SigKeys => new SigKeys($this->Users, $this->id),
            ApiEndpoint::TeamTags => new TeamTags($this->Users, $this->id),
            ApiEndpoint::Teams => new Teams($this->Users, $this->id),
            ApiEndpoint::Todolist => new Todolist($this->Users->userData['userid'], $this->id),
            ApiEndpoint::UnfinishedSteps => new UnfinishedSteps(
                $this->Users,
                $this->Request->query->get('scope') === 'team',
            ),
            ApiEndpoint::Users => new Users($this->id, $this->Users->team, $this->Users),
        };
    }

    private function getSubModel(string $submodel): RestInterface
    {
        if ($this->Model instanceof AbstractEntity) {
            $Config = Config::getConfig();
            return match ($submodel) {
                'comments' => new Comments($this->Model, $this->subId),
                'experiments_links' => new ExperimentsLinks($this->Model, $this->subId),
                'items_links' => new ItemsLinks($this->Model, $this->subId),
                'request_actions' => new RequestActions($this->Users, $this->Model, $this->subId),
                'revisions' => new Revisions(
                    $this->Model,
                    (int) $Config->configArr['max_revisions'],
                    (int) $Config->configArr['min_delta_revisions'],
                    (int) $Config->configArr['min_days_revisions'],
                    $this->subId
                ),
                'steps' => new Steps($this->Model, $this->subId),
                'tags' => new Tags($this->Model, $this->subId),
                'uploads' => new Uploads($this->Model, $this->subId),
                default => throw new ImproperActionException('Incorrect submodel for ' . $this->Model->page . ': available models are: comments, experiments_links, items_links, request_actions, revisions, steps, tags, uploads.'),
            };
        }
        if ($this->Model instanceof Teams) {
            return match ($submodel) {
                // backward compatibility: status == experiments_status
                'status' => new ExperimentsStatus($this->Model, $this->subId),
                'experiments_status' => new ExperimentsStatus($this->Model, $this->subId),
                'experiments_categories' => new ExperimentsCategories($this->Model, $this->subId),
                'items_status' => new ItemsStatus($this->Model, $this->subId),
                'items_categories' => new ItemsTypes($this->Users, $this->subId),
                'procurement_requests' => new ProcurementRequests($this->Model, $this->subId),
                'teamgroups' => new TeamGroups($this->Users, $this->subId),
                default => throw new ImproperActionException('Incorrect submodel for teams: available models are: experiments_status, experiments_categories, items_status, items_categories, teamgroups.'),
            };
        }
        if ($this->Model instanceof Users) {
            return match ($submodel) {
                'notifications' => new UserNotifications($this->Model, $this->subId),
                'request_actions' => new UserRequestActions($this->Model),
                default => throw new ImproperActionException('Incorrect submodel for users: available models are: notifications.'),
            };
        }
        if ($this->Model instanceof Scheduler) {
            return match ($submodel) {
                'notifications' => new EventDeleted($this->Model->readOne(), $this->Users->userData['fullname']),
                default => throw new ImproperActionException('Incorrect submodel for event: available models are: notifications.'),
            };
        }
        throw new ImproperActionException('Incorrect endpoint.');
    }

    private function applyRestrictions(): void
    {
        if (($this->Model instanceof Config || $this->Model instanceof Idps) && $this->Users->userData['is_sysadmin'] !== 1) {
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
