<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Interfaces\EntityParamsInterface;
use Elabftw\Services\Filter;
use function in_array;
use const JSON_HEX_APOS;
use const JSON_THROW_ON_ERROR;

class EntityParams extends ContentParams implements EntityParamsInterface
{
    public function __construct(string $content, string $target = '', ?array $extra = null)
    {
        parent::__construct($content, $target, $extra);
    }

    public function getTitle(): string
    {
        return Filter::title($this->content);
    }

    public function getTags(): array
    {
        return $this->extra['tags'] ?? array();
    }

    public function getDate(): string
    {
        return $this->content;
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
        return json_encode($this->extra['jsonField'] ?? '', JSON_HEX_APOS | JSON_THROW_ON_ERROR);
    }

    public function getUserId(): int
    {
        return (int) $this->content;
    }

    public function getState(): int
    {
        $state = (int) $this->content;
        // TODO in php 8.1, we will use an enum for this
        if (!in_array($state, array(1, 2, 3), true)) {
            return 1;
        }
        return $state;
    }
}
