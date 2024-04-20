<?php

declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use DateTimeImmutable;
use Elabftw\Enums\Action;
use Elabftw\Enums\Usergroup;
use Elabftw\Models\ValidatedUser;

class ExpirationNotifierTest extends \PHPUnit\Framework\TestCase
{
    public function testSendEmails(): void
    {
        // first make a user close to expiration
        $user = ValidatedUser::fromAdmin('expire@soon.example', array(1), 'expire', 'soon', Usergroup::User);
        $date = new DateTimeImmutable('tomorrow');
        $user->patch(Action::Update, array('valid_until' => $date->format('Y-m-d')));
        // now alert user and admin
        $stub = $this->createStub(Email::class);
        $stub->method('sendEmail')->willReturn(true);
        $ExpirationNotifier = new ExpirationNotifier($stub);
        // 2 emails should be sent, one for the user, one for the admin, but we only collect the admins email in the function return value
        $this->assertEquals(1, $ExpirationNotifier->sendEmails());
    }
}
