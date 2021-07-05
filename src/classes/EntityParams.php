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

use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Filter;

class EntityParams extends ContentParams implements EntityParamsInterface
{
    public function __construct(string $content, string $target = '', protected ?array $extra = null)
    {
        parent::__construct($content, $target);
    }

    public function getTitle(): string
    {
        return Filter::title($this->content);
    }

    public function getDate(): string
    {
        return Filter::kdate($this->content);
    }

    public function getBody(): string
    {
        return Filter::body($this->content);
    }

    public function getExtraBody(): string
    {
        return Filter::body($this->extra['body'] ?? '');
    }

    public function getRating(): int
    {
        return (int) $this->content;
    }

    public function getMetadata(): string
    {
        return $this->content;
    }

    public function getField(): string
    {
        return Filter::sanitize($this->extra['jsonField'] ?? '');
    }

    public function getUserId(): int
    {
        return (int) $this->content;
    }
}
