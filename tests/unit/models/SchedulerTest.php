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
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnprocessableContentException;
use Elabftw\Models\Users\Users;
use Elabftw\Params\EntityParams;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\HttpFoundation\InputBag;

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
        $d->add(new DateInterval('PT2H'));
        $this->end = $d->format('c');
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
        $RestrictedBookableItem->update(new EntityParams('canread', BasePermissions::Full->toJson()));
        $RestrictedBookableItem->update(new EntityParams('canbook', BasePermissions::UserOnly->toJson()));
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
            'canread' => json_encode(array(
                'base' => BasePermissions::User->value,
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
        $this->expectException(IllegalActionException::class);
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

    public function testPatchEpoch(): Scheduler
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $newEpoch = new DateTimeImmutable('+6 hour')->format('U');
        // patch `end` first to avoid a temporary state where start > end.
        $this->assertIsArray($Scheduler->patch(Action::Update, array('target' => 'end_epoch', 'epoch' => $newEpoch)));
        $this->assertIsArray($Scheduler->patch(Action::Update, array('target' => 'start_epoch', 'epoch' => $newEpoch)));
        return $Scheduler;
    }

    public function testPatchEpochEndBeforeStart(): void
    {
        $Scheduler = $this->getFreshSchedulerWithEvent();
        $newEpoch = new DateTimeImmutable('+8 hour')->format('U');
        $this->expectException(UnprocessableContentException::class);
        $Scheduler->patch(Action::Update, array('target' => 'start_epoch', 'epoch' => $newEpoch));
    }

    public function testPatchEpochInvalidTarget(): void
    {
        $Scheduler = $this->testPatchEpoch();
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'oops', 'epoch' => date('U')));
    }

    public function testPatchEpochInvalidEpoch(): void
    {
        $Scheduler = $this->testPatchEpoch();
        $this->expectException(ImproperActionException::class);
        $Scheduler->patch(Action::Update, array('target' => 'end_epoch', 'epoch' => ''));
    }

    public function testPatchTitle(): void
    {
        $res = $this->getFreshSchedulerWithEvent()->patch(Action::Update, array('target' => 'title', 'content' => 'new title'));
        $this->assertEquals('new title', $res['title']);
    }

    public function testDestroyNonCancellableEvent(): void
    {
        $Items = $this->getFreshItem(2);
        $Items->Users = $this->getRandomUserInTeam(2);
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
        $Items = $this->getFreshItem(2);
        $Items->Users = $this->getRandomUserInTeam(2);
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
        $Items = $this->getFreshItem(2);
        $Items->Users = $this->getRandomUserInTeam(2);
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
        $Items = $this->getFreshItem(2);
        $Items->Users = $this->getRandomUserInTeam(2);
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
        $Items = $this->getFreshItem(2);
        $Items->Users = $this->getRandomUserInTeam(2);
        $Items->patch(Action::Update, array('book_can_overlap' => 0));
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
        $Scheduler->patch(Action::Update, array('target' => 'start_epoch', 'epoch' => (string) time()));
    }

    public function testCheckMaxSlots(): void
    {
        $Items = $this->getFreshItem(2);
        $Items->Users = $this->getRandomUserInTeam(2);
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

    public function testCanWriteAndWeAreAdmin(): void
    {
        $Items = $this->getFreshItem(2);
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
        $AdminScheduler = new Scheduler($Items);
        $adminEventId = $AdminScheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end));
        $User = $this->getUserInTeam(2);
        $UserScheduler = new Scheduler(new Items($User, $Items->id));
        $UserScheduler->setId($adminEventId);
        // try write event created by admin as user
        $this->expectException(IllegalActionException::class);
        $UserScheduler->patch(Action::Update, array('target' => 'experiment', 'id' => 3));
    }

    public function testUpdateStart(): void
    {
        $this->Scheduler->setId($this->testPostAction());
        $this->Scheduler->patch(Action::Update, array('target' => 'start', 'delta' => $this->delta));
        $delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '1111',
        );
        $this->Scheduler->patch(Action::Update, array('target' => 'start', 'delta' => $delta));
    }

    public function testUpdateEnd(): void
    {
        $this->Scheduler->setId($this->testPostAction());
        $this->Scheduler->patch(Action::Update, array('target' => 'end', 'delta' => $this->delta));
        $delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '1111',
        );
        $this->Scheduler->patch(Action::Update, array('target' => 'end', 'delta' => $delta));
    }

    public function testDestroy(): void
    {
        $this->assertTrue($this->getFreshSchedulerWithEvent()->destroy());
    }

    public function testCanCancelDuringGracePeriod(): void
    {
        $Items = $this->getFreshItem();
        $Scheduler = new Scheduler($Items);
        $d = new DateTime('+1 hour');
        $start = $d->format('c');
        $d->add(new DateInterval('PT2H'));
        $end = $d->format('c');
        $id = $Scheduler->postAction(Action::Create, array('start' => $start, 'end' => $end, 'title' => 'test grace period'));
        $Scheduler->setId($id);
        $this->assertTrue($Scheduler->destroy());
    }

    private function getFreshSchedulerWithEvent(): Scheduler
    {
        $Scheduler = new Scheduler($this->getFreshBookableItem(2));
        $id = $Scheduler->postAction(Action::Create, array('start' => $this->start, 'end' => $this->end));
        $Scheduler->setId($id);
        return $Scheduler;
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
