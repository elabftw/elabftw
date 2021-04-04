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
use Elabftw\Interfaces\ModelInterface;
use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Steps;
use Elabftw\Models\Templates;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use function in_array;
use function json_decode;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process a JSON payload defined by the Payload interface in typescript
 */
class JsonProcessor
{
    public string $method;

    public string $action;

    public ModelInterface $model;

    public AbstractEntity $Entity;

    public ?string $target;

    public string $content = '';

    public int $id = 0;

    private array $decoded;

    private Users $Users;

    public function __construct(Users $users)
    {
        $this->Users = $users;
    }

    public function process(string $payload): self
    {
        // TODO jsonDecoder should have getDecoded that uses json_decode
        // the normal one just returns the request object or something for that method
        // and jsonprocessor is child of abstract processor, and we have multipartprocessor too
        $this->decoded = json_decode($payload, true);
        $this->method = $this->getMethod();
        $this->action = $this->getAction();
        $this->target = $this->getTarget();
        $this->Entity = $this->getEntity();
        $this->model = $this->getModel();
        $this->content = $this->getContent();
        $this->id = $this->getId();
        return $this;
    }

    //public function getParams(): UpdateParamsInterface|CreateParamsInterface
    // @phpstan-ignore-next-line
    public function getParams()
    {
        if ($this->action === 'create') {
            if ($this->model instanceof Steps) {
                return new CreateStep($this->content);
            }
            if ($this->model instanceof Uploads) {
                return new CreateUpload(Request::createFromGlobals());
            }
        }

        if ($this->action === 'update') {
            if ($this->model instanceof Uploads) {
                if ($this->target === 'real_name') {
                    return new UpdateUploadRealName($this);
                }
                if ($this->target === 'comment') {
                    return new UpdateUploadComment($this);
                }
            }
            if ($this->model instanceof Steps) {
                if ($this->target === 'body') {
                    // TODO it's better to have the strict minimum here instead of the whole this
                    // it helps when doing from elsewhere in the code
                    return new UpdateStepBody($this);
                }
                if ($this->target === 'finished') {
                    return new UpdateStepFinished($this->id);
                }
            }
        }


        if ($this->action === 'destroy') {
            return new DestroyParams($this->id);
        }
        throw new IllegalActionException('Bad params');
    }

    // for now only GET or POST, should add PUT and DELETE later on...
    private function getMethod(): string
    {
        if ($this->decoded['method'] === 'POST') {
            return 'POST';
        }
        return 'GET';
    }

    // for now only update
    private function getAction(): string
    {
        $allowed = array(
            'create',
            'update',
            'read',
            'destroy',
        );
        if (!in_array($this->decoded['action'], $allowed, true)) {
            throw new IllegalActionException('Invalid action!');
        }
        return $this->decoded['action'];
    }

    private function getModel(): ModelInterface
    {
        if ($this->decoded['model'] === 'upload') {
            if (!($this->Entity instanceof Experiments || $this->Entity instanceof Database)) {
                throw new IllegalActionException('Invalid entity type for upload');
            }
            return $this->Entity->Uploads;
        }

        if ($this->decoded['model'] === 'step') {
            return $this->Entity->Steps;
        }
        throw new IllegalActionException('Bad model');
    }

    private function getTarget(): ?string
    {
        if (!isset($this->decoded['target'])) {
            return null;
        }
        $allowed = array(
            'body',
            'comment',
            'finished',
            'real_name',
        );
        if (!in_array($this->decoded['target'], $allowed, true)) {
            throw new IllegalActionException('Invalid target!');
        }
        return $this->decoded['target'];
    }

    // figure out which type of entity we have to deal with
    private function getEntity(): AbstractEntity
    {
        if ($this->decoded['entity']['type'] === 'experiments') {
            return new Experiments($this->Users, (int) $this->decoded['entity']['id']);
        } elseif ($this->decoded['entity']['type'] === 'experiments_templates') {
            return new Templates($this->Users, (int) $this->decoded['entity']['id']);
        } elseif ($this->decoded['entity']['type'] === 'items_types') {
            return new ItemsTypes($this->Users, (int) $this->decoded['entity']['id']);
        }
        return new Database($this->Users, (int) $this->decoded['entity']['id']);
    }

    private function getContent(): string
    {
        if (!isset($this->decoded['content'])) {
            return $this->content;
        }
        return Filter::body($this->decoded['content']);
    }

    private function getId(): int
    {
        if (!isset($this->decoded['id'])) {
            return $this->id;
        }
        $id = Check::id((int) $this->decoded['id']);
        if ($id === false) {
            throw new IllegalActionException('Bad id');
        }
        return $id;
    }
}
