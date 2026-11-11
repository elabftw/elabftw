/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import type { Reaction, ReactionComponent } from './reaction';

type Calculated = { amount_mol: number | null; equivalents: number | null; yield_percent: number | null };

/** A reaction as an entry carries it: without its drawing, with has_preview and the calculated quantities, see Reactions::readSummaries() */
export type ReactionSummary = Omit<Reaction, 'scheme' | 'rxn' | 'preview_png' | 'components'> & {
  id: number;
  has_preview: number | boolean;
  components: Array<Omit<ReactionComponent, 'molfile'> & { actual: Calculated }>;
};

// decimal columns come as strings with all their fraction digits: show them like reaction-summary.html does
const formatNumber = (value: string | number | null, digits?: number): string => {
  if (value === null || value === '') {
    return '';
  }
  const number = Number(value);
  return String(digits === undefined ? number : Number(number.toFixed(digits)));
};

const formatQuantity = (value: string | number | null, unit: string): string => value === null || value === '' ? '' : `${formatNumber(value)} ${unit.replace('uL', 'µL')}`;

function element<K extends keyof HTMLElementTagNameMap>(tag: K, text = '', classes: string[] = []): HTMLElementTagNameMap[K] {
  const el = document.createElement(tag);
  el.textContent = text;
  el.classList.add(...classes);
  return el;
}

function renderComponents(reaction: ReactionSummary): HTMLElement {
  const wrapper = element('div', '', ['table-responsive']);
  const table = element('table', '', ['table', 'table-sm']);
  const headRow = table.createTHead().insertRow();
  for (const label of [
    i18next.t('reaction-component'), i18next.t('reaction-role'), `${i18next.t('reaction-molar-mass')} (g/mol)`, i18next.t('reaction-planned'),
    i18next.t('reaction-actual'), `${i18next.t('reaction-amount')} (mmol)`, i18next.t('reaction-equivalents'), `${i18next.t('reaction-isolated-yield')} (%)`,
  ]) {
    const th = element('th', label);
    th.scope = 'col';
    headRow.append(th);
  }
  const body = table.createTBody();
  for (const component of reaction.components) {
    let name = component.name || component.molecular_formula || component.smiles;
    if (component.component_key === reaction.reference_component_key) {
      name += ' (1 eq)';
    }
    if (component.component_key === reaction.yield_basis_component_key) {
      name += ` (${i18next.t('reaction-yield-basis')})`;
    }
    const actual = component.actual;
    const row = body.insertRow();
    for (const text of [
      name,
      i18next.t(`reaction-role-${component.role}`),
      formatNumber(component.molecular_weight),
      formatQuantity(component.planned_value, component.planned_unit),
      formatQuantity(component.actual_value, component.actual_unit),
      actual.amount_mol === null ? '' : formatNumber(actual.amount_mol * 1000, 5),
      formatNumber(actual.equivalents, 3),
      formatNumber(actual.yield_percent, 2),
    ]) {
      row.insertCell().textContent = text;
    }
  }
  wrapper.append(table);
  return wrapper;
}

/** The reactions of an entry, like reactions.html displays them in view mode, without the actions and search links */
export function renderReactionSummaries(reactions: ReactionSummary[], entityType: string, entityId: number): HTMLElement {
  const section = element('section', '', ['mt-3']);
  section.setAttribute('aria-label', i18next.t('reactions'));
  section.append(element('h4', i18next.t('reactions')));
  for (const reaction of reactions) {
    const article = element('article', '', ['border', 'rounded', 'p-3', 'mb-3']);
    article.append(element('h5', reaction.title));
    if (reaction.has_preview) {
      const img = element('img', '', ['img-fluid', 'mb-2']);
      img.alt = i18next.t('reaction-scheme');
      img.loading = 'lazy';
      // the entry only has summaries: the preview is served on its own
      img.src = `/api/v2/${entityType}/${entityId}/reactions/${reaction.id}?format=binary`;
      article.append(img);
    }
    const conditions = [
      reaction.temperature_c === null ? '' : `${formatNumber(reaction.temperature_c)} °C`,
      reaction.duration_s === null ? '' : `${formatNumber(reaction.duration_s)} s`,
      reaction.pressure_pa === null ? '' : `${formatNumber(reaction.pressure_pa)} Pa (${i18next.t('reaction-absolute')})`,
      reaction.atmosphere,
    ].filter(Boolean).join('; ');
    if (conditions) {
      article.append(element('p', conditions));
    }
    if (reaction.conditions_notes) {
      const notes = element('p', reaction.conditions_notes);
      // like nl2br in the template
      notes.style.whiteSpace = 'pre-line';
      article.append(notes);
    }
    article.append(renderComponents(reaction));
    section.append(article);
  }
  return section;
}
