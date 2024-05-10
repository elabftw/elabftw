<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use function array_column;
use function array_combine;
use function array_map;

enum PasswordComplexity: int
{
    case None = 0;
    case Weak = 10;
    case Medium = 20;
    case Strong = 30;

    public function toHuman(): string
    {
        return match ($this) {
            self::None => _('Minimum password length'),
            self::Weak => _('Must have at least one upper and one lower case letter, if your alphabet allows'),
            self::Medium => _('Must have at least one upper and one lower case letter, if your alphabet allows, and one digit'),
            self::Strong => _('Must have at least one upper and one lower case letter, if your alphabet allows, one special character, and one digit'),
        };
    }

    public function toPattern(): string
    {
        // we need Lo for unicase/unicameral alphabets like Chinese, Japanese, and Korean
        $letters = '(?:(?=.*\p{Ll})(?=.*\p{Lu})|(?=.*\p{Lo}))';
        $digits = '(?=.*\d)';
        return match ($this) {
            self::None => '.*',
            self::Weak => "^$letters.*$",
            self::Medium => "^$letters$digits.*$",
            self::Strong => "^$letters$digits(?=.*[\p{P}\p{S}]).*$",
        };
    }

    /**
     * For php, we need to add / as pre+suffix
     * @return non-empty-string
     */
    public function toPhPattern(): string
    {
        return '/' . $this->toPattern() . '/u';
    }

    public static function getAssociativeArray(): array
    {
        $cases = self::cases();
        $values = array_column($cases, 'value');
        $descriptions = array_map(
            fn(self $case): string => $case->toHuman(),
            $cases
        );

        return array_combine($values, $descriptions);
    }
}
