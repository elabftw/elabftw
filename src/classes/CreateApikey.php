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
use Elabftw\Interfaces\CreateApikeyParamsInterface;
use function random_bytes;

final class CreateApikey extends ContentParams implements CreateApikeyParamsInterface
{
    private int $canwrite;

    private string $key = '';

    public function __construct(string $content, string $target, int $canwrite)
    {
        parent::__construct($content, $target);
        $this->canwrite = $canwrite;
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
