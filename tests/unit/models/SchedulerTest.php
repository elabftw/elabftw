<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Enums\BasePermissions;
use Elabftw\Enums\Scope;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

use function array_column;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function json_encode;
use function sprintf;
use function usort;

class SchedulerTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    private Scheduler $Scheduler;

    private array $delta;

    private string $start;

    private string $end;

    protected function setUp(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $d = new DateTimeImmutable('+3 hour');
        $this->start = $d->format('c');
        $this->end = $d->add(new DateInterval('PT2H'))->format('c');
        $this->Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        $this->delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '0',
        );
    }

    public function testGetApiPath(): void
    {
        $this->assertEquals('api/v2/event/', $this->Scheduler->getApiPath());
    }

    public function testInstanciationWithExperiments(): void
    {
        $this->expectException(ImproperActionException::class);
        new Scheduler(new Experiments(new Users()));
    }

    public function testPostActionWithoutId(): void
    {
        $Scheduler = new Scheduler(new Items($this->getRandomUserInTeam(2)));
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array());
    }

    public function testPostActionCannotBook(): void
    {
        $RestrictedBookableItem = $this->getFreshBookableItem(2);
        $RestrictedBookableItem->update(new EntityParams('canread_base', BasePermissions::Full->value));
        $RestrictedBookableItem->update(new EntityParams('canbook_base', BasePermissions::UserOnly->value));
        $Scheduler = new Scheduler(new Items($this->getRandomUserInTeam(1), $RestrictedBookableItem->id));
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array());
    }

    public function testPostAction(): int
    {
        $id = $this->Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end, 'title' => 'Yep'));
        $this->assertIsInt($id);
        return $id;
    }

    public function testCreateDailyRecurringSeries(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $start = new DateTimeImmutable('+2 days 10:00');
        $id = $Scheduler->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->add(new DateInterval('PT2H'))->format('c'),
            'title' => 'Daily series',
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));

        $events = $this->getSortedEvents($Items);
        $this->assertCount(3, $events);
        $this->assertEquals($id, $events[0]['id']);
        $this->assertCount(1, array_unique(array_column($events, 'recurrence_series_id')));
        $this->assertSame(array(1, 2, 3), array_map('intval', array_column($events, 'recurrence_index')));
        $this->assertSame(array(
            $start->format('Y-m-d H:i:s'),
            $start->modify('+1 day')->format('Y-m-d H:i:s'),
            $start->modify('+2 days')->format('Y-m-d H:i:s'),
        ), array_column($events, 'start'));
        foreach ($events as $event) {
            $this->assertSame(120, (new DateTimeImmutable($event['start']))->diff(new DateTimeImmutable($event['end']))->h * 60);
        }
    }

    public function testCreateWeeklySeriesPreservesLocalTimeAcrossDst(): void
    {
        $previousTimezone = date_default_timezone_get();
        date_default_timezone_set('America/New_York');
        try {
            $year = (new DateTimeImmutable('+1 year'))->format('Y');
            $transition = new DateTimeImmutable(sprintf('second sunday of March %s 10:00', $year));
            $start = $transition->modify('-1 week');
            $Items = $this->getFreshBookableItem(2);
            $Scheduler = new Scheduler($Items);
            $Scheduler->postAction(Action::Create, array(
                'start' => $start->format('c'),
                'end' => $start->modify('+90 minutes')->format('c'),
                'recurrence' => array('frequency' => 'weekly', 'interval' => 1, 'count' => 3),
            ));
            $events = $this->getSortedEvents($Items);
        } finally {
            date_default_timezone_set($previousTimezone);
        }
        $this->assertSame(array('10:00:00', '10:00:00', '10:00:00'), array_map(
            static fn (array $event): string => (new DateTimeImmutable($event['start']))->format('H:i:s'),
            $events,
        ));
        $this->assertSame(array(
            $start->format('Y-m-d'),
            $transition->format('Y-m-d'),
            $transition->modify('+1 week')->format('Y-m-d'),
        ), array_map(
            static fn (array $event): string => (new DateTimeImmutable($event['start']))->format('Y-m-d'),
            $events,
        ));
    }

    public function testCreateMonthlySeries(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $year = (new DateTimeImmutable('first day of January next year'))->format('Y');
        $Scheduler->postAction(Action::Create, array(
            'start' => sprintf('%s-01-15T09:00:00-05:00', $year),
            'end' => sprintf('%s-01-15T10:00:00-05:00', $year),
            'recurrence' => array('frequency' => 'monthly', 'interval' => 1, 'count' => 3),
        ));

        $this->assertSame(
            array(sprintf('%s-01-15', $year), sprintf('%s-02-15', $year), sprintf('%s-03-15', $year)),
            array_map(
                static fn (array $event): string => (new DateTimeImmutable($event['start']))->format('Y-m-d'),
                $this->getSortedEvents($Items),
            ),
        );
    }

    public function testInvalidRecurrencesAreRejected(): void
    {
        $invalid = array(
            'daily',
            array('frequency' => 'hourly', 'interval' => 1, 'count' => 2),
            array('frequency' => 'daily', 'interval' => 0, 'count' => 2),
            array('frequency' => 'daily', 'interval' => -1, 'count' => 2),
            array('frequency' => 'daily', 'interval' => 1, 'count' => 0),
            array('frequency' => 'daily', 'interval' => 1, 'count' => Scheduler::MAX_RECURRENCE_OCCURRENCES + 1),
            array('frequency' => 'daily', 'interval' => 365, 'count' => 12),
        );
        $rejected = 0;
        foreach ($invalid as $recurrence) {
            try {
                $this->Scheduler->postAction(Action::Create, array(
                    'start' => $this->start,
                    'end' => $this->end,
                    'recurrence' => $recurrence,
                ));
            } catch (ImproperActionException) {
                $rejected++;
            }
        }
        $this->assertSame(count($invalid), $rejected);
        $this->assertEmpty($this->Scheduler->readOne());
    }

    public function testInvalidMonthlyDayIsRejected(): void
    {
        $year = (new DateTimeImmutable('first day of January next year'))->format('Y');
        $this->expectException(ImproperActionException::class);
        $this->Scheduler->postAction(Action::Create, array(
            'start' => sprintf('%s-01-31T09:00:00-05:00', $year),
            'end' => sprintf('%s-01-31T10:00:00-05:00', $year),
            'recurrence' => array('frequency' => 'monthly', 'interval' => 1, 'count' => 2),
        ));
    }

    public function testRecurringSeriesObeysMaximumSlots(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_max_slots' => 2));
        $Scheduler = new Scheduler($Items);
        try {
            $Scheduler->postAction(Action::Create, array(
                'start' => $this->start,
                'end' => $this->end,
                'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
            ));
            $this->fail('The series should exceed the resource maximum slot count.');
        } catch (ImproperActionException) {
            $this->assertEmpty((new Scheduler($Items))->readOne());
        }
    }

    public function testUnauthorizedUserCannotCreateRecurringSeries(): void
    {
        $RestrictedBookableItem = $this->getFreshBookableItem(2);
        $RestrictedBookableItem->update(new EntityParams('canread_base', BasePermissions::Full->value));
        $RestrictedBookableItem->update(new EntityParams('canbook_base', BasePermissions::UserOnly->value));
        $Scheduler = new Scheduler(new Items($this->getRandomUserInTeam(1), $RestrictedBookableItem->id));
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array(
            'start' => $this->start,
            'end' => $this->end,
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));
    }

    public function testRecurringConflictRollsBackCompleteSeries(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_can_overlap' => 0));
        $Scheduler = new Scheduler($Items);
        $start = new DateTimeImmutable('+2 days 10:00');
        $conflictStart = $start->modify('+2 weeks');
        $Scheduler->postAction(Action::Create, array(
            'start' => $conflictStart->format('c'),
            'end' => $conflictStart->add(new DateInterval('PT2H'))->format('c'),
        ));

        try {
            $Scheduler->postAction(Action::Create, array(
                'start' => $start->format('c'),
                'end' => $start->add(new DateInterval('PT2H'))->format('c'),
                'recurrence' => array('frequency' => 'weekly', 'interval' => 1, 'count' => 4),
            ));
            $this->fail('The conflicting series should have been rejected.');
        } catch (ImproperActionException $e) {
            $this->assertStringContainsString($conflictStart->format('Y-m-d'), $e->getMessage());
        }
        $this->assertCount(1, (new Scheduler($Items))->readOne());
    }

    public function testUpdateAndDeleteRecurringSeries(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $start = new DateTimeImmutable('+3 days 10:00');
        $id = $Scheduler->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->add(new DateInterval('PT1H'))->format('c'),
            'title' => 'Before',
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));
        $Scheduler->setId($id);
        $Scheduler->patch(Action::Update, array(
            'target' => 'datetime',
            'scope' => 'series',
            'start' => $start->modify('+1 hour')->format('c'),
            'end' => $start->modify('+3 hours')->format('c'),
            'title' => 'After',
        ));

        $events = $this->getSortedEvents($Items);
        $this->assertCount(3, $events);
        foreach ($events as $event) {
            $this->assertSame('After', $event['title_only']);
            $this->assertSame('11:00:00', (new DateTimeImmutable($event['start']))->format('H:i:s'));
            $this->assertSame('13:00:00', (new DateTimeImmutable($event['end']))->format('H:i:s'));
        }

        $unrelatedId = (new Scheduler($Items))->postAction(Action::Create, array(
            'start' => $start->modify('+20 days')->format('c'),
            'end' => $start->modify('+20 days +1 hour')->format('c'),
            'title' => 'Unrelated',
        ));

        $SeriesScheduler = new Scheduler($Items, $id, recurringEvents: true);
        $this->assertTrue($SeriesScheduler->destroy());
        $remaining = (new Scheduler($Items))->readOne();
        $this->assertCount(1, $remaining);
        $this->assertEquals($unrelatedId, $remaining[0]['id']);
    }

    public function testUpdateSingleOccurrenceAndDeleteSingleOccurrence(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $start = new DateTimeImmutable('+4 days 10:00');
        $id = $Scheduler->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->add(new DateInterval('PT1H'))->format('c'),
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));

        $Scheduler->setId($id);
        $Scheduler->patch(Action::Update, array(
            'target' => 'datetime',
            'scope' => 'event',
            'start' => $start->modify('+1 hour')->format('c'),
            'end' => $start->modify('+2 hours')->format('c'),
        ));
        $events = $this->getSortedEvents($Items);
        $this->assertSame(array('11:00:00', '10:00:00', '10:00:00'), array_map(
            static fn (array $event): string => (new DateTimeImmutable($event['start']))->format('H:i:s'),
            $events,
        ));

        $this->assertTrue($Scheduler->destroy());
        $remaining = $this->getSortedEvents($Items);
        $this->assertCount(2, $remaining);
        $this->assertSame(array(2, 3), array_map('intval', array_column($remaining, 'recurrence_index')));
    }

    public function testConflictingSeriesUpdateRollsBackCompletely(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_can_overlap' => 0));
        $Scheduler = new Scheduler($Items);
        $start = new DateTimeImmutable('+5 days 10:00');
        $id = $Scheduler->postAction(Action::Create, array(
            'start' => $start->format('c'),
            'end' => $start->add(new DateInterval('PT1H'))->format('c'),
            'recurrence' => array('frequency' => 'daily', 'interval' => 1, 'count' => 3),
        ));
        $blockerStart = $start->modify('+1 day +2 hours');
        $Scheduler->postAction(Action::Create, array(
            'start' => $blockerStart->format('c'),
            'end' => $blockerStart->add(new DateInterval('PT1H'))->format('c'),
        ));
        $Scheduler->setId($id);

        try {
            $Scheduler->patch(Action::Update, array(
                'target' => 'datetime',
                'scope' => 'series',
                'start' => $start->modify('+2 hours')->format('c'),
                'end' => $start->modify('+3 hours')->format('c'),
            ));
            $this->fail('The conflicting series update should have been rejected.');
        } catch (ImproperActionException) {
            $series = array_filter(
                $this->getSortedEvents($Items),
                static fn (array $event): bool => $event['recurrence_series_id'] !== null,
            );
            $this->assertSame(array('10:00:00', '10:00:00', '10:00:00'), array_values(array_map(
                static fn (array $event): string => (new DateTimeImmutable($event['start']))->format('H:i:s'),
                $series,
            )));
        }
    }

    public function testRepeatedOverlappingBookingsCreateOnlyOneEvent(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_can_overlap' => 0));
        $Scheduler = new Scheduler($Items);
        $start = new DateTimeImmutable('+1 hour');
        $end = $start->add(new DateInterval('PT2H'));

        $created = 0;
        $rejected = 0;
        for ($i = 0; $i < 5; $i++) {
            try {
                $Scheduler->postAction(Action::Create, array(
                    'start' => $start->format('c'),
                    'end' => $end->format('c'),
                    'title' => sprintf('Booking %d', $i),
                ));
                $created++;
            } catch (ImproperActionException) {
                $rejected++;
            }
        }

        $this->assertSame(1, $created);
        $this->assertSame(4, $rejected);
    }

    public function testPostActionWithNegativeTimeSlots(): void
    {
        $end = new DateTimeImmutable('-1 hour')->format('c');
        $this->expectException(UnprocessableContentException::class);
        $this->Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $end, 'title' => 'Yep'));
    }

    public function testFailure(): void
    {
        $Items = $this->getFreshItem();
        $Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array('start' => '', 'end' => ''));
    }

    public function testReadFromAnItem(): void
    {
        $this->assertIsArray($this->Scheduler->readAll());
        $Items = $this->getFreshBookableItem(1);
        $this->Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        $this->Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end));
        $this->assertIsArray($this->Scheduler->readOne());
        $this->assertNotEmpty($this->Scheduler->readOne());
    }

    public function testReadAllWithVariousScopes(): void
    {
        foreach (array(Scope::User->value, Scope::Team->value, Scope::Everything->value) as $scope) {
            $Users = $this->getUserInTeam(2, admin: 1);
            $Users->userData['scope_events'] = $scope;
            $Items = $this->getFreshItemWithGivenUser($Users);
            $Items->patch(Action::Update, array('is_bookable' => 1));
            $Scheduler = new Scheduler($Items, null, $this->start, $this->end);
            $Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end));
            $this->assertReadAllReturnsValidEvents($Scheduler, $scope);
        }
    }

    public function testEventVisibilityByTeamAccess(): void
    {
        $Owner = $this->getUserInTeam(1);
        $Items = $this->getFreshItemWithGivenUser($Owner);

        // User 2 can read but cannot book
        $User2 = $this->getUserInTeam(2);

        // grant user 2 'canread' permissions only. Prevents 'access entity without permission'
        $Items->patch(Action::Update, array(
            'is_bookable' => 1,
            'canread_base' => BasePermissions::User->value,
            'canread' => json_encode(array(
                'users' => array($User2->userid),
                'teams' => array(),
                'teamgroups' => array(),
            )),
        ));

        $title = 'Bookable only by user 1';
        // add event to scheduler by user in team 1
        $Scheduler1 = new Scheduler($Items);
        $eventId = $Scheduler1->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end, 'title' => $title));

        // Sets scope->Everything and tries to see user 1's booking
        $User2->userData['scope_events'] = Scope::Everything->value;
        $Items2 = new Items($User2, $Items->id);
        $Scheduler2 = new Scheduler($Items2, null, $this->start, $this->end);

        $events = $Scheduler2->readAll();
        $this->assertIsArray($events);
        $eventsIds = array_column($events, 'id');
        $this->assertContains($eventId, $eventsIds, 'User 2 should see the event, but it remains non-bookable.');
        foreach ($events as $event) {
            if ($event['id'] === $eventId) {
                $this->assertSame(0, (int) $event['canbook'], 'User 2 should not be able to book the event.');
            }
        }
        // Ensure User 2 can not perform any action on visible(non-bookable) event.
        $Scheduler2->setId($eventId);
        $this->expectException(ForbiddenException::class);
        $Scheduler2->patch(Action::Update, array('target' => 'end', 'delta' => $this->delta));
    }

    public function testReadAllWithFilters(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $categoryId = $Items->entityData['category'];
        $Scheduler = new Scheduler($Items);

        $title = 'The filtered event';
        $Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end, 'title' => $title));

        // Filtering by item id
        $q = $this->Scheduler->getQueryParams(new InputBag(array('items' => array($Items->id))));
        $allEvents = $this->Scheduler->readAll();
        $filteredEvent = $this->Scheduler->readAll($q);

        $this->assertNotEmpty($allEvents);
        $this->assertCount(1, $filteredEvent);
        $this->assertEquals($title, $filteredEvent[0]['title_only']);
        $this->assertEquals($Items->id, $filteredEvent[0]['items_id'], 'Item ID should match the filtered item');

        // Filtering by category
        $titleItem2 = sprintf('New Item in category %d', $categoryId);
        $Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end, 'title' => $titleItem2));

        $qCat = $this->Scheduler->getQueryParams(new InputBag(array('category' => $categoryId)));
        $filteredCatEvents = $this->Scheduler->readAll($qCat);
        // two events in given category now
        $this->assertCount(2, $filteredCatEvents);
        $this->assertEquals($title, $filteredCatEvents[0]['title_only']);
        $this->assertEquals($titleItem2, $filteredCatEvents[1]['title_only']);
    }

    public function testPatch(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $newStart = new DateTimeImmutable('+6 hour');
        $newEnd = $newStart->add(new DateInterval('PT2H'));
        $newTitle = 'Afternoon experiment for Toto.';
        $res = $Scheduler->patch(Action::Update, array('target' => 'datetime', 'start' => $newStart->format('c'), 'end' => $newEnd->format('c'), 'title' => $newTitle));
        $this->assertIsArray($res);
        $this->assertEquals($newStart->format('Y-m-d H:i:s'), $res['start']);
        $this->assertEquals($newEnd->format('Y-m-d H:i:s'), $res['end']);
        $this->assertEquals($newTitle, $res['title']);
    }

    public function testPatchDatetimeEndBeforeStart(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $start = new DateTimeImmutable('+10 hour');
        $end = new DateTimeImmutable('+6 hour');
        $this->expectException(UnprocessableContentException::class);
        $Scheduler->patch(Action::Update, array('target' => 'datetime', 'start' => $start->format('c'), 'end' => $end->format('c')));
    }

    public function testPatchDatetimeInvalidFormat(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'datetime', 'start' => '', 'end' => ''));
    }

    public function testPatchInvalidTarget(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'banana'));
    }

    public function testPatchNothingToUpdate(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $res = $Scheduler->patch(Action::Update, array('target' => 'datetime'));
        $this->assertIsArray($res);
    }

    public function testPatchDatetimeMissingStartOrEnd(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array(
            'target' => 'datetime',
            'start' => new DateTimeImmutable('+1 hour')->format('c'),
            // missing end
        ));
    }

    public function testDestroyNonCancellableEvent(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_is_cancellable' => 0));
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('tomorrow');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        $Scheduler->setId($id);
        $this->expectException(ImproperActionException::class);
        $Scheduler->destroy();
    }

    public function testCancelTooClose(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_cancel_minutes' => 666));
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('5 minutes');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        $Scheduler->setId($id);
        $this->expectException(ImproperActionException::class);
        $Scheduler->destroy();
    }

    public function testSlotTime(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_max_minutes' => 12));
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('5 minutes');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
    }

    public function testOverlap(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_can_overlap' => 0));
        $Scheduler = new Scheduler($Items);
        // first one
        $d = new DateTime('5 minutes');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        // second one
        $d = new DateTime('15 minutes');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
    }

    public function testOverlapWhileChangingExisting(): void
    {
        $Items = $this->getFreshItemWithGivenUser($this->getRandomUserInTeam(2));
        $Items->patch(Action::Update, array('book_can_overlap' => 0, 'is_bookable' => 1));
        $Scheduler = new Scheduler($Items);
        // first one
        $d = new DateTime('5 minutes');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        // second one
        $d = new DateTime('3 hours');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        $Scheduler->setId($id);
        $this->expectException(ImproperActionException::class);
        $newStart = new DateTimeImmutable('+10 minutes');
        $newEnd = $newStart->add(new DateInterval('PT2H'));
        $Scheduler->patch(Action::Update, array('target' => 'datetime', 'start' => $newStart->format('c'), 'end' => $newEnd->format('c')));
    }

    public function testCheckMaxSlots(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Items->patch(Action::Update, array('book_max_slots' => 2));
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('5 minutes');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        $this->expectException(ImproperActionException::class);
        $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
    }

    public function testBind(): void
    {
        $this->Scheduler->setId($this->testPostAction());
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => 3)));
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'item_link', 'id' => 3)));
    }

    public function testBindIncorrect(): void
    {
        $this->Scheduler->setId($this->testPostAction());
        $this->expectException(DatabaseErrorException::class);
        $this->Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => -12));
    }

    public function testUnbind(): void
    {
        $this->Scheduler->setId($this->testPostAction());
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'experiment', 'id' => null)));
        $this->assertIsArray($this->Scheduler->patch(Action::Update, array('target' => 'item_link', 'id' => null)));
    }

    public function testReadOneDoesNotLeakPrivateBoundEntityTitles(): void
    {
        $Owner = $this->getUserInTeam(1);
        $User2 = $this->getUserInTeam(2);

        $BookableItem = $this->getFreshItemWithGivenUser($Owner);
        $BookableItem->patch(Action::Update, array(
            'is_bookable' => 1,
            'canread_base' => BasePermissions::User->value,
            'canread' => json_encode(array(
                'users' => array($User2->userid),
                'teams' => array(),
                'teamgroups' => array(),
            )),
        ));

        $PrivateExperiment = $this->getFreshExperimentWithGivenUser($Owner);
        $PrivateExperiment->patch(Action::Update, array(
            'canread_base' => BasePermissions::UserOnly->value,
            'canwrite_base' => BasePermissions::UserOnly->value,
        ));

        $OwnerScheduler = new Scheduler($BookableItem);
        $eventId = $OwnerScheduler->postAction(Action::Create, array(
            'start' => $this->start,
            'end' => $this->end,
        ));

        $OwnerScheduler->setId($eventId);
        $OwnerScheduler->patch(Action::Update, array(
            'target' => 'experiment',
            'id' => $PrivateExperiment->id,
        ));

        $User2Scheduler = new Scheduler(new Items($User2, $BookableItem->id));
        $User2Scheduler->setId($eventId);

        $event = $User2Scheduler->readOne();
        // the experiment's ID is also not shown
        $this->assertEquals(0, (int) $event['experiment']);
        $this->assertNull($event['experiment_title']);
    }

    public function testCanWriteAndWeAreAdmin(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items, null, $this->start, $this->end);
        // create with user, make sure it's in the future!
        $d = new DateTime('now');
        $d->add(new DateInterval('PT2H'));
        $start = $d->format('c');
        $d->add(new DateInterval('PT4H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'Yep'));
        // write with admin
        $Admin = $this->getUserInTeam(2, admin: 1);
        $Scheduler = new Scheduler(new Items($Admin, $Items->id));
        $Scheduler->setId($id);
        $this->assertTrue($Scheduler->destroy());
    }

    public function testCanNotWrite(): void
    {
        $Admin = $this->getUserInTeam(2, admin: 1);
        $Items = $this->getFreshItemWithGivenUser($Admin);
        $Items->patch(Action::Update, array('is_bookable' => 1));
        $AdminScheduler = new Scheduler($Items);
        $adminEventId = $AdminScheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end));
        $User = $this->getUserInTeam(2);
        $UserScheduler = new Scheduler(new Items($User, $Items->id));
        $UserScheduler->setId($adminEventId);
        // try write event created by admin as user
        $this->expectException(ForbiddenException::class);
        $UserScheduler->patch(Action::Update, array('target' => 'experiment', 'id' => 3));
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->getFreshSchedulerWithEvent()->destroy());
    }

    public function testCanCancelDuringGracePeriod(): void
    {
        $Items = $this->getFreshBookableItem(2);
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('+1 hour');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'test grace period'));
        $Scheduler->setId($id);
        $this->assertTrue($Scheduler->destroy());
    }

    public function testCannotBookBeyondMaximumAdvanceDays(): void
    {
        $Items = $this->getFreshBookableItem(2);
        // enable limit
        $Items->patch(Action::Update, array('booking_window_days' => 1));
        $start = new DateTime('+3 days')->format('c');
        $end = new DateTime('+3 days +2 hours')->format('c');
        $this->expectException(ImproperActionException::class);
        $this->getFreshSchedulerWithEvent($Items, $start, $end);
    }

    private function getFreshSchedulerWithEvent(?Items $Items = null, ?string $start = null, ?string $end = null): Scheduler
    {
        $Items ??= $this->getFreshBookableItem(2);
        $start ??= $this->start;
        $end ??= $this->end;
        $Scheduler = new Scheduler($Items);
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end));
        $Scheduler->setId($id);
        return $Scheduler;
    }

    private function getSortedEvents(Items $Items): array
    {
        $events = (new Scheduler($Items))->readOne();
        usort($events, static fn (array $left, array $right): int => $left['start'] <=> $right['start']);
        return $events;
    }

    private function assertReadAllReturnsValidEvents(Scheduler $Scheduler, int $scope): void
    {
        $events = $Scheduler->readAll();
        $this->assertNotEmpty($events, 'Expected events but got none');

        foreach ($events as $event) {
            $this->assertArrayHasKey('id', $event);
            $this->assertArrayHasKey('userid', $event);
            $this->assertArrayHasKey('start', $event);
            $this->assertArrayHasKey('end', $event);
        }

        $this->assertReadAllByScope($events, $scope, $Scheduler->Items->Users->userid, $Scheduler->Items->Users->team);
    }

    private function assertReadAllByScope(array $events, int $scope, int $userid, int $team): void
    {
        foreach ($events as $event) {
            switch ($scope) {
                case Scope::User->value:
                    $this->assertEquals($userid, $event['userid'], 'Event does not belong to user');
                    break;

                case Scope::Team->value:
                    $this->assertEquals($team, $event['team'], 'Event is not from team');
                    break;

                case Scope::Everything->value:
                    $this->assertTrue(
                        $event['userid'] === $userid || $event['team'] === $team,
                        'Event does not match user or team!'
                    );
                    break;

                default:
                    $this->fail("Unknown scope value: $scope");
            }
        }
    }
}
