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
use Elabftw\Models\Config;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Metadata;
use Elabftw\Models\PrivacyPolicy;
use Elabftw\Models\Status;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Elabftw\Models\Todolist;
use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mother class to process a request
 */
abstract class Processor
{
    public AbstractEntity $Entity;

    //private ModelInterface $Model;
    // @phpstan-ignore-next-line
    protected $Model;

    private Users $Users;

    public function __construct(Users $users, Request $request)
    {
        $this->Users = $users;
        $this->process($request);
    }

    //private function getModel(): ModelInterface
    // @phpstan-ignore-next-line
    public function getModel()
    {
        return $this->Model;
    }

    abstract protected function process(Request $request): void;

    protected function getEntity(string $type, ?int $itemId = null): AbstractEntity
    {
        if ($type === 'experiments') {
            return new Experiments($this->Users, $itemId);
        } elseif ($type === 'experiments_templates') {
            return new Templates($this->Users, $itemId);
        } elseif ($type === 'items_types') {
            return new ItemsTypes($this->Users, $itemId);
        }
        return new Database($this->Users, $itemId);
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
                return $this->Entity->Comments;
            case 'link':
                return $this->Entity->Links;
            case 'step':
                return $this->Entity->Steps;
            case 'upload':
                if (!($this->Entity instanceof Experiments || $this->Entity instanceof Database)) {
                    throw new IllegalActionException('Invalid entity type for upload');
                }
                return $this->Entity->Uploads;
            case 'itemsTypes':
                /*
                // items types is only from admin panel
                if (!$App->Session->get('is_admin')) {
                    throw new IllegalActionException('Non admin user tried to access admin controller.');
                }
                 */
                return new ItemsTypes($this->Users);

            case 'metadata':
                return new Metadata($this->Entity);
            case 'privacyPolicy':
                return new PrivacyPolicy(new Config());
            case 'teamgroup':
                /*
                if (!$App->Session->get('is_admin')) {
                    throw new IllegalActionException('Non admin user tried to access admin controller.');
                }
                 */
                return new TeamGroups($this->Users);
            case 'tag':
                if (!($this->Entity instanceof Experiments || $this->Entity instanceof Database || $this->Entity instanceof Templates)) {
                    throw new IllegalActionException('Invalid entity type for tags');
                }
                return $this->Entity->Tags;
            case 'template':
                return $this->Entity;
            case 'todolist':
                return new Todolist($this->Users);
            case 'user':
                return $this->Users;
            default:
                throw new IllegalActionException('Bad model');
        }
    }
}
