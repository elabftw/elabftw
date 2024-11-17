<?php

declare(strict_types=1);
/**
 * @author Marcel Bolten <github@marcelbolten.de>
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Make;

use Elabftw\Enums\Action;
use Elabftw\Enums\CalendarKeys;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Models\Calendar;
use Elabftw\Models\Items;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Todolist;
use Elabftw\Models\Users;
use DateTime;
use DateInterval;
use Symfony\Component\HttpFoundation\Request;

use function array_column;
use function array_slice;
use function str_repeat;

class MakeICalTest extends \PHPUnit\Framework\TestCase
{
    private Calendar $Calendar;

    private Request $Request;

    private int $calId = 0;

    protected function setUp(): void
    {
        $this->Calendar = new Calendar(new Users(1, 1));
        // Mock a request with canbook parameter
        $this->Request = Request::create('/database.php', 'GET', array('canbook' => '1'));

    }

    public function testGetFileName(): void
    {
        $this->assertEquals(
            'eLabFTW-calendar.ics',
            (new MakeICal('notARealToken'))->getFileName(),
        );
    }

    public function testGetContentType(): void
    {
        $this->assertEquals(
            'text/calendar; charset=utf-8',
            (new MakeICal('notARealToken'))->getContentType(),
        );
    }

    public function testNonExistingToken(): void
    {
        $token = str_repeat('0aA', 20);
        $this->expectException(ResourceNotFoundException::class);
        (new MakeICal($token))->getFileContent();
    }

    public function testAllOfTeam(): void
    {
        $Scheduler = new Scheduler(new Items(new Users(1, 1), 1));
        $title = 'for MakeICal Test';
        $Scheduler->postAction(Action::Create, array(
            'title' => $title,
            'start' => (new DateTime())->format(DateTime::ATOM),
            'end' => (new DateTime())->add(new DateInterval('PT2H'))->format(DateTime::ATOM),
        ));

        $iCalString = $this->getICalString(array(CalendarKeys::AllEvents->value => true));
        $this->assertIsCalendar($iCalString, 'VEVENT');
        $this->assertStringContainsString($title, $iCalString);
    }

    public function testAllOfCategory(): void
    {
        $iCalString = $this->getICalString(array(
            CalendarKeys::Categories->value => array(
                (new Items(new Users(1, 1)))->readBookable($this->Request)[0]['category'],
            ),
        ));
        $this->assertIsCalendar($iCalString, 'VEVENT');
    }

    public function testAllOfItem(): void
    {
        $iCalString = $this->getICalString(array(
            CalendarKeys::Items->value => array_slice(
                array_column((new Items(new Users(1, 1)))->readBookable($this->Request), 'id'),
                0,
                3,
            ),
        ));
        $this->assertIsCalendar($iCalString, 'VEVENT');
    }

    public function testTodolist(): void
    {
        $todoBody = 'My first todo entry';
        $todoid = (new Todolist(1))->postAction(Action::Create, array('content' => $todoBody));
        $iCalString = $this->getICalString(array(CalendarKeys::Todo->value => true));
        $this->assertIsCalendar($iCalString, 'VTODO');
        $this->assertStringContainsString($todoBody, $iCalString);
        (new Todolist(1, $todoid))->destroy();
    }

    public function testTodoUnfinishedStepsUser(): void
    {
        $iCalString = $this->getICalString(array(
            CalendarKeys::UnfinishedStepsScope->value => Scope::User->toString(),
        ));
        $this->assertIsCalendar($iCalString, 'VTODO');
    }

    public function testTodoUnfinishedStepsTeam(): void
    {
        $iCalString = $this->getICalString(array(
            CalendarKeys::UnfinishedStepsScope->value => Scope::Team->toString(),
        ));
        $this->assertIsCalendar($iCalString, 'VTODO');
    }

    public function testCombined(): void
    {
        $iCalString = $this->getICalString(array(
            CalendarKeys::AllEvents->value => true,
            CalendarKeys::Todo->value => true,
            CalendarKeys::UnfinishedStepsScope->value => Scope::Team->toString(),
        ));
        $this->assertIsCalendar($iCalString, 'VTODO');
        $this->assertIsCalendar($iCalString, 'VEVENT');
    }

    private function createCalendar(array $reqBody = array()): void
    {
        $this->calId = $this->Calendar->postAction(Action::Create, $reqBody);
        $this->Calendar->setId($this->calId);
    }

    private function getICalString(array $reqBody = array()): string
    {
        $this->createCalendar($reqBody);
        return (new MakeICal($this->Calendar->readOne()['token']))->getFileContent();
    }

    private function assertIsCalendar(string $iCalString, string $component): void
    {
        $this->assertIsString($iCalString);
        $this->assertStringStartsWith('BEGIN:VCALENDAR', $iCalString);
        $this->assertStringContainsString("BEGIN:$component", $iCalString);
        $this->assertStringContainsString("END:$component", $iCalString);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $iCalString);
    }
}
