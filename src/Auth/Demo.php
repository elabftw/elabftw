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

use Elabftw\Elabftw\Authentication;
use Elabftw\Enums\AuthMethod;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthenticatorInterface;
use Elabftw\Models\Users\ExistingUser;
use Override;

use function in_array;

/**
 * Demo auth service: auto login with just an email
 */
final class Demo implements AuthenticatorInterface
{
    // let's just keep a hardcoded list of valid emails for demo login for now
    private const array ALLOWED_EMAILS = array(
        'admin1@demo.elabftw.net',
        'user1@demo.elabftw.net',
        'admin2@demo.elabftw.net',
        'user2@demo.elabftw.net',
        'admin3@demo.elabftw.net',
        'user3@demo.elabftw.net',
    );

    private string $email;

    public function __construct(string $email)
    {
        $this->email = $this->validateEmail($email);
    }

    #[Override]
    public function authenticate(): Authentication
    {
        return new Authentication(
            $this->getUseridFromEmail(),
            AuthMethod::Demo,
        );
    }

    private function validateEmail(string $email): string
    {
        if (!in_array($email, self::ALLOWED_EMAILS, true)) {
            throw new InvalidCredentialsException();
        }
        return $email;
    }

    private function getUseridFromEmail(): int
    {
        try {
            $Users = ExistingUser::fromEmail($this->email);
        } catch (ResourceNotFoundException) {
            // here we rethrow an exception because we don't want to let the user know if the email exists or not
            throw new InvalidCredentialsException();
        }
        return $Users->userData['userid'];
    }
}
