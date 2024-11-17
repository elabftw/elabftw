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

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\CalendarKeys;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Scope;
use Elabftw\Enums\State;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\RestInterface;
use Elabftw\Services\Filter;
use Elabftw\Traits\SetIdTrait;
use PDO;
use Symfony\Component\HttpFoundation\Request;

use function array_combine;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function array_walk;
use function count;
use function end;
use function explode;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function str_repeat;
use function strlen;
use function strtoupper;
use function random_int;

/**
 * All about calendars - iCal feeds of team events
 */
class Calendar implements RestInterface
{
    use SetIdTrait;

    public const int TOKEN_LENGTH = 60;

    private Db $Db;

    public function __construct(private Users $User, ?int $id = null)
    {
        $this->Db = Db::getConnection();
        $this->setId($id);
    }

    public function create(array $reqBody = array()): int
    {
        $sql = 'INSERT INTO calendars (title, token, team, created_by, all_events, todo, unfinished_steps_scope)
                    VALUES (:title, :token, :team, :created_by, :all_events, :todo, :unfinished_steps_scope)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':title', $reqBody['title']);
        $req->bindValue(':token', $this::randomAlphaNumericString(self::TOKEN_LENGTH));
        $req->bindParam(':team', $this->User->team, PDO::PARAM_INT);
        $req->bindParam(':all_events', $reqBody['all_events']);
        $req->bindParam(':todo', $reqBody['todo']);
        $req->bindParam(':unfinished_steps_scope', $reqBody['unfinished_steps_scope']);
        $req->bindParam(':created_by', $this->User->userid, PDO::PARAM_INT);
        $this->Db->execute($req);
        $newId = $this->Db->lastInsertId();

        $this->populateJunctionTables($newId, $reqBody);

        return $newId;
    }

    public function postAction(Action $action, array $reqBody): int
    {
        $normalizedReqBody = CalendarKeys::getDefaultValues();

        foreach (CalendarKeys::toArray() as $key) {
            if (isset($reqBody[$key])) {
                $normalizedReqBody[$key] = match ($key) {
                    CalendarKeys::Title->value => Filter::title((string) $reqBody[$key]),
                    CalendarKeys::Todo->value,
                    CalendarKeys::AllEvents->value => Filter::toBinary($reqBody[$key]),
                    CalendarKeys::UnfinishedStepsScope->value => match ($reqBody[$key]) {
                        Scope::User->toString() => Scope::User->value,
                        Scope::Team->toString() => Scope::Team->value,
                        default => 0,
                    },
                    CalendarKeys::Categories->value,
                    CalendarKeys::Items->value => is_array($reqBody[$key]) ? array_filter($reqBody[$key], 'is_int') : null,
                    default => null,
                };
            }
        }

        return $this->create($normalizedReqBody);
    }

    public function patch(Action $action, array $params): array
    {
        throw new ImproperActionException('No PATCH action.');
    }

    public function getApiPath(): string
    {
        return 'api/v2/calendar/';
    }

    public function readAll(): array
    {
        $page = explode('/', (Request::createFromGlobals())->getScriptName());
        $isProfile = end($page) === 'profile.php';
        $sql = sprintf(
            '%s %s',
            $this->getCommonSQL(),
            !$this->User->isAdmin || $isProfile
                ? 'AND calendars.created_by = :user'
                : '',
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $req->bindParam(':team', $this->User->team, PDO::PARAM_INT);
        if (!$this->User->isAdmin || $isProfile) {
            $req->bindParam(':user', $this->User->userid, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        $result = $req->fetchAll();
        $this->postProcessRead($result);
        return $result;
    }

    public function readOne(): array
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }

        $sql = sprintf(
            '%s AND id = :id %s',
            $this->getCommonSQL(),
            !$this->User->isAdmin
                ? 'AND calendars.created_by = :user'
                : '',
        );
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $req->bindParam(':team', $this->User->team, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        if (!$this->User->isAdmin) {
            $req->bindParam(':user', $this->User->userid, PDO::PARAM_INT);
        }
        $this->Db->execute($req);
        $result = $req->fetchAll();
        $this->postProcessRead($result);
        return $result[0];
    }

    public function destroy(): bool
    {
        $this->canOrExplode();
        $sql = 'UPDATE calendars
                  SET state = :state
                  WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Deleted->value, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        return $this->Db->execute($req);
    }

    /**
     * Generate a random alphanumeric string
     */
    public static function randomAlphaNumericString(int $length): string
    {
        $usedChars  = 'abcdefghijklmnopqrstuvwxyz';
        $usedChars .= strtoupper($usedChars);
        $usedChars .= '0123456789';
        $randomMax = strlen($usedChars) - 1;

        $token = str_repeat(' ', $length);
        for ($i = 0; $i < $length; $i++) {
            $token[$i] = $usedChars[random_int(0, $randomMax)];
        }

        return $token;
    }

    private function canOrExplode(): void
    {
        if ($this->id === null) {
            throw new IllegalActionException('No id was set!');
        }

        $sql = 'SELECT created_by FROM calendars
                    WHERE state = :state
                        AND team = :team
                        AND id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindValue(':state', State::Normal->value, PDO::PARAM_INT);
        $req->bindParam(':team', $this->User->team, PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetch();
        if (count($res) === 0 || $res['created_by'] !== $this->User->userid && $this->User->isAdminOf($res['created_by']) === false) {
            throw new IllegalActionException('You are not allowed to do this.');
        }
    }

    private function populateJunctionTables(int $calendarId, array $reqBody): void
    {
        $tables = array(
            CalendarKeys::Categories->value => array(
                'table' => EntityType::ItemsTypes->value,
                'column' => 'category',
            ),
            CalendarKeys::Items->value => array(
                'table' => EntityType::Items->value,
                'column' => 'item',
            ),
        );
        foreach ($tables as $key => $value) {
            if (isset($reqBody[$key])) {
                $sql = sprintf(
                    'INSERT INTO calendar2%s (calendar, %s)
                        VALUES (:calendar_id, :entity_id)',
                    $value['table'],
                    $value['column'],
                );
                $req = $this->Db->prepare($sql);
                $req->bindParam(':calendar_id', $calendarId, PDO::PARAM_INT);
                foreach ($reqBody[$key] as $id) {
                    $req->bindParam(':entity_id', $id, PDO::PARAM_INT);
                    $this->Db->execute($req);
                }
            }
        }
    }

    private function postProcessRead(array &$result): void
    {
        array_walk($result, function (&$row) {
            // hide token from admins if calendar contains a todolist
            if ($this->User->isAdmin
                && $this->User->userid !== $row['created_by']
                && $row['todo'] === 1
            ) {
                $row['token'] = str_repeat('·', self::TOKEN_LENGTH);
            }

            foreach (array('items', 'categories') as $key) {
                if (is_string($row[$key])) {
                    $row[$key] = $this->transformJsonAgg($row[$key]);
                }
            }
        });
    }

    /**
     * Transforms the MySQL JSON object of aggregate arrays into an array of associative arrays
     *
     * example: JSON input string: {"id": [1, 2, 3], "title": ["foo", "bar", "baz"]}
     *          PHP output array:  [['id' => 1, 'title' => 'foo'], ['id' => 2, 'title' => 'bar'], ['id' => 3, 'title' => 'baz']]
     */
    private function transformJsonAgg(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            return array();
        }
        return array_map(
            fn(...$values) => array_combine(array_keys($decoded), $values),
            ...array_values($decoded),
        );
    }

    private function getCommonSQL(): string
    {
        return 'WITH agg_items AS (
                SELECT calendar2items.calendar,
                    JSON_OBJECT(
                        "id", JSON_ARRAYAGG(items.id),
                        "title", JSON_ARRAYAGG(items.title)
                    ) as items
                FROM calendar2items
                JOIN items ON calendar2items.item = items.id
                GROUP BY calendar2items.calendar
            ),
            agg_items_types AS (
                SELECT calendar2items_types.calendar,
                    JSON_OBJECT(
                        "id", JSON_ARRAYAGG(items_types.id),
                        "title", JSON_ARRAYAGG(items_types.title),
                        "color", JSON_ARRAYAGG(items_types.color)
                    ) as categories
                FROM calendar2items_types
                JOIN items_types ON calendar2items_types.category = items_types.id
                GROUP BY calendar2items_types.calendar
            )
            SELECT calendars.*,
                agg_items.items,
                agg_items_types.categories,
                CONCAT(users.firstname, " ", users.lastname) as fullname
            FROM calendars
            LEFT JOIN agg_items ON calendars.id = agg_items.calendar
            LEFT JOIN agg_items_types ON calendars.id = agg_items_types.calendar
            LEFT JOIN users ON calendars.created_by = users.userid
            WHERE calendars.state = :state
                AND calendars.team = :team';
    }
}
