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
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process a JSON payload defined by the Payload interface in typescript
 */
class JsonProcessor extends Processor implements ProcessorInterface
{
    private array $decoded;

    public function __construct(Users $users, Request $request)
    {
        parent::__construct($users, $request);
    }

    // process a Payload json request
    protected function process(Request $request): void
    {
        $this->decoded = $request->toArray();
        $this->action = $this->decoded['action'] ?? '';
        $this->target = $this->setTarget($this->decoded['target'] ?? '');

        if (isset($this->decoded['entity'])) {
            $id = (int) $this->decoded['entity']['id'];
            if ($id === 0) {
                $id = null;
            }
            $this->Entity = $this->getEntity($this->decoded['entity']['type'], $id);
        }
        $this->id = $this->setId((int) $this->decoded['id']);
        $this->Model = $this->findModel($this->decoded['model'] ?? '');
        $this->content = $this->decoded['content'] ?? '';
        $this->extra = $this->decoded['extraParams'] ?? array();
    }

    //private function getCreateParams(): CreateParamsInterface
    // @phpstan-ignore-next-line
    protected function getCreateParams()
    {
        if ($this->Model instanceof ApiKeys) {
            return new CreateApikey($this->content, (int) $this->extra['canwrite']);
        }
        if ($this->Model instanceof Comments) {
            return new ContentParams($this->content);
        }
        if ($this->Model instanceof Experiments || $this->Model instanceof Items) {
            return new IdParams((int) $this->id);
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
            return new IdParams($this->id);
        }
        if ($this->Model instanceof Status) {
            return new CreateStatus($this->content, $this->extra['color'], (bool) $this->extra['isTimestampable']);
        }
        if ($this->Model instanceof Steps) {
            return new CreateStep($this->content);
        }
        if ($this->Model instanceof Tags) {
            return new TagParams($this->content);
        }
        if ($this->Model instanceof Templates) {
            return new CreateTemplate($this->content, $this->extra['body'] ?? '');
        }
        if ($this->Model instanceof Todolist) {
            return new ContentParams($this->content);
        }
        if ($this->Model instanceof Uploads) {
            return new CreateUpload(Request::createFromGlobals());
        }
        throw new IllegalActionException('Bad params');
    }

    //private function getUpdateParams(): UpdateParams
    // @phpstan-ignore-next-line
    protected function getUpdateParams()
    {
        if ($this->Model instanceof Comments) {
            return new ContentParams($this->content);
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
        if ($this->Model instanceof Experiments || $this->Model instanceof Items) {
            return new UpdateEntity($this->target, $this->content);
        }

        if ($this->Model instanceof Status) {
            return new UpdateStatus($this->content, $this->extra['color'], (bool) $this->extra['isTimestampable'], (bool) $this->extra['isDefault']);
        }
        if ($this->Model instanceof Todolist) {
            return new ContentParams($this->content);
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
}
