<?php

declare(strict_types=1);

/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Elabftw\Db;
use Elabftw\Enums\Action;
use Elabftw\Enums\Usergroup;
use Elabftw\Models\Teams;
use Elabftw\Models\Users\Users;
use Elabftw\Models\Users\ValidatedUser;
use Elabftw\Traits\TestsUtilsTrait;
use Symfony\Component\Console\Output\NullOutput;

use function bin2hex;
use function random_bytes;

class ExpirationNotifierTest extends \PHPUnit\Framework\TestCase
{
    use TestsUtilsTrait;

    public function testSendEmails(): void
    {
        $Db = Db::getConnection();
        $Db->beginTransaction();

        try {
            // Make sure unrelated test data cannot affect this test.
            $sql = "UPDATE users
                SET valid_until = '3000-01-01'
                WHERE valid_until BETWEEN CURDATE()
                    AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            $Db->execute($Db->prepare($sql));

            $suffix = bin2hex(random_bytes(6));

            // Dedicated team with exactly one admin.
            $teamId = new Teams(new Users(1, 1))
                ->create('Expiration notifier ' . $suffix);

            $admin = ValidatedUser::fromAdmin(
                'expiration-admin-' . $suffix . '@example.com',
                array($teamId),
                'Expiration',
                'Admin',
                Usergroup::Admin,
            );

            $admin = new Users(
                $admin->getUserid(),
                $teamId,
            );

            // Dedicated user that expires tomorrow.
            $user = ValidatedUser::fromAdmin(
                'expiration-user-' . $suffix . '@example.com',
                array($teamId),
                'Expire',
                'Soon',
                Usergroup::User,
            );

            $user->requester = $admin;
            $user->patch(
                Action::Update,
                array(
                    'valid_until' => new DateTimeImmutable('tomorrow')
                        ->format('Y-m-d'),
                ),
            );

            $email = $this->createMock(Email::class);
            $email
                ->expects(self::exactly(2))
                ->method('sendEmail')
                ->willReturn(true);

            $notifier = new ExpirationNotifier($email);

            // One user email + one admin email.
            // sendEmails() returns only the number of admin emails.
            self::assertSame(
                1,
                $notifier->sendEmails(new NullOutput()),
            );
        } finally {
            $Db->rollBack();
        }
    }
}
