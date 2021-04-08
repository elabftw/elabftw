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
use Elabftw\Interfaces\ModelInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\Metadata;
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
use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class to process a request
 */
abstract class Processor
{
    public ?AbstractEntity $Entity = null;

    public string $content = '';

    public ?string $target;

    protected string $action;

    protected ?int $id = null;

    protected ModelInterface $Model;

    protected array $extra;

    private Users $Users;

    public function __construct(Users $users, Request $request)
    {
        $this->Users = $users;
        $this->process($request);
    }

    public function getModel(): ModelInterface
    {
        return $this->Model;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    // @phpstan-ignore-next-line
    public function getParams()
    {
        switch ($this->action) {
            case 'create':
                return $this->getCreateParams();
            case 'update':
                return $this->getUpdateParams();
            // no parameters needed for these actions
            case 'destroy':
            case 'duplicate':
            case 'lock':
                return;
            default:
                throw new IllegalActionException('Bad params');
        }
    }

    abstract protected function process(Request $request): void;

    // @phpstan-ignore-next-line
    abstract protected function getCreateParams();

    // @phpstan-ignore-next-line
    abstract protected function getUpdateParams();

    // a target is like a subpart of a model
    // example: update the comment of an upload
    protected function setTarget(string $target): ?string
    {
        if (empty($target)) {
            return null;
        }
        $allowed = array(
            'body',
            'comment',
            'date',
            'file',
            'finished',
            'real_name',
            'title',
        );
        if (!in_array($target, $allowed, true)) {
            throw new IllegalActionException('Invalid target!');
        }
        return $target;
    }

    protected function setAction(string $action): string
    {
        $allowed = array(
            'create',
            'read',
            'update',
            'destroy',
            'deduplicate',
            'duplicate',
            'lock',
            'unreference',
        );
        if (!in_array($action, $allowed, true)) {
            throw new IllegalActionException('Invalid action!');
        }
        return $action;
    }

    protected function getEntity(string $type, ?int $itemId = null): AbstractEntity
    {
        if ($type === 'experiment') {
            return new Experiments($this->Users, $itemId);
        } elseif ($type === 'template') {
            return new Templates($this->Users, $itemId);
        } elseif ($type === 'itemtype') {
            return new ItemsTypes($this->Users->team, $itemId);
        }
        return new Items($this->Users, $itemId);
    }

    //private function getModel(): ModelInterface
    // @phpstan-ignore-next-line
    protected function findModel(string $model)
    {
        switch ($model) {
            case 'apikey':
                return new ApiKeys($this->Users);
            case 'status':
                return new Status($this->Users->team);
            case 'comment':
                return new Comments($this->Entity, new Email(new Config(), $this->Users), $this->id);
            case 'link':
                return new Links($this->Entity, $this->id);
            case 'step':
                return new Steps($this->Entity, $this->id);
            case 'upload':
                return new Uploads($this->Entity, $this->id);
            case 'metadata':
                return new Metadata($this->Entity);
            case 'privacyPolicy':
                return new PrivacyPolicy(new Config());
            case 'teamgroup':
                return new TeamGroups($this->Users);
            case 'tag':
                return new Tags($this->Entity, $this->id);
            case 'experiment':
            case 'item':
            case 'template':
            case 'itemtype':
                return $this->Entity;
            case 'todolist':
                return new Todolist((int) $this->Users->userData['userid']);
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
