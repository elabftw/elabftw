<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

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

    public static function toHuman(self $case): string
    {
        return match ($case) {
            PasswordComplexity::None => _('Minimum password length'),
            PasswordComplexity::Weak => _('Must have at least one upper and one lower case letter'),
            PasswordComplexity::Medium => _('Must have at least one digit, one upper and one lower case letter'),
            PasswordComplexity::Strong => _('Must have at least one special character, one digit, one upper and one lower case letter'),
        };
    }

    public static function toPattern(self $case): string
    {
        return match ($case) {
            PasswordComplexity::None => '.*',
            PasswordComplexity::Weak => '^(?=.*[a-z])(?=.*[A-Z]).*$',
            PasswordComplexity::Medium => '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$',
            PasswordComplexity::Strong => '^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).*$',
        };
    }

    /**
     * For php, we need to add / as pre+suffix
     * @return non-empty-string
     */
    public static function toPhPattern(self $case): string
    {
        return '/' . $case::toPattern($case) . '/';
    }

    public static function getAssociativeArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            // we use alternative syntax instead of 'self::toHuman' here because
            // https://github.com/phpstan/phpstan/issues/4376
            array_map(array(__CLASS__, 'toHuman'), self::cases()),
        );
    }
}
