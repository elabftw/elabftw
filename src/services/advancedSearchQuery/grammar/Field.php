<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Services\AdvancedSearchQuery\Grammar;

use Elabftw\Services\AdvancedSearchQuery\Enums\Fields;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\FieldType;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Term;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitable;
use Elabftw\Services\AdvancedSearchQuery\Interfaces\Visitor;
use Elabftw\Services\AdvancedSearchQuery\Visitors\VisitorParameters;
use Override;

final class Field implements Term, Visitable, FieldType
{
    public function __construct(private string $field, private SimpleValueWrapper $valueWrapper, private ?bool $strict = null) {}

    #[Override]
    public function accept(Visitor $visitor, VisitorParameters $parameters): mixed
    {
        return $visitor->visitField($this, $parameters);
    }

    #[Override]
    public function getValue(): string
    {
        // body is stored as html after htmlPurifier worked on it
        // so '<', '>', '&' need to be converted to their htmlentities &lt;, &gt;, &amp;
        if (Fields::from($this->field) === Fields::Body) {
            return htmlspecialchars($this->valueWrapper->getValue(), ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        }
        return $this->valueWrapper->getValue();
    }

    #[Override]
    public function getFieldType(): Fields
    {
        return Fields::from($this->field);
    }

    public function getAffix(): string
    {
        return $this->strict ? '' : '%';
    }
}
