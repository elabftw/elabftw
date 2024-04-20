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
class EmailValidator
{
    private ?string $emailDomain;

    private Db $Db;

    public function __construct(private string $email, ?string $emailDomain = null)
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
            throw new ImproperActionException(_('Someone is already using that email address!'));
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
