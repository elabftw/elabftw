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
use Elabftw\Enums\ApiSubModels;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\ExportFormat;
use Elabftw\Enums\Storage;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidApiSubModelException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Factories\LinksFactory;
use Elabftw\Import\Handler as ImportHandler;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Make\ReportsHandler;
use Elabftw\Make\Exports;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Batch;
use Elabftw\Models\Comments;
use Elabftw\Models\Compounds;
use Elabftw\Models\Config;
use Elabftw\Models\ExperimentsCategories;
use Elabftw\Models\ExperimentsStatus;
use Elabftw\Models\ExtraFieldsKeys;
use Elabftw\Models\FavTags;
use Elabftw\Models\Idps;
use Elabftw\Models\IdpsSources;
use Elabftw\Models\Info;
use Elabftw\Models\Items;
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
use Elabftw\Models\StorageUnits;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\TeamTags;
use Elabftw\Models\Todolist;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Uploads;
use Elabftw\Models\UserRequestActions;
use Elabftw\Models\Users;
use Elabftw\Models\UserUploads;
use Elabftw\Services\Fingerprinter;
use Elabftw\Services\HttpGetter;
use Elabftw\Services\NullFingerprinter;
use Exception;
use GuzzleHttp\Client;
use JsonException;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ValueError;
use Override;

/**
 * For API V2 requests
 */
final class Apiv2Controller extends AbstractApiController
{
    private array $allowedMethods = array('GET', 'POST', 'DELETE', 'PATCH');

    private RestInterface $Model;

    private ?int $subId = null;

    private bool $hasSubmodel = false;

    private array $reqBody = array();

    private ExportFormat $format = ExportFormat::Json;

    private Action $action = Action::Create;

    #[Override]
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
    #[Override]
    protected function parseReq(): array
    {
        $req = parent::parseReq();
        // load Model
        $this->Model = $this->getModel();
        // load submodel
        if (!empty($req[5])) {
            $subId = (int) ($req[6] ?? '');
            $this->subId = $subId > 0 ? $subId : null;
            $this->Model = $this->getSubModel(ApiSubModels::tryFrom((string) $req[5]));
            $this->hasSubmodel = true;
        }

        // FORMAT
        if ($this->Request->query->has('format')) {
            try {
                $this->format = ExportFormat::from($this->Request->query->getAlpha('format'));
            } catch (ValueError) {
                throw new ImproperActionException(
                    sprintf('Incorrect value for format parameter. Available values are: %s.', ExportFormat::toCsList())
                );
            }
            // fit the request with what makecontroller expects
            if ($this->Model instanceof AbstractEntity) {
                $this->Request->query->set('type', $this->Model->entityType->value);
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
        if (($this->Model instanceof Uploads || $this->Model instanceof ImportHandler) && $this->action === Action::Create) {
            $file = $this->Request->files->get('file');
            // this was added to prevent: Uncaught Error: Call to a member function getClientOriginalName() on null
            // not sure what triggers it though
            if ($file === null) {
                throw new ImproperActionException('Error reading file!');
            }
            $this->reqBody['real_name'] = $file->getClientOriginalName();
            $this->reqBody['file'] = $file;
            $this->reqBody['target'] = $this->Request->request->getString('target');
            $this->reqBody['filePath'] = $file->getPathname();
            $this->reqBody['comment'] = $this->Request->request->get('comment');
            $this->reqBody['entity_type'] = $this->Request->request->get('entity_type'); // can be null
            $this->reqBody['category'] = $this->Request->request->get('category'); // can be null
            $this->reqBody['owner'] = $this->Request->request->getInt('owner');
            $this->reqBody['canread'] = (BasePermissions::tryFrom($this->Request->request->getInt('canread')) ?? BasePermissions::Team)->toJson();
            $this->reqBody['canwrite'] = (BasePermissions::tryFrom($this->Request->request->getInt('canwrite')) ?? BasePermissions::User)->toJson();
        }
        $id = $this->Model->postAction($this->action, $this->reqBody);
        return new Response('', Response::HTTP_CREATED, array('Location' => sprintf('%s/%s%d', Config::fromEnv('SITE_URL'), $this->Model->getApiPath(), $id)));
    }

    private function getArray(): array
    {
        if (($this->id !== null && !$this->hasSubmodel) || ($this->subId !== null && $this->hasSubmodel)) {
            return $this->Model->readOne();
        }
        $queryParams = $this->Model->getQueryParams($this->Request->query);
        return $this->Model->readAll($queryParams);
    }

    private function handleGet(): Response
    {
        return match ($this->format) {
            ExportFormat::Binary => (
                function () {
                    if ($this->Model instanceof Uploads || $this->Model instanceof Exports) {
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
            ExportFormat::Zip => (new MakeController($this->requester, $this->Request))->getResponse(),
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
            ApiEndpoint::ApiKeys => new ApiKeys($this->requester, $this->id),
            ApiEndpoint::Batch => new Batch($this->requester),
            ApiEndpoint::Compounds => (
                function () {
                    $Config = Config::getConfig();
                    $Fingerprinter = new NullFingerprinter();
                    if (Config::boolFromEnv('USE_FINGERPRINTER')) {
                        $httpGetter = new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0');
                        $Fingerprinter = new Fingerprinter($httpGetter, Config::fromEnv('FINGERPRINTER_URL'));
                    }
                    return new Compounds(
                        new HttpGetter(new Client(), $Config->configArr['proxy'], $Config->configArr['debug'] === '0'),
                        $this->requester,
                        $Fingerprinter,
                        $this->id,
                    );
                }
            )(),
            ApiEndpoint::Config => Config::getConfig(),
            ApiEndpoint::Idps => new Idps($this->requester, $this->id),
            ApiEndpoint::IdpsSources => new IdpsSources($this->requester, $this->id),
            ApiEndpoint::Import => new ImportHandler($this->requester, new Logger('elabftw')),
            ApiEndpoint::Info => new Info(),
            ApiEndpoint::Export => new Exports($this->requester, Storage::EXPORTS->getStorage(), $this->id),
            ApiEndpoint::Experiments,
            ApiEndpoint::Items,
            ApiEndpoint::ExperimentsTemplates,
            ApiEndpoint::ItemsTypes => EntityType::from($this->endpoint->value)->toInstance($this->requester, $this->id),
            // for a single event, the id is the id of the event
            ApiEndpoint::Event => new Scheduler(new Items($this->requester), $this->id),
            // otherwise it's the id of the item
            ApiEndpoint::Events => new Scheduler(
                new Items($this->requester, $this->id),
                null,
                $this->Request->query->getString('start', Scheduler::EVENT_START),
                $this->Request->query->getString('end', Scheduler::EVENT_END),
                $this->Request->query->getInt('cat'),
            ),
            ApiEndpoint::ExtraFieldsKeys => new ExtraFieldsKeys(
                $this->requester,
                trim($this->Request->query->getString('q')),
                $this->Request->query->getInt('limit'),
            ),
            ApiEndpoint::FavTags => new FavTags($this->requester, $this->id),
            ApiEndpoint::Reports => new ReportsHandler($this->requester),
            ApiEndpoint::StorageUnits => new StorageUnits($this->requester, $this->id),
            // Temporary informational endpoint, can be removed in 5.2
            ApiEndpoint::TeamTags => throw new ImproperActionException('Use api/v2/teams/current/tags endpoint instead.'),
            ApiEndpoint::Teams => new Teams($this->requester, $this->id),
            ApiEndpoint::Todolist => new Todolist($this->requester->userData['userid'], $this->id),
            ApiEndpoint::UnfinishedSteps => new UnfinishedSteps(
                $this->requester,
                $this->Request->query->get('scope') === 'team',
            ),
            ApiEndpoint::Users => new Users($this->id, $this->requester->team, $this->requester),
        };
    }

    private function getSubModel(?ApiSubModels $submodel): RestInterface
    {
        if ($this->Model instanceof AbstractEntity) {
            $Config = Config::getConfig();
            return match ($submodel) {
                ApiSubModels::Comments => new Comments($this->Model, $this->subId),
                ApiSubModels::Containers => LinksFactory::getContainersLinks($this->Model, $this->subId),
                ApiSubModels::ExperimentsLinks => LinksFactory::getExperimentsLinks($this->Model, $this->subId),
                ApiSubModels::Compounds => LinksFactory::getCompoundsLinks($this->Model, $this->subId),
                ApiSubModels::ItemsLinks => LinksFactory::getItemsLinks($this->Model, $this->subId),
                ApiSubModels::RequestActions => new RequestActions($this->requester, $this->Model, $this->subId),
                ApiSubModels::Revisions => new Revisions(
                    $this->Model,
                    (int) $Config->configArr['max_revisions'],
                    (int) $Config->configArr['min_delta_revisions'],
                    (int) $Config->configArr['min_days_revisions'],
                    $this->subId
                ),
                ApiSubModels::Steps => new Steps($this->Model, $this->subId),
                ApiSubModels::Tags => new Tags($this->Model, $this->subId),
                ApiSubModels::Uploads => new Uploads($this->Model, $this->subId, includeArchived: $this->Request->query->getBoolean('archived')),
                default => throw new InvalidApiSubModelException(ApiEndpoint::from($this->Model->entityType->value)),
            };
        }
        if ($this->Model instanceof Teams) {
            return match ($submodel) {
                // backward compatibility: Status == ExperimentsStatus
                ApiSubModels::Status, ApiSubModels::ExperimentsStatus => new ExperimentsStatus($this->Model, $this->subId),
                ApiSubModels::ExperimentsCategories => new ExperimentsCategories($this->Model, $this->subId),
                ApiSubModels::ItemsStatus => new ItemsStatus($this->Model, $this->subId),
                ApiSubModels::ItemsCategories => new ItemsTypes($this->requester, $this->subId),
                ApiSubModels::ProcurementRequests => new ProcurementRequests($this->Model, $this->subId),
                ApiSubModels::Tags => new TeamTags($this->requester, $this->subId),
                ApiSubModels::Teamgroups => new TeamGroups($this->requester, $this->subId),
                default => throw new InvalidApiSubModelException(ApiEndpoint::Teams),
            };
        }
        if ($this->Model instanceof Users) {
            return match ($submodel) {
                ApiSubModels::Notifications => new UserNotifications($this->Model, $this->subId),
                ApiSubModels::RequestActions => new UserRequestActions($this->Model),
                ApiSubModels::SigKeys => new SigKeys($this->requester, $this->subId),
                // the uploads users/X/uploads endpoint forces the use of the requester
                ApiSubModels::Uploads => new UserUploads($this->requester, $this->subId),
                default => throw new InvalidApiSubModelException(ApiEndpoint::Users),
            };
        }
        if ($this->Model instanceof Scheduler) {
            return match ($submodel) {
                ApiSubModels::Notifications => new EventDeleted($this->Model->readOne(), $this->requester->userData['fullname']),
                default => throw new InvalidApiSubModelException(ApiEndpoint::Event),
            };
        }
        throw new ImproperActionException('Incorrect endpoint.');
    }

    private function applyRestrictions(): void
    {
        if (($this->Model instanceof Config) && $this->requester->userData['is_sysadmin'] !== 1) {
            throw new IllegalActionException('Non sysadmin user tried to use a restricted api endpoint.');
        }

        // allow multipart/form-data for the POST/uploads and POST/import endpoints only,
        // use str_starts_with because the actual header will also contain the boundary
        if (str_starts_with($this->Request->headers->get('content-type') ?? '', 'multipart/form-data') &&
            ($this->Model instanceof Uploads || $this->Model instanceof ImportHandler) &&
            $this->Request->getMethod() === Request::METHOD_POST) {
            return;
        }

        // only accept json content-type unless it's GET (also prevents csrf!)
        if ($this->Request->getMethod() !== Request::METHOD_GET && $this->Request->headers->get('content-type') !== 'application/json') {
            throw new ImproperActionException('Incorrect content-type header.');
        }
    }
}
