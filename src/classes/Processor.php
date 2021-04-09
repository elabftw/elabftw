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
use Elabftw\Interfaces\ProcessorInterface;
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
abstract class Processor implements ProcessorInterface
{
    public ?AbstractEntity $Entity = null;

    public string $content = '';

    public string $target = '';

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
            // no parameters needed for these actions
            case 'destroy':
            case 'duplicate':
            case 'deduplicate':
            case 'lock':
                return;
            case 'create':
            case 'update':
                if ($this->Model instanceof Comments || $this->Model instanceof Todolist || $this->Model instanceof Links) {
                    return new ContentParams($this->content, $this->target);
                }
                if ($this->Model instanceof ItemsTypes) {
                    return new ItemTypeParams(
                        $this->content,
                        $this->extra['color'],
                        $this->extra['body'],
                        $this->extra['canread'],
                        $this->extra['canwrite'],
                        (int) $this->extra['bookable'],
                    );
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
                    return new CreateApikey($this->content, $this->target, (int) $this->extra['canwrite']);
                }

                if ($this->Model instanceof Experiments || $this->Model instanceof Items) {
                    return new EntityParams($this->content, $this->target);
                }
                if ($this->Model instanceof Tags) {
                    return new TagParams($this->content);
                }
                if ($this->Model instanceof Templates) {
                    return new CreateTemplate($this->content, $this->extra['body'] ?? '');
                }
                if ($this->Model instanceof Uploads) {
                    return new UploadParams($this->content, $this->target);
                }
                // no break
            default:
                throw new IllegalActionException('Bad params');
        }
    }

    abstract protected function process(Request $request): void;

    protected function setTarget(string $target): string
    {
        return Check::target($target);
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
                return new ApiKeys($this->Users, $this->id);
            case 'status':
                return new Status($this->Users->team, $this->id);
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
