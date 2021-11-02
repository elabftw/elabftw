<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2021 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\ApiKeys;
use Elabftw\Models\Comments;
use Elabftw\Models\Config;
use Elabftw\Models\Experiments;
use Elabftw\Models\FavTags;
use Elabftw\Models\Items;
use Elabftw\Models\ItemsTypes;
use Elabftw\Models\Links;
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
use Elabftw\Services\Email;

class ParamsBuilderTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
    }

    public function testGetParams(): void
    {
        // ContentParams
        $Experiments = new Experiments($this->Users, 1);
        $builder = new ParamsBuilder(new Comments($Experiments, new Email(Config::getConfig(), $this->Users)));
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        $builder = new ParamsBuilder(Config::getConfig());
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        $builder = new ParamsBuilder(new Todolist(1));
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        $builder = new ParamsBuilder(new Links($Experiments));
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        $builder = new ParamsBuilder(new FavTags($this->Users));
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        $builder = new ParamsBuilder($this->Users);
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        $builder = new ParamsBuilder(new Teams($this->Users));
        $this->assertInstanceOf(ContentParams::class, $builder->getParams());
        // EntityParams
        $builder = new ParamsBuilder($Experiments);
        $this->assertInstanceOf(EntityParams::class, $builder->getParams());
        $builder = new ParamsBuilder(new Items($this->Users));
        $this->assertInstanceOf(EntityParams::class, $builder->getParams());
        $builder = new ParamsBuilder(new Templates($this->Users));
        $this->assertInstanceOf(EntityParams::class, $builder->getParams());
        // ItemTypeParams
        $builder = new ParamsBuilder(new ItemsTypes($this->Users));
        $this->assertInstanceOf(ItemTypeParams::class, $builder->getParams());
        // UnfinishedSteps
        $builder = new ParamsBuilder(new UnfinishedSteps($Experiments));
        $this->assertInstanceOf(UnfinishedStepsParams::class, $builder->getParams());
        // Steps
        $builder = new ParamsBuilder(new Steps($Experiments));
        $this->assertInstanceOf(StepParams::class, $builder->getParams());
        // Status
        $builder = new ParamsBuilder(new Status(1), 'status-name', '', array(
            'color' => '00FF00',
            'isTimestampable' => '1',
            'isDefault' => '0',
        ));
        $this->assertInstanceOf(StatusParams::class, $builder->getParams());
        // ApiKeys
        $builder = new ParamsBuilder(new ApiKeys($this->Users), 'apikey-name', '', array('canwrite' => '1'));
        $this->assertInstanceOf(CreateApikey::class, $builder->getParams());
        // Tags
        $builder = new ParamsBuilder(new Tags($Experiments));
        $this->assertInstanceOf(TagParams::class, $builder->getParams());
        // Uploads
        $builder = new ParamsBuilder(new Uploads($Experiments));
        $this->assertInstanceOf(UploadParams::class, $builder->getParams());
        // TeamGroups
        $builder = new ParamsBuilder(new TeamGroups($this->Users));
        $this->assertInstanceOf(TeamGroupParams::class, $builder->getParams());
    }
}
