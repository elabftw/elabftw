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

use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use function filter_var;
use function in_array;
use function mb_strlen;

/**
 * When values need to be checked
 */
class Check
{
    /** the minimum password length */
    public const MIN_PASSWORD_LENGTH = 8;

    /** cookie is a sha256 sum: 64 chars */
    private const COOKIE_LENGTH = 64;

    /**
     * Check the number of character of a password
     */
    public static function passwordLength(string $password): string
    {
        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new ImproperActionException(sprintf(_('Password must contain at least %d characters.'), self::MIN_PASSWORD_LENGTH));
        }
        return $password;
    }

    /**
     * Check ID is valid (pos int)
     */
    public static function id(int $id): int | false
    {
        $filter_options = array(
            'options' => array(
                'min_range' => 1,
            ),
        );
        return filter_var($id, FILTER_VALIDATE_INT, $filter_options);
    }

    /**
     * Check id and throw exception if it's wrong
     */
    public static function idOrExplode(int $id): int
    {
        if (self::id($id) === false) {
            throw new IllegalActionException('The id parameter is not valid!');
        }
        return $id;
    }

    public static function usergroup(int $gid): int
    {
        return match ($gid) {
            1, 2, 4 => $gid,
            default => throw new ImproperActionException('Invalid usergroup value.'),
        };
    }

    /**
     * Get only the relevant part of the color: remove the #
     *
     * @param string $color #121212
     */
    public static function color(string $color): string
    {
        $color = filter_var(substr($color, 1, 7), FILTER_SANITIZE_STRING);
        if ($color === false || mb_strlen($color) !== 6) {
            throw new ImproperActionException('Bad color');
        }
        return $color;
    }

    /**
     * Check the limit value
     */
    public static function limit(int $limit): int
    {
        $filterOptions = array(
            'options' => array(
                'default' => 15,
                'min_range' => 1,
                'max_range' => 9999,
            ),
            'flags' => FILTER_NULL_ON_FAILURE,
        );
        return filter_var($limit, FILTER_VALIDATE_INT, $filterOptions);
    }

    /**
     * Check if we have a correct value for visibility
     */
    public static function visibility(string $visibility): string
    {
        $validArr = array(
            'public',
            'organization',
            'team',
            'user',
            'useronly',
        );

        if (!in_array($visibility, $validArr, true) && self::id((int) $visibility) === false) {
            throw new IllegalActionException('The visibility parameter is wrong.');
        }

        return $visibility;
    }

    /**
     * A target is like a subpart of a model
     * example: update the comment of an upload
     * todo: this will disappear in favor of better params classes handling the columns for each model
     */
    public static function target(string $target): string
    {
        $allowed = array(
            'all',
            'action',
            'blox_anon',
            'blox_enabled',
            'body',
            'bodyappend',
            'boundevent',
            'comment',
            'content_type',
            'date',
            'email',
            'deadline',
            'deadline_notif',
            'file',
            'finished',
            'finished_time',
            'firstname',
            'lastname',
            'list',
            'member',
            'metadata',
            'metadatafield',
            'notif_comment_created',
            'notif_comment_created_email',
            'notif_event_deleted',
            'notif_event_deleted_email',
            'notif_step_deadline',
            'notif_step_deadline_email',
            'notif_user_created',
            'notif_user_created_email',
            'notif_user_need_validation',
            'notif_user_need_validation_email',
            'privacypolicy',
            'rating',
            'real_name',
            'state',
            'title',
            'ts_authority',
            'ts_cert',
            'ts_bloxberg',
            'ts_classic',
            'ts_limit',
            'ts_login',
            'ts_password',
            'ts_url',
            'unreference',
            'uploadid',
            'userid',
            'validated',
            // no target is also valid
            '',
        );
        if (!in_array($target, $allowed, true)) {
            // TODO
            //throw new ImproperActionException('Invalid target!');
        }
        return $target;
    }

    /**
     * Check the cookie token
     */
    public static function token(string $token): string
    {
        if (mb_strlen($token) !== self::COOKIE_LENGTH) {
            throw new IllegalActionException('Invalid cookie!');
        }
        return Filter::sanitize($token);
    }

    public static function orcid(string $orcid): string
    {
        if (preg_match('/[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}/', $orcid) === 1) {
            return $orcid;
        }
        // note: the input field should prevent any incorrect value from being submitted in the first place
        throw new ImproperActionException('Incorrect value for orcid');
    }
}
