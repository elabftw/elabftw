<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\CrudInterface;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\FavTags;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
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

/**
 * Build the params object
 */
class ParamsBuilder
{
    public function __construct(
        private CrudInterface | Users | Config $model,
        private ?string $content,
        private ?string $target,
        private ?array $extra,
    ) {
    }

    // @phpstan-ignore-next-line
    public function getParams()
    {
        if ($this->model instanceof Comments ||
            $this->model instanceof Config ||
            $this->model instanceof Todolist ||
            $this->model instanceof Links ||
            $this->model instanceof FavTags ||
            $this->model instanceof Users ||
            $this->model instanceof Teams ||
            $this->model instanceof PrivacyPolicy) {
            return new ContentParams($this->content, $this->target);
        }
        if ($this->model instanceof Experiments || $this->model instanceof Items || $this->model instanceof Templates) {
            return new EntityParams($this->content, $this->target, $this->extra);
        }
        if ($this->model instanceof ItemsTypes) {
            return new ItemTypeParams($this->content, $this->target, $this->extra);
        }
        if ($this->model instanceof UnfinishedSteps) {
            return new UnfinishedStepsParams($this->extra);
        }
        if ($this->model instanceof Steps) {
            return new StepParams($this->content, $this->target);
        }
        if ($this->model instanceof Status) {
            return new StatusParams(
                $this->content,
                $this->extra['color'],
                (bool) $this->extra['isTimestampable'],
                (bool) $this->extra['isDefault']
            );
        }
        if ($this->model instanceof ApiKeys) {
            // TODO only giv extra as third param and the get function will extract the correct stuff from it?
            // will help with homogeneisation of Params class
            return new CreateApikey($this->content, $this->target, (int) $this->extra['canwrite']);
        }
        if ($this->model instanceof Tags) {
            return new TagParams($this->content, $this->target);
        }
        if ($this->model instanceof Uploads) {
            return new UploadParams($this->content, $this->target);
        }
        if ($this->model instanceof TeamGroups) {
            return new TeamGroupParams($this->content, $this->target, $this->extra);
        }
    }
}
