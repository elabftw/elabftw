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
use Elabftw\Services\Filter;
use const SECRET_KEY;
use function str_contains;

class ContentParams implements ContentParamsInterface
{
    protected const MIN_CONTENT_SIZE = 1;

    public function __construct(protected string $content = '', protected string $target = '', protected ?array $extra = null)
    {
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getContent(): string
    {
        // if we're dealing with a password, return the encrypted content
        if (str_contains($this->target, '_password')) {
            return Crypto::encrypt($this->content, Key::loadFromAsciiSafeString(SECRET_KEY));
        }

        // check for length
        $c = Filter::sanitize($this->content);
        if (mb_strlen($c) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        return $c;
    }

    public function getBody(): string
    {
        return Filter::body($this->content);
    }

    public function getExtra(string $key): string
    {
        return Filter::sanitize($this->extra[$key] ?? '');
    }
}
