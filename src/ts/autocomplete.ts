/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Autocomplete, { countGraphemes } from './Autocomplete.class';
import type { AutocompleteOptions } from './Autocomplete.class';
import { ApiC } from './api';
import { EntityType, Model } from './interfaces';
import i18next from './i18n';
import { escapeExtendedQuery } from './misc';

const sources = {
  tags: async (term: string) => (await ApiC.getJson(`${Model.Team}/current/${Model.Tag}`, { q: term })).map(tag => tag.tag),
  compounds: async (term: string) => (await ApiC.getJson(Model.Compounds, { q: term })).map(compound => `${compound.id} - ${compound.name}`),
  extraFieldsKeys: async (term: string) => (await ApiC.getJson(`${Model.ExtraFieldsKeys}/`, { q: term })).map(entry => entry.extra_fields_key),
};

const links = {
  addLinkItemsInput: { filterId: 'addLinkCatFilter', target: EntityType.Item, parameter: 'category' },
  addLinkExpInput: { filterId: 'addLinkOwnerFilter', target: EntityType.Experiment, parameter: 'owner' },
};

const formatEntity = entity => {
  const category = entity.category_title ? `${entity.category_title} - ` : '';
  const status = entity.status_title ? `${entity.status_title} - ` : '';
  return `${entity.id} - ${category}${status}${entity.title}`;
};

const formatters = {
  users: user => `${user.userid} - ${user.fullname} (${user.email})`,
  compounds: compound => `${compound.id} - ${compound.name} ${compound.cas_number ? ` (CAS: ${compound.cas_number})` : ''}`,
};

function recordOptions(input: HTMLInputElement): AutocompleteOptions {
  const { target, team, identifier } = input.dataset;
  const filter = team ? input.ownerDocument.getElementById(team) as HTMLSelectElement : null;
  const format = formatters[target] ?? formatEntity;
  return {
    appendTo: identifier ? input.ownerDocument.getElementById(`autocompleteAnchorDiv_${identifier}`) : null,
    dependencies: filter ? [filter] : [],
    source: async searchTerm => {
      const term = searchTerm.trim();
      // East/Southeast Asian scripts can be searched with a single grapheme.
      const shortScript = /\p{Script=Han}|\p{Script=Hiragana}|\p{Script=Katakana}|\p{Script=Hangul}|\p{Script=Thai}|\p{Script=Lao}|\p{Script=Khmer}|\p{Script=Myanmar}|\p{Script=Bopomofo}/u.test(term);
      if (!shortScript && countGraphemes(term) < 3) {
        return [{ label: i18next.t('type-3-chars'), disabled: true }];
      }
      const params = {
        q: target === EntityType.Experiment || target === EntityType.Item ? escapeExtendedQuery(term) : term,
        ...(filter?.value ? { team: filter.value } : {}),
      };
      const results = await ApiC.getJson(`${target}/`, params);
      return Array.isArray(results) && results.length
        ? results.map(format)
        : [{ label: i18next.t('not-found'), disabled: true }];
    },
  };
}

function linkOptions(input: HTMLInputElement): AutocompleteOptions | undefined {
  const { filterId, target, parameter } = links[input.id];
  const filter = input.ownerDocument.getElementById(filterId) as HTMLSelectElement | null;
  if (!filter) return;
  const cache = new Map<string, string[]>();
  return {
    dependencies: [filter],
    source: async term => {
      // A late response must stay associated with its original category/owner.
      const key = JSON.stringify([filter.value, term]);
      const cached = cache.get(key);
      if (cached) return cached;
      const results = await ApiC.getJson(`${target}/`, {
        [parameter]: filter.value,
        fastq: escapeExtendedQuery(term),
        scope: '3',
      });
      const labels = results.map(entity => {
        const customId = entity.custom_id ? `${entity.custom_id} - ` : '';
        return formatEntity({ ...entity, title: `${customId}${entity.title.substring(0, 60)}` });
      });
      cache.set(key, labels);
      return labels;
    },
  };
}

/** Initialize once per page. Focus also covers inputs inserted by fragment reloads. */
export function initAutocompletes(root: HTMLElement): void {
  const selector = 'input[data-action="autocomplete"], input[data-autocomplete], #addLinkItemsInput, #addLinkExpInput';
  const attach = (input: HTMLInputElement) => {
    if (Autocomplete.get(input)) return;
    let options: AutocompleteOptions | undefined;
    if (Object.hasOwn(links, input.id)) {
      options = linkOptions(input);
    } else if (input.dataset.action === 'autocomplete') {
      options = recordOptions(input);
    } else if (Object.hasOwn(sources, input.dataset.autocomplete)) {
      options = {
        source: sources[input.dataset.autocomplete],
        appendTo: input.dataset.autocomplete === 'extraFieldsKeys'
          ? input.ownerDocument.getElementById('autocompleteAnchorDiv_extra_fields_keys') : null,
      };
    }
    if (options) Autocomplete.attach(input, options);
  };

  root.querySelectorAll<HTMLInputElement>(selector).forEach(attach);
  root.addEventListener('focusin', event => {
    const input = event.target;
    if (input instanceof HTMLInputElement && input.matches(selector)) attach(input);
  });
}
