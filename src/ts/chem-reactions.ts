/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { ChemicalMimeType } from 'ketcher-core';
import type { Ketcher } from 'ketcher-core';
import Autocomplete from './Autocomplete.class';
import { ApiC } from './api';
import { loadChemDrawing } from './chem-drawing';
import { ensureTogglableSectionIsOpen, reloadElements } from './misc';
import { notify } from './notify';
import { emptyComponent, quantityUnits, reactionFromKetcher, replaceReactionDrawing } from './reaction';
import type { Reaction, ReactionComponent } from './reaction';

type Choice = { id: number; name?: string; title?: string; smiles?: string; inchi_key?: string; molecular_formula?: string; molecular_weight?: number; cas_number?: string };
type Calculated = { amount_mol: number | null; mass_g: number | null; volume_ml: number | null; equivalents: number | null; yield_percent: number | null };
type CalculatedComponent = ReactionComponent & { planned: Calculated; actual: Calculated };

export function initChemReactions(ketcher: Ketcher): void {
  const form = document.getElementById('reactionForm') as HTMLFormElement | null;
  if (!form || form.dataset.initialized) return;
  form.dataset.initialized = '1';
  const create = document.getElementById('chemCreateReaction') as HTMLButtonElement;
  const section = document.getElementById('reactionsSection');
  const rows = document.getElementById('reactionComponents');
  const template = document.getElementById('reactionComponentTemplate') as HTMLTemplateElement;
  const modal = $('#reactionModal');
  const error = document.getElementById('reactionError');
  const preview = document.getElementById('reactionPreview') as HTMLImageElement;
  const endpoint = form.dataset.endpoint;
  const field = (name: string) => form.elements.namedItem(name) as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;
  const formFields = ['title', 'temperature_c', 'duration_s', 'pressure_pa', 'atmosphere', 'conditions_notes'];
  let reaction: Reaction;
  let busy = false;
  let dirty = false;
  let calculationRequest = 0;
  let calculationTimer: ReturnType<typeof setTimeout>;

  const showError = (value: unknown): void => {
    error.textContent = value instanceof Error ? value.message : String(value);
    error.hidden = false;
  };
  const setBusy = (value: boolean): void => {
    busy = value;
    create.disabled = value;
    form.querySelectorAll<HTMLInputElement | HTMLButtonElement | HTMLSelectElement | HTMLTextAreaElement>('input, button, select, textarea')
      .forEach(control => control.disabled = value);
  };
  const collect = (): Reaction => {
    for (const name of formFields) Object.assign(reaction, { [name]: field(name).value || (field(name).type === 'number' ? null : '') });
    reaction.reference_component_key = field('reference_component_key').value || null;
    reaction.yield_basis_component_key = field('yield_basis_component_key').value || null;
    rows.querySelectorAll<HTMLTableRowElement>('tr[data-component-key]').forEach(row => {
      const component = reaction.components.find(component => component.component_key === row.dataset.componentKey);
      row.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[data-field]').forEach(input => {
        Object.assign(component, { [input.dataset.field]: input.value || (input.type === 'number' ? null : '') });
      });
      component.planned_basis = quantityUnits[component.planned_unit];
      component.actual_basis = quantityUnits[component.actual_unit];
    });
    return reaction;
  };
  const referenceOptions = (): void => {
    for (const name of ['reference_component_key', 'yield_basis_component_key']) {
      const select = field(name) as HTMLSelectElement;
      const value = reaction[name] || '';
      select.replaceChildren(new Option('', ''), ...reaction.components
        .filter(component => ['reactant', 'reagent'].includes(component.role))
        .map(component => new Option(component.name || component.molecular_formula || component.smiles, component.component_key)));
      select.value = value;
    }
  };
  const format = (value: number | null, unit: string): string => value === null ? '' : Number(value.toPrecision(6)).toLocaleString() + ' ' + unit;
  const calculate = (): void => {
    clearTimeout(calculationTimer);
    const request = ++calculationRequest;
    rows.querySelectorAll('output').forEach(output => output.textContent = '');
    calculationTimer = setTimeout(async () => {
      if (busy || !reaction) return;
      const draft = collect();
      try {
        const response = await ApiC.patch(endpoint, {
          action: 'calculate', components: draft.components,
          reference_component_key: draft.reference_component_key, yield_basis_component_key: draft.yield_basis_component_key,
          notifOnSaved: 0, notifOnError: 0,
        });
        const result: { components: CalculatedComponent[] } = await response.json();
        if (request !== calculationRequest || !modal.hasClass('show')) return;
        for (const component of result.components) {
          const row = rows.querySelector(`[data-component-key="${CSS.escape(component.component_key)}"]`);
          for (const phase of ['planned', 'actual']) {
            const value: Calculated = component[phase];
            row.querySelector(`[data-result="${phase}"]`).textContent = [
              format(value.mass_g, 'g'), format(value.volume_ml, 'mL'), format(value.amount_mol === null ? null : value.amount_mol * 1000, 'mmol'),
              format(value.equivalents, 'eq'), format(value.yield_percent, '%'),
            ].filter(Boolean).join('; ');
          }
        }
        error.hidden = true;
      } catch (value) {
        if (request === calculationRequest) showError(value);
      }
    }, 300);
  };
  const attachLookup = (row: HTMLTableRowElement, component: ReactionComponent, kind: 'compound' | 'item'): void => {
    const input = row.querySelector<HTMLInputElement>(`[data-link="${kind}"]`);
    const idField = kind === 'compound' ? 'compound_id' : 'item_id';
    const choices = new Map<string, Choice>();
    if (component[idField]) {
      input.value = '#' + component[idField];
      // Read the target through its normal endpoint, so resource names respect permissions.
      void ApiC.getJson<Choice>(`${kind === 'compound' ? 'compounds' : 'items'}/${component[idField]}`, { notifOnError: 0 })
        .then(choice => { if (input.isConnected && component[idField] === choice.id) input.value = choice.name || choice.title || choice.smiles || '#' + choice.id; }).catch(() => {});
    }
    input.addEventListener('input', () => { component[idField] = null; });
    Autocomplete.attach(input, {
      minLength: 2, appendTo: form,
      source: async term => {
        const results = await ApiC.getJson<Choice[]>(kind === 'compound' ? 'compounds' : 'items', { q: term, limit: 10, notifOnError: 0 });
        choices.clear();
        return results.map(choice => {
          choices.set(String(choice.id), choice);
          return { value: String(choice.id), label: choice.name || choice.title || choice.smiles || String(choice.id) };
        });
      },
      onSelect: async selected => {
        const choice = choices.get(selected.value);
        input.value = selected.label;
        if (kind === 'compound' && component.inchi_key && choice.inchi_key && component.inchi_key !== choice.inchi_key) {
          input.value = '';
          showError(form.dataset.mismatch);
          return;
        }
        component[idField] = choice.id;
        if (kind === 'compound' && !component.molfile && !component.smiles) {
          try {
            component.smiles = choice.smiles || '';
            component.inchi_key = choice.inchi_key || '';
            component.molecular_formula = choice.molecular_formula || '';
            component.molecular_weight = choice.molecular_weight > 0 ? choice.molecular_weight : null;
            component.name = choice.name || component.name;
            if (choice.smiles) component.molfile = (await ketcher.structService.convert({ struct: choice.smiles, output_format: ChemicalMimeType.Mol })).struct;
            render();
          } catch (value) { showError(value); }
        }
        dirty = true;
        calculate();
      },
    });
  };
  const render = (): void => {
    for (const name of formFields) field(name).value = String(reaction[name] ?? '');
    preview.hidden = !reaction.preview_png;
    if (reaction.preview_png) preview.src = 'data:image/png;base64,' + reaction.preview_png;
    rows.replaceChildren();
    for (const component of reaction.components) {
      const row = template.content.firstElementChild.cloneNode(true) as HTMLTableRowElement;
      row.dataset.componentKey = component.component_key;
      row.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[data-field]').forEach(input => input.value = String(component[input.dataset.field] ?? ''));
      row.querySelector('[data-formula]').textContent = component.molecular_formula || component.smiles;
      rows.append(row);
      attachLookup(row, component, 'compound');
      attachLookup(row, component, 'item');
    }
    referenceOptions();
    calculate();
  };
  const open = (value: Reaction): void => {
    reaction = value;
    dirty = !value.id;
    error.hidden = true;
    render();
    modal.modal('show');
  };
  create.disabled = false;
  create.onclick = async () => {
    if (busy) return;
    setBusy(true);
    try {
      const drawing = await reactionFromKetcher(ketcher);
      // Reuse registered chemical identities without publishing novel structures.
      for (const component of drawing.components) {
        if (!component.inchi_key) continue;
        const candidates = await ApiC.getJson<Choice[]>('compounds', { q: component.inchi_key, limit: 5, notifOnError: 0 }).catch(() => []);
        const match = candidates.find(candidate => candidate.inchi_key === component.inchi_key);
        if (match) { component.compound_id = match.id; component.name = match.name || ''; }
      }
      drawing.components.forEach(component => component.name ||= component.molecular_formula || component.smiles);
      open(drawing);
    } catch (value) { notify.error(value instanceof Error ? value.message : String(value)); }
    finally { setBusy(false); }
  };
  document.getElementById('reactionReplaceDrawing').onclick = async () => {
    if (busy || !confirm(form.dataset.confirmReplace)) return;
    collect();
    setBusy(true);
    try {
      reaction = replaceReactionDrawing(reaction, await reactionFromKetcher(ketcher));
      render();
      dirty = true;
    } catch (value) { showError(value); }
    finally { setBusy(false); }
  };
  document.getElementById('reactionAddComponent').onclick = () => {
    collect();
    if (reaction.components.length >= 128) return;
    reaction.components.push(emptyComponent());
    dirty = true;
    render();
  };
  form.addEventListener('input', () => {
    dirty = true;
    collect();
    referenceOptions();
    calculate();
  });
  form.addEventListener('change', () => { dirty = true; collect(); referenceOptions(); calculate(); });
  rows.addEventListener('click', event => {
    if (!(event.target as HTMLElement).closest('[data-remove-component]')) return;
    collect();
    const key = (event.target as HTMLElement).closest<HTMLTableRowElement>('tr').dataset.componentKey;
    reaction.components = reaction.components.filter(component => component.component_key !== key);
    if (reaction.reference_component_key === key) reaction.reference_component_key = null;
    if (reaction.yield_basis_component_key === key) reaction.yield_basis_component_key = null;
    dirty = true;
    render();
  });
  form.onsubmit = async event => {
    event.preventDefault();
    if (busy || !form.reportValidity()) return;
    collect();
    setBusy(true);
    ++calculationRequest;
    clearTimeout(calculationTimer);
    try {
      if (reaction.id) await ApiC.patch(`${endpoint}/${reaction.id}`, { ...reaction, notifOnError: 0 });
      else await ApiC.post(endpoint, { ...reaction, notifOnError: 0 });
      dirty = false;
      busy = false;
      modal.modal('hide');
      await reloadElements(['reactionsList']).catch(value => notify.error(value));
    } catch (value) { showError(value); }
    finally { setBusy(false); }
  };
  modal.on('shown.bs.modal', () => { field('title').focus(); calculate(); });
  modal.on('hide.bs.modal', event => {
    if (busy || (dirty && !confirm(form.dataset.confirmDiscard))) event.preventDefault();
    else { ++calculationRequest; clearTimeout(calculationTimer); }
  });
  window.addEventListener('beforeunload', event => { if (dirty && modal.hasClass('show')) event.preventDefault(); });
  section.addEventListener('click', async event => {
    const button = (event.target as HTMLElement).closest<HTMLButtonElement>('[data-reaction-action]');
    if (!button || busy) return;
    const id = button.closest<HTMLElement>('[data-reaction-id]').dataset.reactionId;
    setBusy(true);
    button.disabled = true;
    try {
      if (button.dataset.reactionAction === 'delete') {
        if (!confirm(form.dataset.confirmDelete)) return;
        await ApiC.delete(`${endpoint}/${id}`);
        await reloadElements(['reactionsList']);
      } else {
        const saved = await ApiC.getJson<Reaction>(`${endpoint}/${id}`);
        if (button.dataset.reactionAction === 'edit') open(saved);
        else {
          if (!await loadChemDrawing(ketcher, saved.scheme ? JSON.stringify(saved.scheme) : saved.rxn, {
            confirm: form.dataset.confirmLoad, error: 'Could not load this reaction.',
          })) return;
          ensureTogglableSectionIsOpen('chemEditorIcon', 'chemEditorDiv');
        }
      }
    } catch (value) { notify.error(value instanceof Error ? value.message : String(value)); }
    finally { button.disabled = false; setBusy(false); }
  });
}
