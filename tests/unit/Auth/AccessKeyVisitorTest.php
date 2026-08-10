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

use Elabftw\Elabftw\Db;
use Elabftw\Enums\EntityType;
use Elabftw\Enums\Entrypoint;
use Elabftw\Enums\Language;
use Elabftw\Models\Experiments;
use Elabftw\Models\Users\AnonymousUser;
use Elabftw\Models\Users\Users;
use Elabftw\Services\AccessKeyHelper;
use Elabftw\Services\TeamFinder;
use PHPUnit\Framework\TestCase;

final class AccessKeyVisitorTest extends TestCase
{
    private Db $Db;

    protected function setUp(): void
    {
        $this->Db = Db::getConnection();
        $this->Db->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->Db->rollBack();
    }

    public function testGetUserReturnsAnonymousUserForAccessKeyTeam(): void
    {
        $Experiments = new Experiments(new Users(1, 1));
        $experimentId = $Experiments->create();
        $accessKey = new AccessKeyHelper(
            EntityType::Experiments,
            $experimentId,
        )->toggleAccessKey();

        $visitor = new AccessKeyVisitor(
            new TeamFinder(
                Entrypoint::Experiments->toPage(),
                $accessKey,
            ),
            new AnonymousLoginValidator(true),
        );

        $user = $visitor->getUser();

        self::assertInstanceOf(AnonymousUser::class, $user);
        self::assertSame(1, $user->team);
        self::assertSame(Language::EnglishGB->value, $user->userData['lang']);
    }
}
