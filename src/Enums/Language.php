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

enum Language: string
{
    case Catalan = 'ca_ES';
    case Czech = 'cs_CZ';
    case German = 'de_DE';
    case EnglishGB = 'en_GB';
    case EnglishUS = 'en_US';
    case Spanish = 'es_ES';
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
    case Chinese = 'zh_CN';

    public static function getAllHuman(): array
    {
        // we use alternative syntax instead of 'self::from' here because
        // https://github.com/phpstan/phpstan/issues/4376
        $all = array_map(array(__CLASS__, 'from'), self::getAssociativeArray());
        return array_map(array(__CLASS__, 'toHuman'), $all);
    }

    public function toCalendar(): string
    {
        return match ($this) {
            $this::Catalan => 'ca',
            $this::Czech => 'cs',
            $this::German => 'de',
            $this::EnglishGB => 'en-gb',
            $this::EnglishUS => 'en-us',
            $this::Spanish => 'es',
            $this::French => 'fr',
            $this::Indonesian => 'id',
            $this::Italian => 'it',
            $this::Japanese => 'ja',
            $this::Korean => 'ko',
            $this::Dutch => 'nl',
            $this::Polish => 'pl',
            $this::PortugueseBrazilian => 'pt-br',
            $this::Portuguese => 'pt',
            $this::Russian => 'ru',
            $this::Slovenian => 'sl',
            $this::Slovak => 'sk',
            $this::Chinese => 'zh-cn',
        };
    }

    private static function toHuman(self $value): string
    {
        return match ($value) {
            Language::Catalan => 'Spanish (Catalan)',
            Language::Czech => 'Czech ',
            Language::German => 'German',
            Language::EnglishGB => 'English (UK/GB)',
            Language::EnglishUS => 'English (US)',
            Language::Spanish => 'Spanish',
            Language::French => 'French',
            Language::Indonesian => 'Indonesian',
            Language::Italian => 'Italian',
            Language::Japanese => 'Japanese',
            Language::Korean => 'Korean',
            Language::Dutch => 'Dutch',
            Language::Polish => 'Polish',
            Language::PortugueseBrazilian => 'Portuguese (Brazilian)',
            Language::Portuguese => 'Portuguese',
            Language::Russian => 'Russian',
            Language::Slovenian => 'Slovenian',
            Language::Slovak => 'Slovak',
            Language::Chinese => 'Chinese Simplified',
        };
    }

    private static function getAssociativeArray(): array
    {
        return array_combine(self::values(), self::values());
    }

    private static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
