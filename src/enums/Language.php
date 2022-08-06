<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Enums;

enum Language: string
{
    case Catalan = 'ca_ES';
    case German = 'de_DE';
    case English = 'en_GB';
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
}
