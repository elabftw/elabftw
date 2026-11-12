/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */
import { ChemicalMimeType } from 'ketcher-core';
import type { Ketcher } from 'ketcher-core';
import Autocomplete from './Autocomplete.class';
import { ApiC } from './api';
import { loadChemDrawing } from './chem-drawing';
import { hideModal, isModalShown, onModalEvent, showModal } from './common';
import { BusyState, showFormError } from './form-state';
import { errorMessage } from './misc';
import { showTab } from './tabbed-sections';
import { notify } from './notify';
import { emptyComponent, quantityUnits, reactionFromKetcher, replaceReactionDrawing } from './reaction';
import { reloadReactionsList } from './reaction-summary';
import type { Reaction, ReactionComponent } from './reaction';

type Choice = { id: number; name?: string; title?: string; smiles?: string; inchi_key?: string; molecular_formula?: string; molecular_weight?: number; cas_number?: string };
type Calculated = { amount_mol: number | null; mass_g: number | null; volume_ml: number | null; equivalents: number | null; yield_percent: number | null };
type CalculatedComponent = ReactionComponent & { planned: Calculated; actual: Calculated };
type LinkKind = 'compound' | 'item';

const modalSelector = '#reactionModal';
const formFields = ['title', 'temperature_c', 'duration_s', 'pressure_pa', 'atmosphere', 'conditions_notes'] as const;
const links = {
  compound: { endpoint: 'compounds', idField: 'compound_id' },
  item: { endpoint: 'items', idField: 'item_id' },
} as const;

const choiceLabel = (choice: Choice): string => choice.name || choice.title || choice.smiles || '#' + choice.id;
const notifyError = (error: unknown): void => notify.error(errorMessage(error));
// toLocaleString() alone keeps at most 3 fraction digits, hiding small-scale quantities.
const formatQuantity = (value: number | null, unit: string): string => value === null ? '' : value.toLocaleString(undefined, { maximumSignificantDigits: 6 }) + ' ' + unit;

const INCHI_KEY = /^[A-Z]{14}-[A-Z]{10}-[A-Z]$/;

/** Both creating a reaction and replacing its drawing go through this, so new components look the same either way. */
async function enrichDrawing(drawing: Reaction): Promise<Reaction> {
  // Reuse registered chemical identities without publishing novel structures: one exact lookup for all the structures
  const inchiKeys = [...new Set(drawing.components.map(component => component.inchi_key).filter(key => INCHI_KEY.test(key)))];
  const compounds = inchiKeys.length === 0
    ? []
    : await ApiC.getJson<Choice[]>('compounds', { inchi_key: inchiKeys.join(','), limit: inchiKeys.length, notifOnError: 0 }).catch((): Choice[] => []);
  const byInchiKey = new Map(compounds.map(compound => [compound.inchi_key, compound]));
  for (const component of drawing.components) {
    const match = byInchiKey.get(component.inchi_key);
    if (match) {
      component.compound_id = match.id;
      component.name = match.name || '';
    }
    component.name ||= component.molecular_formula || component.smiles;
  }
  return drawing;
}

/** The reaction modal: a draft reaction edited in a form, with quantities calculated by the server as the user types. */
class ReactionEditor {
  private reaction: Reaction;
  private dirty = false;
  private calculationRequest = 0;
  private calculationTimer: ReturnType<typeof setTimeout>;
  // Re-rendering the table (add, remove, replace) must not read every linked target again.
  private linkLabels = new Map<string, Promise<string>>();
  private readonly state: BusyState;
  private readonly rows = document.getElementById('reactionComponents');
  private readonly template = document.getElementById('reactionComponentTemplate') as HTMLTemplateElement;
  private readonly error = document.getElementById('reactionError');
  private readonly preview = document.getElementById('reactionPreview') as HTMLImageElement;
  private readonly endpoint: string;

  constructor(private readonly ketcher: Ketcher, private readonly form: HTMLFormElement, create: HTMLButtonElement, section: HTMLElement) {
    this.endpoint = form.dataset.endpoint;
    this.state = new BusyState([create]);
    create.disabled = false;
    create.onclick = () => this.create();
    document.getElementById('reactionReplaceDrawing').onclick = () => this.replaceDrawing();
    document.getElementById('reactionAddComponent').onclick = () => this.addComponent();
    // Autocomplete selections and selects emit input too, so this sees every edit.
    form.addEventListener('input', () => this.edited());
    this.rows.addEventListener('click', event => {
      const row = (event.target as HTMLElement).closest('[data-remove-component]')?.closest<HTMLTableRowElement>('tr');
      if (row) this.removeComponent(row.dataset.componentKey);
    });
    form.onsubmit = event => this.save(event);
    onModalEvent(modalSelector, 'shown', () => { this.field('title').focus(); this.calculate(); });
    onModalEvent(modalSelector, 'hide', event => {
      if (this.state.busy || (this.dirty && !confirm(form.dataset.confirmDiscard))) event.preventDefault();
      else this.cancelCalculation();
    });
    window.addEventListener('beforeunload', event => { if (this.dirty && isModalShown(modalSelector)) event.preventDefault(); });
    section.addEventListener('click', event => {
      const button = (event.target as HTMLElement).closest<HTMLButtonElement>('[data-reaction-action]');
      if (button) void this.runAction(button);
    });
  }

  private field(name: string): HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement {
    return this.form.elements.namedItem(name) as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;
  }

  private run(task: () => Promise<boolean | void>, onError = (error: unknown) => showFormError(this.error, error)): Promise<boolean> {
    return this.state.run(this.form, task, onError);
  }

  private collect(): Reaction {
    const reaction = this.reaction;
    for (const name of formFields) Object.assign(reaction, { [name]: this.field(name).value || (this.field(name).type === 'number' ? null : '') });
    reaction.reference_component_key = this.field('reference_component_key').value || null;
    reaction.yield_basis_component_key = this.field('yield_basis_component_key').value || null;
    this.rows.querySelectorAll<HTMLTableRowElement>('tr[data-component-key]').forEach(row => {
      const component = reaction.components.find(component => component.component_key === row.dataset.componentKey);
      row.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[data-field]').forEach(input => {
        Object.assign(component, { [input.dataset.field]: input.value || (input.type === 'number' ? null : '') });
      });
      component.planned_basis = quantityUnits[component.planned_unit];
      component.actual_basis = quantityUnits[component.actual_unit];
    });
    return reaction;
  }

  private referenceOptions(): void {
    for (const name of ['reference_component_key', 'yield_basis_component_key'] as const) {
      const select = this.field(name) as HTMLSelectElement;
      const value = this.reaction[name] || '';
      select.replaceChildren(new Option('', ''), ...this.reaction.components
        .filter(component => ['reactant', 'reagent'].includes(component.role))
        .map(component => new Option(component.name || component.molecular_formula || component.smiles, component.component_key)));
      select.value = value;
    }
  }

  private cancelCalculation(): void {
    ++this.calculationRequest;
    clearTimeout(this.calculationTimer);
  }

  private calculate(): void {
    this.cancelCalculation();
    const request = this.calculationRequest;
    this.rows.querySelectorAll('output').forEach(output => output.textContent = '');
    this.calculationTimer = setTimeout(async () => {
      if (this.state.busy || !this.reaction) return;
      const draft = this.collect();
      try {
        const response = await ApiC.patch(this.endpoint, {
          action: 'calculate', components: draft.components,
          reference_component_key: draft.reference_component_key, yield_basis_component_key: draft.yield_basis_component_key,
          notifOnSaved: 0, notifOnError: 0,
        });
        const result: { components: CalculatedComponent[] } = await response.json();
        if (request !== this.calculationRequest || !isModalShown(modalSelector)) return;
        for (const component of result.components) {
          const row = this.rows.querySelector(`[data-component-key="${CSS.escape(component.component_key)}"]`);
          for (const phase of ['planned', 'actual'] as const) {
            const value = component[phase];
            row.querySelector(`[data-result="${phase}"]`).textContent = [
              formatQuantity(value.mass_g, 'g'), formatQuantity(value.volume_ml, 'mL'),
              formatQuantity(value.amount_mol === null ? null : value.amount_mol * 1000, 'mmol'),
              formatQuantity(value.equivalents, 'eq'), formatQuantity(value.yield_percent, '%'),
            ].filter(Boolean).join('; ');
          }
        }
        this.error.hidden = true;
      } catch (error) {
        if (request === this.calculationRequest) showFormError(this.error, error);
      }
    }, 300);
  }

  private linkLabel(kind: LinkKind, id: number): Promise<string> {
    const key = `${kind}/${id}`;
    if (!this.linkLabels.has(key)) {
      // Read the target through its normal endpoint, so resource names respect permissions.
      this.linkLabels.set(key, ApiC.getJson<Choice>(`${links[kind].endpoint}/${id}`, { notifOnError: 0 }).then(choiceLabel));
    }
    return this.linkLabels.get(key);
  }

  private attachLookup(row: HTMLTableRowElement, component: ReactionComponent, kind: LinkKind): void {
    const input = row.querySelector<HTMLInputElement>(`[data-link="${kind}"]`);
    const { endpoint, idField } = links[kind];
    const choices = new Map<string, Choice>();
    const linkedId = component[idField];
    if (linkedId) {
      input.value = '#' + linkedId;
      void this.linkLabel(kind, linkedId)
        .then(label => { if (input.isConnected && component[idField] === linkedId) input.value = label; }).catch(() => {});
    }
    input.addEventListener('input', () => { component[idField] = null; });
    Autocomplete.attach(input, {
      minLength: 2, appendTo: this.form,
      source: async term => {
        const results = await ApiC.getJson<Choice[]>(endpoint, { q: term, limit: 10, notifOnError: 0 });
        choices.clear();
        return results.map(choice => {
          choices.set(String(choice.id), choice);
          return { value: String(choice.id), label: choiceLabel(choice) };
        });
      },
      onSelect: async selected => {
        const choice = choices.get(selected.value);
        input.value = selected.label;
        if (kind === 'compound' && component.inchi_key && choice.inchi_key && component.inchi_key !== choice.inchi_key) {
          input.value = '';
          showFormError(this.error, this.form.dataset.mismatch);
          return;
        }
        component[idField] = choice.id;
        this.linkLabels.set(`${kind}/${choice.id}`, Promise.resolve(selected.label));
        if (kind === 'compound' && !component.molfile && !component.smiles) {
          try {
            component.smiles = choice.smiles || '';
            component.inchi_key = choice.inchi_key || '';
            component.molecular_formula = choice.molecular_formula || '';
            component.molecular_weight = choice.molecular_weight > 0 ? choice.molecular_weight : null;
            component.name = choice.name || component.name;
            if (choice.smiles) component.molfile = (await this.ketcher.structService.convert({ struct: choice.smiles, output_format: ChemicalMimeType.Mol })).struct;
            this.render();
          } catch (error) { showFormError(this.error, error); }
        }
        this.dirty = true;
        this.calculate();
      },
    });
  }

  private render(): void {
    for (const name of formFields) this.field(name).value = String(this.reaction[name] ?? '');
    this.preview.hidden = !this.reaction.preview_png;
    if (this.reaction.preview_png) this.preview.src = 'data:image/png;base64,' + this.reaction.preview_png;
    this.rows.replaceChildren();
    for (const component of this.reaction.components) {
      const row = this.template.content.firstElementChild.cloneNode(true) as HTMLTableRowElement;
      row.dataset.componentKey = component.component_key;
      row.querySelectorAll<HTMLInputElement | HTMLSelectElement>('[data-field]').forEach(input => input.value = String(component[input.dataset.field] ?? ''));
      row.querySelector('[data-formula]').textContent = component.molecular_formula || component.smiles;
      this.rows.append(row);
      this.attachLookup(row, component, 'compound');
      this.attachLookup(row, component, 'item');
    }
    this.referenceOptions();
    this.calculate();
  }

  private open(reaction: Reaction): void {
    this.reaction = reaction;
    this.dirty = !reaction.id;
    this.linkLabels.clear();
    this.error.hidden = true;
    this.render();
    showModal(modalSelector);
  }

  private async create(): Promise<void> {
    await this.run(async () => this.open(await enrichDrawing(await reactionFromKetcher(this.ketcher))), notifyError);
  }

  private async replaceDrawing(): Promise<void> {
    if (this.state.busy || !confirm(this.form.dataset.confirmReplace)) return;
    this.collect();
    await this.run(async () => {
      this.reaction = replaceReactionDrawing(this.reaction, await enrichDrawing(await reactionFromKetcher(this.ketcher)));
      this.render();
      this.dirty = true;
    });
  }

  private addComponent(): void {
    this.collect();
    if (this.reaction.components.length >= 128) return;
    this.reaction.components.push(emptyComponent());
    this.dirty = true;
    this.render();
  }

  private removeComponent(key: string): void {
    const reaction = this.collect();
    reaction.components = reaction.components.filter(component => component.component_key !== key);
    if (reaction.reference_component_key === key) reaction.reference_component_key = null;
    if (reaction.yield_basis_component_key === key) reaction.yield_basis_component_key = null;
    this.dirty = true;
    this.render();
  }

  private edited(): void {
    this.dirty = true;
    this.collect();
    this.referenceOptions();
    this.calculate();
  }

  private async save(event: SubmitEvent): Promise<void> {
    event.preventDefault();
    if (this.state.busy || !this.form.reportValidity()) return;
    const reaction = this.collect();
    this.cancelCalculation();
    const saved = await this.run(async () => {
      if (reaction.id) await ApiC.patch(`${this.endpoint}/${reaction.id}`, { ...reaction, notifOnError: 0 });
      else await ApiC.post(this.endpoint, { ...reaction, notifOnError: 0 });
      this.dirty = false;
    });
    if (!saved) return;
    hideModal(modalSelector);
    await reloadReactionsList().catch(notifyError);
  }

  private async runAction(button: HTMLButtonElement): Promise<void> {
    if (this.state.busy) return;
    const id = button.closest<HTMLElement>('[data-reaction-id]').dataset.reactionId;
    const action = button.dataset.reactionAction;
    button.disabled = true;
    await this.run(async () => {
      if (action === 'delete') {
        if (!confirm(this.form.dataset.confirmDelete)) return false;
        await ApiC.delete(`${this.endpoint}/${id}`);
        await reloadReactionsList();
        return;
      }
      const saved = await ApiC.getJson<Reaction>(`${this.endpoint}/${id}`);
      if (action === 'edit') {
        this.open(saved);
        return;
      }
      if (!await loadChemDrawing(this.ketcher, saved.scheme ? JSON.stringify(saved.scheme) : saved.rxn, {
        confirm: this.form.dataset.confirmLoad, error: 'Could not load this reaction.',
      })) return false;
      showTab('chem', { scroll: true });
    }, notifyError);
    button.disabled = false;
  }
}

export function initChemReactions(ketcher: Ketcher): void {
  const form = document.getElementById('reactionForm') as HTMLFormElement | null;
  if (!form || form.dataset.initialized) return;
  form.dataset.initialized = '1';
  new ReactionEditor(ketcher, form, document.getElementById('chemCreateReaction') as HTMLButtonElement, document.getElementById('reactionsSection'));
}
