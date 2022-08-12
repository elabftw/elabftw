<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\ContentParamsInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use const SECRET_KEY;

class ContentParams implements ContentParamsInterface
{
    protected const MIN_CONTENT_SIZE = 1;

    public function __construct(protected string $target, protected string $content)
    {
    }

    public function getUnfilteredContent(): string
    {
        return $this->content;
    }

    // maybe rename to something else, so we have getContent to get filtered content and this would be get nonemptystring
    public function getContent(): mixed
    {
        // check for length
        $c = Filter::sanitize($this->content);
        if (mb_strlen($c) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), self::MIN_CONTENT_SIZE));
        }
        return $c;
    }

    public function getColumn(): string
    {
        return $this->getTarget();
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    protected function getBody(): string
    {
        return Filter::body($this->content);
    }

    protected function getBinary(): int
    {
        return Filter::toBinary($this->content);
    }

    /*
    protected function filter(): mixed
    {
        return match ($this->target) {
            // simple strings
            'orgid', 'link_name', 'name', 'comment' => $this->getContent(),
            // from tinymce/html
            'common_template', 'body' => Filter::body($this->content),
            'deletable_xp', 'deletable_item', 'user_create_tag', 'force_exp_tpl', 'public_db', 'do_force_canread', 'do_force_canwrite', 'visible', 'notif_comment_created', 'notif_comment_created_email', 'notif_user_created', 'notif_user_created_email', 'notif_user_need_validation', 'notif_user_need_validation_email', 'notif_step_deadline', 'notif_step_deadline_email', 'notif_event_deleted', 'notif_event_deleted_email' => $this->getInt(),
            'link_href' => $this->getUrl(),
            'force_canread', 'force_canwrite' => Check::visibility($this->content),
            // if we're dealing with a password, return the encrypted content
            'smtp_password', 'ts_password', 'ldap_password' => Crypto::encrypt($this->content, Key::loadFromAsciiSafeString(SECRET_KEY)),
            'query' => $this->getContent(),
            'category' => (int) $this->getUnfilteredContent(),
            default => throw new ImproperActionException('Invalid parameter sent.'),
        };
    }
     */

    protected function getInt(): int
    {
        return (int) $this->content;
    }

    protected function getUrl(): string
    {
        if (filter_var($this->content, FILTER_VALIDATE_URL) === false) {
            throw new ImproperActionException('Invalid URL format.');
        }
        return $this->content;
    }
}
