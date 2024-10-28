<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Make;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Elabftw\Enums\ApiEndpoint;
use Elabftw\Enums\ApiSubModels;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Interfaces\StringMakerInterface;
use Elabftw\Models\Config;
use Elabftw\Models\Items;
use Elabftw\Models\Scheduler;
use Elabftw\Models\Users;
use Elabftw\Models\Todolist;
use Elabftw\Models\UnfinishedSteps;
use Elabftw\Models\Notifications\StepDeadline;
use Elabftw\Models\Steps;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Entity\Todo;
use Eluceo\iCal\Domain\ValueObject\Alarm;
use Eluceo\iCal\Domain\ValueObject\Alarm\DisplayAction;
use Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;

use function array_merge;
use function date_default_timezone_get;
use function json_decode;
use function sprintf;
use function strlen;

/**
 * Make an iCal file
 */
class MakeICal extends AbstractMake implements StringMakerInterface
{
    protected string $contentType = 'text/calendar; charset=utf-8';

    public function __construct(protected string $calendarToken)
    {
        parent::__construct();
    }

    /**
     * Create an iCal file from events
     */
    public function getFileContent(): string
    {
        $iCalString = $this->getICalString($this->getEntries($this->getCalendarProperties()));
        $this->contentSize = strlen($iCalString);
        return $iCalString;
    }

    /**
     * Return a nice name for the file
     */
    public function getFileName(): string
    {
        return 'eLabFTW-calendar.ics';
    }

    private function getCalendarProperties(): array
    {
        $sql = 'SELECT `team`, `created_by`,
                        `all_events`,
                        JSON_ARRAYAGG(`category`) AS `categories`,
                        JSON_ARRAYAGG(`item`) AS `items`,
                        `todo`, `unfinished_steps_scope`
                    FROM `calendars`
                    LEFT JOIN `calendar2items_types` ON `calendars`.`id` = `calendar2items_types`.`calendar`
                    LEFT JOIN `calendar2items` ON `calendars`.`id` = `calendar2items`.`calendar`
                    WHERE `token` LIKE :token
                    GROUP BY `calendars`.`id`
                    LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':token', $this->calendarToken);
        $this->Db->execute($req);
        $res = $this->Db->fetch($req);
        foreach (array('categories', 'items') as $key) {
            $res[$key] = json_decode($res[$key], true);
            if ($res[$key][0] === null) {
                $res[$key] = null;
            }
        }
        return $res;
    }

    private function getEntries(array $calendarProps): array
    {
        $entries = array();
        // read an individual event
        // can be used to export a single event
        // if (isset($calendarProps['event'])) {
        //     $Item = new Items(new Users(team: $calendarProps['team']), bypassReadPermission: true);
        //     $Item->Users->userData['team'] = $calendarProps['team'];
        //     $Scheduler = new Scheduler($Item, $calendarProps['event']);
        //     $entries = array($Scheduler->readOne());
        // }

        // read all events of one bookable item
        if ($calendarProps['items'] !== null) {
            $Item = new Items(new Users($calendarProps['created_by'], $calendarProps['team']));
            foreach ($calendarProps['items'] as $item) {
                $Item->setId($item);
                foreach ((new Scheduler($Item))->readOne() as $event) {
                    $event['entry_type'] = 'event';
                    $entries[] = $event;
                };
            }
        }

        // read all events of one resource category
        if ($calendarProps['categories'] !== null) {
            $Item = new Items(new Users($calendarProps['created_by'], $calendarProps['team']));
            foreach ($calendarProps['categories'] as $category) {
                foreach ((new Scheduler($Item, category: $category))->readAll() as $event) {
                    $event['entry_type'] = 'event';
                    $entries[] = $event;
                }
            }
        }

        // read all events
        if ($calendarProps['all_events'] === 1) {
            $Item = new Items(new Users($calendarProps['created_by'], $calendarProps['team']));
            foreach ((new Scheduler($Item))->readAll() as $event) {
                $event['entry_type'] = 'event';
                $entries[] = $event;
            }
        }

        // read all todo entries
        if ($calendarProps['todo'] === 1) {
            foreach ((new Todolist($calendarProps['created_by']))->readAll() as $todo) {
                $todo['entry_type'] = 'todo';
                $entries[] = $todo;
            }
        }

        // unfinished steps user/team
        if ($calendarProps['unfinished_steps_scope'] !== 0) {
            $entries = array_merge(
                $entries,
                $this->prepareSteps($calendarProps, $calendarProps['unfinished_steps_scope'] === Scope::User->value),
            );
        }

        return $entries;
    }

    private function getICalString(array $entries): string
    {
        $calendar = new Calendar();
        foreach ($entries as $entry) {
            if ($entry['entry_type'] === 'event') {
                $calendar->addEvent($this->getEvent($entry));
            } elseif ($entry['entry_type'] === 'todo') {
                $calendar->addTodo($this->getTodo($entry));
            } elseif ($entry['entry_type'] === 'steps') {
                $calendar->addTodo($this->getStep($entry));
            }
        }
        return (string) (new CalendarFactory())->createCalendar($calendar);
    }

    private function getEvent(array $event): Event
    {
        /** @psalm-suppress PossiblyFalseArgument */
        return (new Event(
            new UniqueIdentifier(sprintf(
                '%s/event/%d',
                Config::fromEnv('SITE_URL'),
                $event['id'],
            ))
        ))
        ->setSummary($event['title'] ?? _('Untitled'))
        ->setDescription(trim(sprintf(
            "%s\n%s\n%s\n%s\n%s",
            $event['title_only'] ?? _('Untitled'), // event title
            $event['fullname'] ?? '', // name of the user who booked the event
            $event['item_title'] ?? '', // title of the bookable resource item
            $event['experiment_title'] ?? '', // title of a linked experiment
            $event['item_link_title'] ?? '', // title of a linked resource item
        )))
        ->setOccurrence(new TimeSpan(
            /** @phpstan-ignore argument.type */
            new DateTime(DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $event['start']), false),
            /** @phpstan-ignore argument.type */
            new DateTime(DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $event['end']), false),
        ));
    }

    private function getTodo(array $todo): Todo
    {
        return (new Todo(
            new UniqueIdentifier(sprintf(
                '%s/%s/%d',
                Config::fromEnv('SITE_URL'),
                ApiEndpoint::Todolist->value,
                $todo['id'],
            ))
        ))->setSummary($todo['body']);
    }

    // id, title, entity_type, step_id, step_body, deadline
    private function getStep(array $step): Todo
    {
        $todo = (new Todo(
            new UniqueIdentifier(sprintf(
                '%s/%s/%d/%s/%d',
                Config::fromEnv('SITE_URL'),
                $step['entity_type'],
                $step['id'],
                ApiSubModels::Steps->value,
                $step['step_id'],
            ))
        ))->setSummary(sprintf(
            '[%s] %s - %s',
            $step['entity_type'],
            $step['title'],
            $step['step_body'],
        ));

        if ($step['deadline'] !== null) {
            // todo check if timezone is correct, are the deadlines stored in server time zone or UTC?
            /** @phpstan-ignore argument.type */ /** @psalm-suppress PossiblyFalseArgument */
            $todo->setDue(new DateTime(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $step['deadline'], new DateTimeZone(date_default_timezone_get())), true));
        }

        if ($step['deadline_notif'] !== null) {
            $todo->addAlarm(new Alarm(
                new DisplayAction(_('A step deadline is approaching.')),
                (new RelativeTrigger(DateInterval::createFromDateString(sprintf('-%d minutes', StepDeadline::NOTIFLEADTIME))))->withRelationToEnd(),
            ));
        }

        return $todo;
    }

    private function prepareSteps(array $calendarProps, bool $teamScope = false): array
    {
        $User = new Users($calendarProps['created_by'], $calendarProps['team']);
        $UnfinishedArr = (new UnfinishedSteps($User, $teamScope))->readAll();

        $entries = array();
        foreach ($UnfinishedArr as $type => $UnfinishedStepsArr) {
            foreach ($UnfinishedStepsArr as $UnfinishedStep) {
                $UnfinishedStep['entity_type'] = $type;
                // id, title, entity_type, steps:array(array(0=>id, 1=>body),...)
                foreach ($UnfinishedStep['steps'] as $step) {
                    $entry = $UnfinishedStep;
                    unset($entry['steps']);
                    $entry['step_id'] = (int) $step[0];
                    $entry['step_body'] = $step[1];
                    $fullStep = (new Steps(
                        EntityType::from($entry['entity_type'])
                            ->toInstance($User, $entry['id']),
                        $entry['step_id']
                    ))->readOne();
                    $entry['deadline'] = $fullStep['deadline'];
                    $entry['deadline_notif'] = $fullStep['deadline_notif'];
                    $entry['entry_type'] = 'steps';
                    // id, title, entity_type, step_id, step_body, deadline, deadline_notif, entry_type
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }
}
