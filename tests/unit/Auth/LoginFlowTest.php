<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Auth;

use Elabftw\Elabftw\Authentication;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\AuthMethod;
use Elabftw\Enums\EnforceMfa;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\UnauthorizedException;
use PDO;
use PHPUnit\Framework\TestCase;

final class LoginFlowTest extends TestCase
{
    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
        $this->Db->execute(
            $this->Db->prepare(
                'UPDATE users SET mfa_secret = NULL WHERE userid = 1',
            ),
        );
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public function testStartRequiresMfaWhenPolicyRequiresIt(): void
    {
        $step = $this->createFlow(EnforceMfa::Everyone)->start(
            new Authentication(1, AuthMethod::Local),
        );

        self::assertInstanceOf(MfaRequired::class, $step);
    }

    public function testStartContinuesWhenMfaIsNotRequired(): void
    {
        $this->replaceMemberships(array(1));

        $step = $this->createFlow()->start(
            new Authentication(1, AuthMethod::Local),
        );

        self::assertInstanceOf(UserLoginContext::class, $step);
        self::assertSame(1, $step->getTeam());
    }

    public function testAfterMfaRequiresPasswordRenewalForOldLocalPassword(): void
    {
        $req = $this->Db->prepare(
            'UPDATE users SET password_modified_at = :modified_at WHERE userid = 1',
        );
        $req->bindValue(':modified_at', '2000-01-01 00:00:00');
        $this->Db->execute($req);

        $step = $this->createFlow(maxPasswordAgeDays: 1)->afterMfa(
            new Authentication(1, AuthMethod::Local),
        );

        self::assertInstanceOf(PasswordRenewalRequired::class, $step);
    }

    public function testAfterMfaSkipsPasswordRenewalForExternalAuthentication(): void
    {
        $this->replaceMemberships(array(1));

        $step = $this->createFlow(maxPasswordAgeDays: 1)->afterMfa(
            new Authentication(1, AuthMethod::Saml),
        );

        self::assertInstanceOf(UserLoginContext::class, $step);
    }

    public function testAfterPasswordRenewalRequestsTeamWhenNoneIsSelectable(): void
    {
        $this->replaceMemberships(array());

        $step = $this->createFlow()->afterPasswordRenewal(
            new Authentication(1, AuthMethod::Local),
        );

        self::assertInstanceOf(TeamRequestRequired::class, $step);
    }

    public function testAfterPasswordRenewalFinalizesSingleSelectableTeam(): void
    {
        $this->replaceMemberships(array(1));

        $step = $this->createFlow()->afterPasswordRenewal(
            new Authentication(1, AuthMethod::Local),
        );

        self::assertInstanceOf(UserLoginContext::class, $step);
        self::assertSame(1, $step->getTeam());
    }

    public function testAfterPasswordRenewalRequestsSelectionForSeveralTeams(): void
    {
        $this->replaceMemberships(array(1, 2));

        $step = $this->createFlow()->afterPasswordRenewal(
            new Authentication(1, AuthMethod::Local),
        );

        self::assertInstanceOf(TeamSelectionRequired::class, $step);
        self::assertSame(2, $step->teams->count());
    }

    public function testSelectTeamRejectsTeamOutsideSelectableSet(): void
    {
        $this->replaceMemberships(array(1, 2));

        $this->expectException(UnauthorizedException::class);
        $this->createFlow()->selectTeam(
            new Authentication(1, AuthMethod::Local),
            3,
        );
    }

    public function testSelectTeamFinalizesSelectableTeam(): void
    {
        $this->replaceMemberships(array(1, 2));

        $context = $this->createFlow()->selectTeam(
            new Authentication(1, AuthMethod::Local),
            2,
        );

        self::assertSame(1, $context->getUserid());
        self::assertSame(2, $context->getTeam());
    }

    public function testStartRejectsUserArchivedInAllTeams(): void
    {
        $this->replaceMemberships(array());
        $this->expectException(ImproperActionException::class);
        $this->expectExceptionMessage('This account is archived in all teams and cannot login.');
        $this->createFlow()->start(new Authentication(1, AuthMethod::Saml));
    }

    private function createFlow(
        EnforceMfa $enforceMfa = EnforceMfa::Disabled,
        int $maxPasswordAgeDays = 0,
    ): LoginFlow {
        return new LoginFlow(
            new MfaPolicy($enforceMfa),
            new PasswordRenewalPolicy($maxPasswordAgeDays),
            new SelectableTeamsProvider(),
            new UserLoginValidator(),
        );
    }

    /**
     * @param list<int> $teamIds
     */
    private function replaceMemberships(array $teamIds): void
    {
        $archive = $this->Db->prepare(
            'UPDATE users2teams SET is_archived = 1 WHERE users_id = 1',
        );
        $this->Db->execute($archive);

        foreach ($teamIds as $teamId) {
            $insert = $this->Db->prepare(
                'INSERT INTO users2teams (users_id, teams_id, is_archived)
                    VALUES (1, :team_id, 0)
                    ON DUPLICATE KEY UPDATE is_archived = 0',
            );
            $insert->bindValue(':team_id', $teamId, PDO::PARAM_INT);
            $this->Db->execute($insert);
        }
    }
}
