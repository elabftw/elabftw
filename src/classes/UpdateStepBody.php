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

use Elabftw\Interfaces\UpdateParamsInterface;
use Elabftw\Services\Filter;

final class UpdateStepBody extends UpdateStep implements UpdateParamsInterface
{
    private string $content;

    public function __construct(int $id, string $content)
    {
        parent::__construct($id);
        $this->content = $content;
        $this->target = 'body';
    }

    public function getContent(): string
    {
        return Filter::sanitize($this->content);
    }
}
