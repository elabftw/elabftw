<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Interfaces\Term;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Override;

final class MetadataField implements Term, Visitable
{
    public function __construct(private SimpleValueWrapper $keyWrapper, private SimpleValueWrapper $valueWrapper, private ?bool $strict = null) {}

    #[Override]
    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitMetadataField($this, $parameters);
    }

    #[Override]
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
