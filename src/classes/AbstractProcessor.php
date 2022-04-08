<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Interfaces\ProcessorInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\FavTags;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\Notifications;
use Elabftw\Models\PrivacyPolicy;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Teams;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
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

    protected CrudInterface | Users | Config $Model;

    protected array $extra = array();

    public function __construct(private Users $Users, Request $request)
    {
        $this->process($request);
    }

    public function getModel(): CrudInterface | Users | Config
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
            $ParamsBuilder = new ParamsBuilder($this->Model, $this->content, $this->target, $this->extra);
            return $ParamsBuilder->getParams();
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

    protected function buildModel(string $model): CrudInterface | Users | Config
    {
        switch ($model) {
            case 'apikey':
                return new ApiKeys($this->Users, $this->id);
            case 'config':
                return Config::getConfig();
            case 'status':
                return new Status($this->Users->team, $this->id);
            case 'comment':
                return new Comments($this->Entity, $this->id);
            case 'link':
                return new Links($this->Entity, $this->id);
            case 'favtag':
                return new FavTags($this->Users, $this->id);
            case 'notification':
                return new Notifications($this->Users, $this->id);
            case 'step':
                return new Steps($this->Entity, $this->id);
            case 'unfinishedsteps':
                return new UnfinishedSteps($this->Entity);
            case 'upload':
                return new Uploads($this->Entity, $this->id);
            case 'privacypolicy':
                return new PrivacyPolicy(Config::getConfig());
            case 'team':
                return new Teams($this->Users, $this->Users->team);
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
}
