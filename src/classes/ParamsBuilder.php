<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Models\ApiKeys;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Status;
use Elabftw\Models\Steps;
use Elabftw\Models\Tags;
use Elabftw\Models\TeamGroups;
use Elabftw\Models\Templates;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Uploads;
use Elabftw\Models\Users;

/**
 * Return the corresponding parameters object based on the model
 */
class ParamsBuilder
{
    public function __construct(
        private CrudInterface | Users | Config $model,
        private string $content = '',
        private string $target = '',
        private array $extra = array(),
    ) {
    }

    public function getParams(): ContentParamsInterface
    {
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
        return new ContentParams($this->content, $this->target);
    }
}
