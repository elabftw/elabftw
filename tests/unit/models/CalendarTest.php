<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Enums\Action;
use Elabftw\Enums\CalendarKeys;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\IllegalActionException;
use Symfony\Component\HttpFoundation\Request;

use function array_column;
use function array_slice;
use function strlen;

class CalendarTest extends \PHPUnit\Framework\TestCase
{
    private Calendar $Calendar;

    protected function setUp(): void
    {
        $this->Calendar = new Calendar(new Users(1, 1));
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/calendar/', $this->Calendar->getApiPath());
    }

    public function testRandomAlphaNumericString(): void
    {
        $length = 10;
        $this->assertEquals($length, strlen($this->Calendar::randomAlphaNumericString($length)));
    }

    public function testTokenLength(): void
    {
        $this->assertEquals(60, $this->Calendar::TOKEN_LENGTH);
    }

    public function testPostAction(): void
    {
        $fullTeam = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::AllEvents->value => true,
            CalendarKeys::Title->value => 'All events',
        ));
        $this->assertIsInt($fullTeam);

        // Mock a request with canbook parameter
        $Request = Request::create('/database.php', 'GET', array('canbook' => '1'));
        $bookableCat = array((new Items(new Users(1, 1)))->readBookable($Request)[0]['category']);
        $bookableItems = array_slice(
            array_column((new Items(new Users(1, 1)))->readBookable($Request), 'id'),
            0,
            5,
        );

        $category = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::Categories->value => $bookableCat,
            CalendarKeys::Title->value => 'Cat 1',
        ));
        $this->assertIsInt($category);

        $item = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::Items->value => $bookableItems,
            CalendarKeys::Title->value => '5 Items',
        ));
        $this->assertIsInt($item);

        $categoriesAndItems = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::Categories->value => $bookableCat,
            CalendarKeys::Items->value => $bookableItems,
            CalendarKeys::Title->value => '1 category and 5 items',
        ));
        $this->assertIsInt($categoriesAndItems);

        $todo = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::Todo->value => true,
            CalendarKeys::Title->value => 'Todolist',
        ));
        $this->assertIsInt($todo);

        $user_steps = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::UnfinishedStepsScope->value => Scope::User->toString(),
            CalendarKeys::Title->value => 'Unfinished steps of user',
        ));
        $this->assertIsInt($user_steps);

        $team_steps = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::UnfinishedStepsScope->value => Scope::Team->toString(),
            CalendarKeys::Title->value => 'Unfinished steps of team',

        ));
        $this->assertIsInt($team_steps);

        $all = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::AllEvents->value => true,
            CalendarKeys::Todo->value => true,
            CalendarKeys::UnfinishedStepsScope->value => Scope::Team->toString(),
            CalendarKeys::Title->value => 'Events and todos',
        ));
        $this->assertIsInt($all);
    }

    public function testReadAll(): void
    {
        $this->assertIsArray($this->Calendar->readAll());
    }

    public function testReadOne(): void
    {
        $this->Calendar->setId(10);
        $this->assertIsArray($this->Calendar->readOne());

        $this->Calendar->id = null;
        $this->expectException(IllegalActionException::class);
        $this->assertIsArray($this->Calendar->readOne());
    }

    public function testDestroy(): void
    {
        $newId = $this->Calendar->postAction(Action::Create, array(
            CalendarKeys::AllEvents->value => true,
        ));
        $this->Calendar->setId($newId);
        $this->assertTrue($this->Calendar->destroy());
    }
}
