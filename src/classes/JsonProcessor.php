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
use Elabftw\Interfaces\CreateParamsInterface;
use Elabftw\Interfaces\ProcessorInterface;
use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use function in_array;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process a JSON payload defined by the Payload interface in typescript
 */
class JsonProcessor extends Processor implements ProcessorInterface
{
    public string $method;

    public ?string $target;

    public string $content = '';

    private array $decoded;

    private array $extra;

    public function __construct(Users $users, Request $request)
    {
        parent::__construct($users, $request);
    }

    //public function getParams(): CreateParamsInterface | UpdateParamsInterface
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

    // process a Payload json request
    protected function process(Request $request): void
    {
        $this->decoded = $request->toArray();
        $this->action = $this->setAction();
        $this->target = $this->getTarget();

        if (isset($this->decoded['entity'])) {
            $id = (int) $this->decoded['entity']['id'];
            if ($id === 0) {
                $id = null;
            }
            $this->Entity = $this->getEntity($this->decoded['entity']['type'], $id);
        }
        $this->id = $this->getId();
        $this->Model = $this->findModel($this->decoded['model'] ?? '');
        $this->content = $this->getContent();
        $this->extra = $this->decoded['extraParams'] ?? array();
    }

    //private function getCreateParams(): CreateParamsInterface
    // @phpstan-ignore-next-line
    private function getCreateParams()
    {
        if ($this->Model instanceof ApiKeys) {
            return new CreateApikey($this->content, (int) $this->extra['canwrite']);
        }
        if ($this->Model instanceof Comments) {
            return new CreateComment($this->content);
        }
        if ($this->Model instanceof Experiments || $this->Model instanceof Database) {
            return new CreateEntity((int) $this->id);
        }
        if ($this->Model instanceof ItemsTypes) {
            return new CreateItemType(
                $this->content,
                $this->extra['color'],
                $this->extra['body'],
                $this->extra['canread'],
                $this->extra['canwrite'],
                (int) $this->extra['bookable'],
            );
        }
        if ($this->Model instanceof Links) {
            return new CreateLink($this->id);
        }
        if ($this->Model instanceof Status) {
            return new CreateStatus($this->content, $this->extra['color'], (bool) $this->extra['isTimestampable']);
        }
        if ($this->Model instanceof Steps) {
            return new CreateStep($this->content);
        }
        if ($this->Model instanceof Tags) {
            return new CreateTag($this->content);
        }
        if ($this->Model instanceof Templates) {
            return new CreateTemplate($this->content, $this->extra['body'] ?? '');
        }
        if ($this->Model instanceof Todolist) {
            return new CreateTodoitem($this->content);
        }
        if ($this->Model instanceof Uploads) {
            return new CreateUpload(Request::createFromGlobals());
        }
        throw new IllegalActionException('Bad params');
    }

    //private function getUpdateParams(): UpdateParams
    // @phpstan-ignore-next-line
    private function getUpdateParams()
    {
        if ($this->Model instanceof Comments) {
            return new UpdateComment($this->content);
        }
        if ($this->Model instanceof Steps) {
            return new UpdateStep($this->target, $this->content);
        }
        if ($this->Model instanceof ItemsTypes) {
            return new UpdateItemType(
                $this->content,
                $this->extra['color'],
                $this->extra['body'],
                $this->extra['canread'],
                $this->extra['canwrite'],
                (int) $this->extra['bookable'],
            );
        }
        if ($this->Model instanceof Experiments || $this->Model instanceof Database) {
            return new UpdateEntity($this->target, $this->content);
        }

        if ($this->Model instanceof Status) {
            return new UpdateStatus($this->content, $this->extra['color'], (bool) $this->extra['isTimestampable'], (bool) $this->extra['isDefault']);
        }
        if ($this->Model instanceof Todolist) {
            return new UpdateTodoitem($this->content);
        }
        if ($this->Model instanceof Uploads) {
            if ($this->target === 'real_name') {
                return new UpdateUploadRealName($this->content);
            }
            if ($this->target === 'comment') {
                return new UpdateUploadComment($this->content);
            }
        }

        throw new IllegalActionException('Bad params');
    }

    private function setAction(): string
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
        if (!in_array($this->decoded['action'], $allowed, true)) {
            throw new IllegalActionException('Invalid action!');
        }
        return $this->decoded['action'];
    }

    // a target is like a subpart of a model
    // example: update the comment of an upload
    private function getTarget(): ?string
    {
        if (!isset($this->decoded['target'])) {
            return null;
        }
        $allowed = array(
            'body',
            'date',
            'comment',
            'finished',
            'real_name',
            'title',
        );
        if (!in_array($this->decoded['target'], $allowed, true)) {
            throw new IllegalActionException('Invalid target!');
        }
        return $this->decoded['target'];
    }

    private function getContent(): string
    {
        if (!isset($this->decoded['content'])) {
            return $this->content;
        }
        return $this->decoded['content'];
    }

    private function getId(): ?int
    {
        if (!isset($this->decoded['id']) || $this->decoded['id'] === 0) {
            return null;
        }
        $id = Check::id((int) $this->decoded['id']);
        if ($id === false) {
            throw new IllegalActionException('Bad id');
        }
        return $id;
    }
}
