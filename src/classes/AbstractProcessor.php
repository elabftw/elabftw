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
use Elabftw\Interfaces\ProcessorInterface;
use Elabftw\Models\AbstractEntity;
use Elabftw\Models\Config;
use Elabftw\Models\FavTags;
use Elabftw\Models\Notifications;
use Elabftw\Models\PrivacyPolicy;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;
use Elabftw\Models\Users2Teams;
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

    // @phpstan-ignore-next-line
    protected $Model;

    protected array $extra = array();

    public function __construct(protected Users $Users, Request $request)
    {
        $this->process($request);
    }

    // @phpstan-ignore-next-line
    public function getModel()
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
        if ($this->action === 'create' || $this->action === 'read' || $this->action === 'update' || $this->action === 'destroy') {
            return new ContentParams($this->target, $this->content);
        }
    }

    abstract protected function process(Request $request): void;

    protected function setTarget(string $target): void
    {
        $this->target = $target;
    }

    // @phpstan-ignore-next-line
    protected function buildModel(string $model)
    {
        switch ($model) {
            case 'favtag':
                return new FavTags($this->Users, $this->id);
            case 'notification':
                return new Notifications($this->Users, $this->id);
            case 'unfinishedsteps':
                return new UnfinishedSteps($this->Entity);
            case 'upload':
                return new Uploads($this->Entity, $this->id);
            case 'privacypolicy':
                return new PrivacyPolicy(Config::getConfig());
            case 'teamgroup':
                return new TeamGroups($this->Users, $this->id);
            case 'tag':
                return new Tags($this->Entity, $this->id);
            case 'user2team':
                return new Users2Teams();
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
