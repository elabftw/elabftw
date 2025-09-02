<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Enums;

use function array_map;

enum Language: string
{
    // by alphabetical order of value
    case Catalan = 'ca_ES';
    case Czech = 'cs_CZ';
    case German = 'de_DE';
    case Greek = 'el_GR';
    case EnglishGB = 'en_GB';
    case EnglishUS = 'en_US';
    case Spanish = 'es_ES';
    case Estonian = 'et_EE';
    case Finnish = 'fi_FI';
    case French = 'fr_FR';
    case Indonesian = 'id_ID';
    case Italian = 'it_IT';
    case Japanese = 'ja_JP';
    case Korean = 'ko_KR';
    case Dutch = 'nl_BE';
    case Polish = 'pl_PL';
    case PortugueseBrazilian = 'pt_BR';
    case Portuguese = 'pt_PT';
    case Russian = 'ru_RU';
    case Slovenian = 'sl_SI';
    case Slovak = 'sk_SK';
    case Uzbek = 'uz_UZ';
    case Chinese = 'zh_CN';

    public static function getAllHuman(): array
    {
        return array_combine(
            array_map(fn($case) => $case->value, self::cases()),
            array_map(fn($case) => $case->toHuman(), self::cases())
        );
    }

    public function toCalendar(): string
    {
        return match ($this) {
            self::Catalan => 'ca',
            self::Czech => 'cs',
            self::German => 'de',
            self::Greek => 'el',
            self::EnglishGB => 'en-gb',
            self::EnglishUS => 'en-us',
            self::Spanish => 'es',
            self::Estonian => 'et',
            self::Finnish => 'fi',
            self::French => 'fr',
            self::Indonesian => 'id',
            self::Italian => 'it',
            self::Japanese => 'ja',
            self::Korean => 'ko',
            self::Dutch => 'nl',
            self::Polish => 'pl',
            self::PortugueseBrazilian => 'pt-br',
            self::Portuguese => 'pt',
            self::Russian => 'ru',
            self::Slovenian => 'sl',
            self::Slovak => 'sk',
            self::Uzbek => 'uz',
            self::Chinese => 'zh-cn',
        };
    }

    public function toHuman(): string
    {
        return match ($this) {
            self::Catalan => 'Spanish (Catalan)',
            self::Czech => 'Czech ',
            self::German => 'German',
            self::Greek => 'Greek',
            self::EnglishGB => 'English (UK/GB)',
            self::EnglishUS => 'English (US)',
            self::Spanish => 'Spanish',
            self::Estonian => 'Estonian',
            self::Finnish => 'Finnish',
            self::French => 'French',
            self::Indonesian => 'Indonesian',
            self::Italian => 'Italian',
            self::Japanese => 'Japanese',
            self::Korean => 'Korean',
            self::Dutch => 'Dutch',
            self::Polish => 'Polish',
            self::PortugueseBrazilian => 'Portuguese (Brazilian)',
            self::Portuguese => 'Portuguese',
            self::Russian => 'Russian',
            self::Slovenian => 'Slovenian',
            self::Slovak => 'Slovak',
            self::Uzbek => 'Uzbek',
            self::Chinese => 'Chinese Simplified',
        };
    }
}
