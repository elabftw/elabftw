<?php declare(strict_types=1);
/**
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Term;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;

class MetadataField implements Term, Visitable
{
    public function __construct(private SimpleValueWrapper $keyWrapper, private SimpleValueWrapper $valueWrapper, private ?bool $strict = null)
    {
    }

    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitMetadataField($this, $parameters);
    }

    public function getValue(): string
    {
        return $this->valueWrapper->getValue();
    }

    public function getKey(): string
    {
        return $this->keyWrapper->getValue();
    }

    public function getAffix(): string
    {
        return $this->strict ? '' : '%';
    }
}
