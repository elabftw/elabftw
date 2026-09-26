<?php

declare(strict_types=1);

/**
 * @author Moritz IHLER
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\WebhookEvent;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Models\Users\Users;
use Elabftw\Traits\TestsUtilsTrait;
use PDO;

use function count;

class UsersWebhooksTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    /** a user who belongs to more than one team */
    private int $userid;

    protected function setUp(): void
    {
        $this->userid = $this->getUserIdFromEmail('user1@demo.elabftw.net');
    }

    public function testGetApiPath(): void
    {
        $this->assertStringEndsWith('webhooks/', UsersWebhooks::forRequester($this->getSysadmin(), $this->userid)->getApiPath());
    }

    public function testOwnerManagesTheirOwnWebhooks(): void
    {
        $Webhooks = UsersWebhooks::forRequester(new Users($this->userid), $this->userid);
        $initialCount = count($Webhooks->readAll());
        $id = $Webhooks->postAction(Action::Create, array(
            'name' => 'my own hook',
            'url' => 'https://192.0.2.40/hook',
            'events' => array(WebhookEvent::ExperimentUpdated->value),
        ));
        $this->assertEquals($initialCount + 1, count($Webhooks->readAll()));

        $Webhook = UsersWebhooks::forRequester(new Users($this->userid), $this->userid, $id);
        $this->assertEquals('https://192.0.2.40/hook', $Webhook->readOne()['url']);
        $this->assertTrue($Webhook->destroy());
    }

    public function testSysadminManagesAnyUsersWebhooks(): void
    {
        $Webhooks = UsersWebhooks::forRequester($this->getSysadmin(), $this->userid);
        $id = $Webhooks->postAction(Action::Create, array(
            'url' => 'https://192.0.2.41/hook',
            'events' => array(WebhookEvent::ItemCreated->value),
        ));
        $this->assertTrue(UsersWebhooks::forRequester($this->getSysadmin(), $this->userid, $id)->destroy());
    }

    /**
     * A team admin is an admin of every user they share a team with, but a user webhook is
     * matched on ownership alone in WebhooksQueue::fanout(), with no team constraint. Letting
     * a team admin manage it would hand them the events of the entries that user owns in
     * their other teams, and the signing secret with them.
     */
    public function testTeamAdminCannotManageAUsersWebhooks(): void
    {
        $teamAdmin = $this->getUserInTeam(team: 2, admin: 1);
        // the premise: the admin administers this user, and the user is in a team the admin
        // is not in. Without both, this test would pass for the wrong reason.
        $this->assertTrue($teamAdmin->isAdminOf($this->userid));
        $this->assertNotEmpty($this->getTeamsOnlyTheUserIsIn($teamAdmin->getUserid()));

        try {
            UsersWebhooks::forRequester($teamAdmin, $this->userid)->readAll();
            $this->fail('a team admin listing a user webhook should have been refused');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }
        try {
            UsersWebhooks::forRequester($teamAdmin, $this->userid)->postAction(Action::Create, array(
                'url' => 'https://192.0.2.42/hook',
                'events' => array(WebhookEvent::ExperimentCreated->value),
            ));
            $this->fail('a team admin creating a user webhook should have been refused');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }

        // and the secret of a webhook the user set up themselves stays out of reach
        $id = UsersWebhooks::forRequester(new Users($this->userid), $this->userid)->postAction(Action::Create, array(
            'url' => 'https://192.0.2.43/hook',
            'events' => array(WebhookEvent::ExperimentCreated->value),
        ));
        try {
            UsersWebhooks::forRequester($teamAdmin, $this->userid, $id)->readOne();
            $this->fail('a team admin reading a user webhook should have been refused');
        } catch (ForbiddenException) {
            $this->addToAssertionCount(1);
        }
        UsersWebhooks::forRequester($this->getSysadmin(), $this->userid, $id)->destroy();
    }

    private function getSysadmin(): Users
    {
        return new Users(1, 1);
    }

    /**
     * @return array<int, int> teams the user is in and the other one is not
     */
    private function getTeamsOnlyTheUserIsIn(int $otherUserid): array
    {
        $Db = Db::getConnection();
        $sql = 'SELECT teams_id FROM users2teams WHERE users_id = :userid
            AND teams_id NOT IN (SELECT teams_id FROM users2teams WHERE users_id = :other)';
        $req = $Db->prepare($sql);
        $req->bindValue(':userid', $this->userid, PDO::PARAM_INT);
        $req->bindValue(':other', $otherUserid, PDO::PARAM_INT);
        $Db->execute($req);
        return $req->fetchAll(PDO::FETCH_COLUMN);
    }
}
