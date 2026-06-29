<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;

use function bin2hex;
use function random_bytes;

class AbstractCategoryTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private ExperimentsStatus $Category;

    protected function setUp(): void
    {
        $this->Category = new ExperimentsStatus(new Teams(new Users(1, 1), 1));
    }

    public function testGetIdempotentIdFromTitleCreatesCategory(): void
    {
        $title = 'Idempotent category ' . bin2hex(random_bytes(8));

        $id = $this->Category->getIdempotentIdFromTitle($title, '#29AEB9');

        $this->assertIsInt($id);

        $Category = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $id);
        $category = $Category->readOne();

        $this->assertEquals($title, $category['title']);
        $this->assertEquals('29AEB9', $category['color']);
    }

    public function testGetIdempotentIdFromTitleReturnsExistingCategory(): void
    {
        $title = 'Existing idempotent category ' . bin2hex(random_bytes(8));

        $firstId = $this->Category->getIdempotentIdFromTitle($title, '#29AEB9');
        $secondId = $this->Category->getIdempotentIdFromTitle($title, '#121212');

        $this->assertSame($firstId, $secondId);

        $Category = new ExperimentsStatus(new Teams(new Users(1, 1), 1), $firstId);
        $category = $Category->readOne();

        $this->assertEquals($title, $category['title']);
        $this->assertEquals('29AEB9', $category['color']);
    }

    public function testNormalUserCannotCreateCategoryIfTeamSettingDisallowsIt(): void
    {
        $user = $this->getUserInTeam(1);
        $Teams = new Teams($user, 1);
        $Teams->teamArr['users_canwrite_experiments_categories'] = 0;

        $Category = new ExperimentsCategories($Teams);

        $this->expectException(IllegalActionException::class);

        $Category->postAction(Action::Create, array(
            'title' => 'Forbidden category',
            'color' => '#29AEB9',
        ));
    }
}
