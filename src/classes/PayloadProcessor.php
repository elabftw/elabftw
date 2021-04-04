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
use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Models\AbstractCategory;
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

/**
 * Process a JSON payload
 */
class PayloadProcessor
{
    public string $method;

    public string $action;

    public ModelInterface $model;

    public AbstractEntity|AbstractCategory $Entity;

    public string $target;

    public string $content;

    public int $id;

    private array $decoded;

    private Users $Users;

    public function __construct(Users $users)
    {
        $this->Users = $users;
    }

    public function process(string $payload): self
    {
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

    public function getParams(): UpdateParamsInterface
    {
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
                    return new UpdateStepBody($this);
                }
            }
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
        return 'update';
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

    private function getTarget(): string
    {
        $allowedTargets = array(
            'comment',
            'real_name',
            'body',
        );
        if (!in_array($this->decoded['target'], $allowedTargets, true)) {
            throw new IllegalActionException('Invalid target!');
        }
        return $this->decoded['target'];
    }

    // figure out which type of entity we have to deal with
    private function getEntity(): AbstractEntity|AbstractCategory
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
        return Filter::body($this->decoded['content'] ?? '');
    }

    private function getId(): int
    {
        $id = Check::id((int) $this->decoded['id']);
        if ($id === false) {
            throw new IllegalActionException('Bad id');
        }
        return $id;
    }
}
