<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Interfaces\ProcessorInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\PrivacyPolicy;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Services\Email;
use function property_exists;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class to process a request
 */
abstract class AbstractProcessor implements ProcessorInterface
{
    public AbstractEntity $Entity;

    public string $content = '';

    public string $target = '';

    protected string $action;

    protected ?int $id = null;

    protected CrudInterface | Users $Model;

    protected array $extra = array();

    public function __construct(private Users $Users, Request $request)
    {
        $this->process($request);
    }

    public function getModel(): CrudInterface | Users
    {
        return $this->Model;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    // @phpstan-ignore-next-line
    public function getParams()
    {
        if ($this->action === 'create' || $this->action === 'read' || $this->action === 'update') {
            return $this->getParamsObject();
        }
    }

    protected function processJson(string $json): void
    {
        $decoded = json_decode($json);
        $this->action = $decoded->action ?? '';
        $this->setTarget($decoded->target ?? '');

        if (property_exists($decoded, 'entity') && $decoded->entity !== null) {
            $id = (int) $decoded->entity->id;
            if ($id === 0) {
                $id = null;
            }
            $this->Entity = $this->getEntity($decoded->entity->type, $id);
        }
        $this->id = $this->setId((int) ($decoded->id ?? 0));
        $this->Model = $this->buildModel($decoded->model ?? '');
        $this->content = $decoded->content ?? '';
        if (property_exists($decoded, 'extraParams')) {
            $this->extra = (array) $decoded->extraParams;
        }
    }

    abstract protected function process(Request $request): void;

    protected function setTarget(string $target): void
    {
        $this->target = Check::target($target);
    }

    protected function getEntity(string $type, ?int $itemId = null): AbstractEntity
    {
        if ($type === 'experiment') {
            return new Experiments($this->Users, $itemId);
        } elseif ($type === 'template') {
            return new Templates($this->Users, $itemId);
        } elseif ($type === 'itemtype') {
            return new ItemsTypes($this->Users, $itemId);
        }
        return new Items($this->Users, $itemId);
    }

    protected function buildModel(string $model): CrudInterface | Users
    {
        switch ($model) {
            case 'apikey':
                return new ApiKeys($this->Users, $this->id);
            case 'status':
                return new Status($this->Users->team, $this->id);
            case 'comment':
                return new Comments($this->Entity, new Email(Config::getConfig(), $this->Users), $this->id);
            case 'link':
                return new Links($this->Entity, $this->id);
            case 'step':
                return new Steps($this->Entity, $this->id);
            case 'upload':
                return new Uploads($this->Entity, $this->id);
            case 'privacypolicy':
                return new PrivacyPolicy(Config::getConfig());
            case 'teamgroup':
                return new TeamGroups($this->Users, $this->id);
            case 'tag':
                return new Tags($this->Entity, $this->id);
            case 'experiment':
            case 'item':
            case 'template':
            case 'itemtype':
                return $this->Entity;
            case 'todolist':
                return new Todolist((int) $this->Users->userData['userid'], $this->id);
            case 'user':
                return $this->Users;
            default:
                throw new IllegalActionException('Bad model');
        }
    }

    protected function setId(?int $id): ?int
    {
        if (!isset($id) || $id === 0) {
            return null;
        }
        $id = Check::id((int) $id);
        if ($id === false) {
            throw new IllegalActionException('Bad id');
        }
        return $id;
    }

    // @phpstan-ignore-next-line
    private function getParamsObject()
    {
        if ($this->Model instanceof Comments ||
            $this->Model instanceof Todolist ||
            $this->Model instanceof Links ||
            $this->Model instanceof Users ||
            $this->Model instanceof PrivacyPolicy) {
            return new ContentParams($this->content, $this->target);
        }
        if ($this->Model instanceof Experiments || $this->Model instanceof Items || $this->Model instanceof Templates) {
            return new EntityParams($this->content, $this->target, $this->extra);
        }
        if ($this->Model instanceof ItemsTypes) {
            return new ItemTypeParams($this->content, $this->target, $this->extra);
        }
        if ($this->Model instanceof Steps) {
            return new StepParams($this->content, $this->target);
        }
        if ($this->Model instanceof Status) {
            return new StatusParams(
                $this->content,
                $this->extra['color'],
                (bool) $this->extra['isTimestampable'],
                (bool) $this->extra['isDefault']
            );
        }
        if ($this->Model instanceof ApiKeys) {
            // TODO only giv extra as third param and the get function will extract the correct stuff from it?
            // will help with homogeneisation of Params class
            return new CreateApikey($this->content, $this->target, (int) $this->extra['canwrite']);
        }
        if ($this->Model instanceof Tags) {
            return new TagParams($this->content, $this->target);
        }
        if ($this->Model instanceof Uploads) {
            return new UploadParams($this->content, $this->target);
        }
        if ($this->Model instanceof TeamGroups) {
            return new TeamGroupParams($this->content, $this->target, $this->extra);
        }
    }
}
