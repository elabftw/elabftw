<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;

use function array_map;
use function filter_var;
use function in_array;

/**
 * Validate an email address for several parameters
 */
final class EmailValidator
{
    private ?string $emailDomain;

    private Db $Db;

    public function __construct(private string $email, private bool $adminsImportUsers = false, ?string $emailDomain = null)
    {
        // if it's an empty string, make it null
        if ($emailDomain === '') {
            $emailDomain = null;
        }
        $this->emailDomain = $emailDomain;
        $this->Db = Db::getConnection();
    }

    public function validate(): string
    {
        $this->basicCheck();
        if ($this->isDuplicateEmail()) {
            // error message will be different depending on the setting of "Allow admins to import users"
            $msg = _('An active account already exists with this email. Use the Sysconfig Panel to add user to the requested team.');
            if ($this->adminsImportUsers) {
                $msg = _('An active account already exists with this email. Search for user in the input above without a team filter and use "Add to team" action to add the user to your team.');
            }
            throw new ImproperActionException($msg);
        }
        $this->validateDomain();
        return $this->email;
    }

    private function basicCheck(): void
    {
        if (filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new ImproperActionException('Invalid email address!');
        }
    }

    private function validateDomain(): void
    {
        if ($this->emailDomain !== null) {
            $splitEmail = explode('@', $this->email);
            $splitDomains = array_map('trim', explode(',', $this->emailDomain));
            if (!in_array($splitEmail[1], $splitDomains, true)) {
                throw new ImproperActionException(sprintf(_('This email domain is not allowed. Allowed domains: %s'), implode(', ', $splitDomains)));
            }
        }
    }

    /**
     * Check we have not a duplicate email in DB
     *
     * @return bool true if there is a duplicate
     */
    private function isDuplicateEmail(): bool
    {
        $sql = 'SELECT email FROM users WHERE email = :email AND archived = 0';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':email', $this->email);
        $this->Db->execute($req);

        return (bool) $req->rowCount();
    }
}
