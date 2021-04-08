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

class ContentParams
{
    protected const MIN_CONTENT_SIZE = 2;

    protected string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }
}
