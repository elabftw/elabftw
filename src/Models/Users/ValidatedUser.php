<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Models\Users;

use Elabftw\Enums\Usergroup;
use Elabftw\Enums\UsersColumn;
use Elabftw\Exceptions\ImproperActionException;
use Override;

/**
 * A user that exists in the db, so we have a userid but not necessarily a team
 */
final class ValidatedUser extends ExistingUser
{
    #[Override]
    public static function fromEmail(string $email): Users
    {
        return self::search(UsersColumn::Email, $email, true);
    }

    #[Override]
    public static function fromOrgid(string $orgid): Users
    {
        return self::search(UsersColumn::Orgid, $orgid, true);
    }

    public static function fromExternal(string $email, array $teams, string $firstname, string $lastname, ?string $orgid = null, bool $allowTeamCreation = false): Users
    {
        return parent::fromScratch($email, $teams, $firstname, $lastname, automaticValidationEnabled: true, orgid: $orgid, allowTeamCreation: $allowTeamCreation);
    }

    public static function fromAdmin(string $email, array $teams, string $firstname, string $lastname, Usergroup $usergroup, bool $skipDomainValidation = false): Users
    {
        return parent::fromScratch($email, $teams, $firstname, $lastname, $usergroup, true, false, skipDomainValidation: $skipDomainValidation);
    }

    // create a user from the information provided in a node of type Person (.eln)
    // skip domain validation here to prevent running into an error while importing trusted eln
    public static function createFromPerson(array $person, int $team): Users
    {
        return self::fromAdmin(
            $person['email'] ?? throw new ImproperActionException('Could not find an email to create the user!'),
            array($team),
            $person['givenName'] ?? 'Unknown',
            $person['familyName'] ?? 'Unknown',
            Usergroup::User,
            skipDomainValidation: true,
        );
    }
}
