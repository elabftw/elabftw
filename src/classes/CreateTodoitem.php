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

use Elabftw\Interfaces\CreateTodoitemParamsInterface;
use Elabftw\Services\Filter;

final class CreateTodoitem implements CreateTodoitemParamsInterface
{
    public string $action;

    private string $content;

    public function __construct(string $content)
    {
        $this->content = $content;
        $this->action = 'create';
    }

    public function getContent(): string
    {
        return Filter::sanitize($this->content);
    }
}
