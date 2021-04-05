<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function bin2hex;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CreateApikeyParamsInterface;
use function mb_strlen;
use function random_bytes;

final class CreateApikey implements CreateApikeyParamsInterface
{
    private const MIN_CONTENT_SIZE = 2;

    public string $action;

    private string $content;

    private int $canwrite;

    private string $key = '';

    public function __construct(string $content, int $canwrite)
    {
        $this->content = $content;
        $this->action = 'create';
        $this->canwrite = $canwrite;
    }

    public function getContent(): string
    {
        // check for length
        if (mb_strlen($this->content) < self::MIN_CONTENT_SIZE) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        return $this->content;
    }

    public function getCanwrite(): int
    {
        return $this->canwrite;
    }

    public function getKey(): string
    {
        if (empty($this->key)) {
            $key = bin2hex(random_bytes(42));
            // keep it in the object so we can display it to the user after
            $this->key = $key;
        }
        return $this->key;
    }
}
