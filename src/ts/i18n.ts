/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import t_ca_ES from './langs/ca_ES';
import t_de_DE from './langs/de_DE';
import t_en_GB from './langs/en_GB';
import t_es_ES from './langs/es_ES';
import t_fr_FR from './langs/fr_FR';
import t_id_ID from './langs/id_ID';
import t_it_IT from './langs/it_IT';
import t_ja_JP from './langs/ja_JP';
import t_ko_KR from './langs/ko_KR';
import t_nl_BE from './langs/nl_BE';
import t_pl_PL from './langs/pl_PL';
import t_pt_BR from './langs/pt_BR';
import t_pt_PT from './langs/pt_PT';
import t_ru_RU from './langs/ru_RU';
import t_sl_SI from './langs/sl_SI';
import t_sk_SK from './langs/sk_SK';
import t_zh_CN from './langs/zh_CN';

i18next.init({
  lng: 'en_GB',
  supportedLngs: [
    'ca_ES',
    'de_DE',
    'en_GB',
    'es_ES',
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
    'zh_CN'
  ],
  fallbackLng: 'en_GB',
  resources: {
    ca_ES: t_ca_ES,
    de_DE: t_de_DE,
    en_GB: t_en_GB,
    es_ES: t_es_ES,
    fr_FR: t_fr_FR,
    id_ID: t_id_ID,
    it_IT: t_it_IT,
    ja_JP: t_ja_JP,
    ko_KR: t_ko_KR,
    nl_BE: t_nl_BE,
    pl_PL: t_pl_PL,
    pt_BR: t_pt_BR,
    pt_PT: t_pt_PT,
    ru_RU: t_ru_RU,
    sk_SK: t_sk_SK,
    sl_SI: t_sl_SI,
    zh_CN: t_zh_CN,
  },
});
