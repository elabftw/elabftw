/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { ApiC } from './api';
import i18next from './i18n';
import type { Reaction, ReactionComponent } from './reaction';

type Calculated = { amount_mol: number | null; equivalents: number | null; yield_percent: number | null };

/** A reaction as an entry carries it: without its drawing, with has_preview and the calculated quantities, see Reactions::readSummaries() */
export type ReactionSummary = Omit<Reaction, 'scheme' | 'rxn' | 'preview_png' | 'components'> & {
  id: number;
  has_preview: number | boolean;
  // inline for a page opened with the access key of the entry, which cannot load it from the API, see AbstractEntityController::view()
  preview_png?: string;
  reaction_key?: string | null;
  components: Array<Omit<ReactionComponent, 'molfile'> & { actual: Calculated }>;
};

/** The translated labels of the reactions list, given by reactions.html */
interface ListLabels {
  // links to the experiments with the same reaction or component
  sameReaction: string;
  sameCompound: string;
  // the actions of the edit page: absent elsewhere
  load?: string;
  edit?: string;
  delete?: string;
}

// decimal columns come as strings with all their fraction digits
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
  if (classes.length > 0) {
    el.classList.add(...classes);
  }
  return el;
}

function icon(...classes: string[]): HTMLElement {
  const el = element('i', '', ['fas', 'fa-fw', ...classes]);
  el.setAttribute('aria-hidden', 'true');
  return el;
}

// the experiments listing with a reaction search
function searchUrl(params: Record<string, string>): string {
  return `experiments.php?${new URLSearchParams({ mode: 'show', ...params })}`;
}

function renderComponents(reaction: ReactionSummary, labels?: ListLabels): HTMLElement {
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
    const nameCell = row.insertCell();
    nameCell.textContent = name;
    if (labels && component.inchi_key) {
      const link = element('a');
      link.href = searchUrl({ reaction_structure: component.inchi_key, reaction_exact: '1' });
      link.title = labels.sameCompound;
      link.setAttribute('aria-label', labels.sameCompound);
      link.append(icon('fa-magnifying-glass'));
      nameCell.append(' ', link);
    }
    for (const text of [
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

function renderActions(labels: ListLabels): HTMLElement {
  const group = element('div', '', ['btn-group']);
  const button = (action: string, classes: string[], text = ''): HTMLButtonElement => {
    const btn = element('button', text, ['btn', 'btn-sm', ...classes]);
    btn.type = 'button';
    btn.dataset.reactionAction = action;
    return btn;
  };
  const remove = button('delete', ['btn-danger']);
  remove.setAttribute('aria-label', labels.delete);
  remove.title = labels.delete;
  remove.append(icon('fa-trash-alt'));
  group.append(button('load', ['btn-secondary'], labels.load), button('edit', ['btn-primary'], labels.edit), remove);
  return group;
}

/**
 * One reaction: its preview, conditions and components. With labels, the search links are added,
 * and with the labels of the actions, the buttons of the edit page (see chem-reactions.ts)
 */
function renderReaction(reaction: ReactionSummary, entityType: string, entityId: number, titleTag: 'h4' | 'h5', labels?: ListLabels): HTMLElement {
  const article = element('article', '', ['border', 'rounded', 'p-3', 'mb-3']);
  article.dataset.reactionId = String(reaction.id);
  const header = element('div', '', ['d-flex', 'align-items-center', 'justify-content-between']);
  header.append(element(titleTag, reaction.title));
  if (labels?.load) {
    header.append(renderActions(labels));
  }
  article.append(header);
  if (reaction.preview_png || reaction.has_preview) {
    const img = element('img', '', ['img-fluid', 'mb-2']);
    img.alt = i18next.t('reaction-scheme');
    img.loading = 'lazy';
    // the entry only has summaries: the preview is served on its own
    img.src = reaction.preview_png
      ? `data:image/png;base64,${reaction.preview_png}`
      : `/api/v2/${entityType}/${entityId}/reactions/${reaction.id}?format=binary`;
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
    // keep the line breaks of the notes
    notes.style.whiteSpace = 'pre-line';
    article.append(notes);
  }
  if (labels && reaction.reaction_key) {
    const link = element('a');
    link.href = searchUrl({ reaction_key: reaction.reaction_key });
    link.append(icon('fa-magnifying-glass', 'mr-1'), labels.sameReaction);
    const p = element('p');
    p.append(link);
    article.append(p);
  }
  article.append(renderComponents(reaction, labels));
  return article;
}

/** The reactions of an entry in the listing, when its body is expanded: without the actions and search links */
export function renderReactionSummaries(reactions: ReactionSummary[], entityType: string, entityId: number): HTMLElement {
  const section = element('section', '', ['mt-3']);
  section.setAttribute('aria-label', i18next.t('reactions'));
  section.append(element('h4', i18next.t('reactions')));
  section.append(...reactions.map(reaction => renderReaction(reaction, entityType, entityId, 'h5')));
  return section;
}

function renderReactionsList(list: HTMLElement, reactions: ReactionSummary[]): void {
  const { entityType, entityId, labelSameReaction, labelSameCompound, labelLoad, labelEdit, labelDelete } = list.dataset;
  const labels: ListLabels = { sameReaction: labelSameReaction, sameCompound: labelSameCompound, load: labelLoad, edit: labelEdit, delete: labelDelete };
  list.replaceChildren(...reactions.map(reaction => renderReaction(reaction, entityType, Number(entityId), 'h4', labels)));
}

/** The reactions of the entry on its view and edit pages: they come with the page, see reactions.html */
export function initReactionsList(): void {
  const list = document.getElementById('reactionsList');
  if (list?.dataset.reactions) {
    renderReactionsList(list, JSON.parse(list.dataset.reactions));
  }
}

/** After a change: the entry gives the current summaries of its reactions */
export async function reloadReactionsList(): Promise<void> {
  const list = document.getElementById('reactionsList');
  if (!list) {
    return;
  }
  const entry = await ApiC.getJson<{ reactions: ReactionSummary[] }>(`${list.dataset.entityType}/${list.dataset.entityId}`);
  renderReactionsList(list, entry.reactions);
}
