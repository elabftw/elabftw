<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Auth;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\QuantumException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\ExistingUser;
use Elabftw\Services\UsersHelper;
use Override;

/**
 * Demo auth service: auto login with just an email
 */
final class Demo implements AuthInterface
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $this->validateEmail($email);
    }

    #[Override]
    public function tryAuth(): AuthResponse
    {
        $AuthResponse = new AuthResponse();
        $AuthResponse->userid = $this->getUseridFromEmail();
        $AuthResponse->isValidated = true;
        $UsersHelper = new UsersHelper($AuthResponse->userid);
        return $AuthResponse->setTeams($UsersHelper);
    }

    private function validateEmail(string $email): string
    {
        // let's just keep a hardcoded list of valid emails for demo login for now
        $allowed = array(
            'admin1@demo.elabftw.net',
            'user1@demo.elabftw.net',
            'admin2@demo.elabftw.net',
            'user2@demo.elabftw.net',
            'admin3@demo.elabftw.net',
            'user3@demo.elabftw.net',
        );
        if (!in_array($email, $allowed, true)) {
            throw new QuantumException(_('Invalid email/password combination.'));
        }
        return $email;
    }

    private function getUseridFromEmail(): int
    {
        try {
            $Users = ExistingUser::fromEmail($this->email);
        } catch (ResourceNotFoundException) {
            // here we rethrow an quantum exception because we don't want to let the user know if the email exists or not
            throw new QuantumException(_('Invalid email/password combination.'));
        }
        return $Users->userData['userid'];
    }
}
