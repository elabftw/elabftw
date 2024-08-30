/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import caES from './langs/ca_ES';
import csCZ from './langs/cs_CZ';
import deDE from './langs/de_DE';
import enGB from './langs/en_GB';
import esES from './langs/es_ES';
import fiFI from './langs/fi_FI';
import frFR from './langs/fr_FR';
import idID from './langs/id_ID';
import itIT from './langs/it_IT';
import jaJP from './langs/ja_JP';
import koKR from './langs/ko_KR';
import nlBE from './langs/nl_BE';
import plPL from './langs/pl_PL';
import ptBR from './langs/pt_BR';
import ptPT from './langs/pt_PT';
import ruRU from './langs/ru_RU';
import slSI from './langs/sl_SI';
import skSK from './langs/sk_SK';
import zhCN from './langs/zh_CN';

i18next.init({
  lng: 'en_GB',
  supportedLngs: [
    'ca_ES',
    'cs_CZ',
    'de_DE',
    'en_GB',
    'es_ES',
    'fi_FI',
    'fr_FR',
    'id_ID',
    'it_IT',
    'ja_JP',
    'ko_KR',
    'nl_BE',
    'pl_PL',
    'pt_BR',
    'pt_PT',
    'ru_RU',
    'sl_SI',
    'sk_SK',
    'zh_CN',
  ],
  fallbackLng: 'en_GB',
  resources: {
    ca_ES: caES,
    cs_CZ: csCZ,
    de_DE: deDE,
    en_GB: enGB,
    es_ES: esES,
    fi_FI: fiFI,
    fr_FR: frFR,
    id_ID: idID,
    it_IT: itIT,
    ja_JP: jaJP,
    ko_KR: koKR,
    nl_BE: nlBE,
    pl_PL: plPL,
    pt_BR: ptBR,
    pt_PT: ptPT,
    ru_RU: ruRU,
    sk_SK: skSK,
    sl_SI: slSI,
    zh_CN: zhCN,
  },
});
