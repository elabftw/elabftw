<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Enums\EmailTarget;
use Elabftw\Exceptions\ForbiddenException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Notifications\EventDeleted;
use Elabftw\Models\Users\Users;
use Elabftw\Services\Email;
use Elabftw\Traits\TestsUtilsTrait;

use function count;
use function sprintf;

class EventDeletedTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testTeamTargetIsDerivedFromEvent(): void
    {
        $requester = $this->getRandomUserInTeam(1);
        $Notifications = $this->getNotifications($requester);
        $expected = count(Email::getIdsOfRecipients(EmailTarget::Team, 1));

        $actual = $Notifications->postAction(Action::Create, array(
            'target' => EmailTarget::Team->value,
            'targetid' => 2,
        ));

        $this->assertSame($expected, $actual);
    }

    public function testInstanceWideTargetsAreRejected(): void
    {
        $requester = $this->getRandomUserInTeam(1);
        $targets = array(
            EmailTarget::Admins,
            EmailTarget::Sysadmins,
            EmailTarget::ActiveUsers,
            EmailTarget::AdminsOfTeam,
        );
        foreach ($targets as $target) {
            try {
                $this->getNotifications($requester)->postAction(Action::Create, array('target' => $target->value));
                $this->fail(sprintf('Target %s should not be accepted.', $target->value));
            } catch (ImproperActionException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testForeignTeamGroupIsRejected(): void
    {
        $requester = $this->getRandomUserInTeam(1);
        $teamTwoAdmin = $this->getUserInTeam(2, admin: 1);
        $foreignGroupId = (new TeamGroups($teamTwoAdmin))->postAction(
            Action::Create,
            array('name' => 'Foreign notification target'),
        );

        $this->expectException(ImproperActionException::class);
        $this->getNotifications($requester)->postAction(Action::Create, array(
            'target' => EmailTarget::TeamGroup->value,
            'targetid' => $foreignGroupId,
        ));
    }

    public function testReadOnlyUserCannotNotifyEventRecipients(): void
    {
        $requester = $this->getRandomUserInTeam(1);
        $this->expectException(ForbiddenException::class);
        $this->getNotifications($requester, eventOwner: -1)->postAction(Action::Create, array(
            'target' => EmailTarget::Team->value,
        ));
    }

    private function getNotifications(Users $requester, ?int $eventOwner = null): EventDeleted
    {
        $now = new DateTimeImmutable();
        return new EventDeleted($requester, array(
            'item' => 12,
            'team' => 1,
            'userid' => $eventOwner ?? $requester->userid,
            'start' => $now->format('Y-m-d H:i:s'),
            'end' => $now->format('Y-m-d H:i:s'),
        ), 'Test User');
    }
}
