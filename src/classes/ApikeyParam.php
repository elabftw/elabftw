<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use function bin2hex;
use function random_bytes;

final class ApikeyParam extends ContentParams
{
    private string $key = '';

    public function __construct(protected string $content, public int $canwrite = 0)
    {
        parent::__construct('', $content);
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
