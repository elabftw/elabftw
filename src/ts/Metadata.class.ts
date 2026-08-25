/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';
import { ApiC } from './api';
import { Action, EntityType } from './interfaces';
import type { Entity } from './interfaces';
import JsonEditorHelper from './JsonEditorHelper.class';
import { ExtraFieldInputType } from './metadataInterfaces';
import type { ExtraFieldProperties, ExtraFieldsGroup, ValidMetadata } from './metadataInterfaces';
import { adjustHiddenState, makeSortableGreatAgain, reloadElements, replaceWithTitle } from './misc';
import { notify } from './notify';

export function ResourceNotFoundException(message: string): void {
  this.message = message;
  this.name = 'ResourceNotFoundException';
}

// Auto-resize the textarea to prevent hidden lines on page load
export function autoResize(element: HTMLTextAreaElement): void {
  element.style.height = 'auto';
  element.style.height = `${element.scrollHeight}px`;
}

export class Metadata {
  entity: Entity;
  editor: JsonEditorHelper;
  model: EntityType;
  metadataDiv: Element;

  constructor(entity: Entity, jsonEditor: JsonEditorHelper) {
    this.entity = entity;
    this.editor = jsonEditor;
    this.model = entity.type;
    // this is the div that will hold all the generated fields from metadata json
    this.metadataDiv = document.getElementById('metadataDiv');
  }

  /**
   * Get the json from the metadata column
   */
  read(): Promise<Record<string, unknown>|ValidMetadata> {
    return ApiC.getJson(`${this.entity.type}/${this.entity.id}`).then(json => {
      // if there are no metadata.json file available, return an empty object
      if (typeof json.metadata === 'undefined' || !json.metadata) {
        return {};
      }
      return JSON.parse(json.metadata);
    });
  }

  toggleReadonly(fieldName: string, readonly: boolean): Promise<void> {
    return this.read().then(metadata => {
      if (!metadata.extra_fields || !metadata.extra_fields[fieldName]) return;
      metadata.extra_fields[fieldName].readonly = readonly;
      return this.save(metadata as ValidMetadata).then(() => this.display('edit'));
    });
  }

  /**
   * Save a field value after a change.
   * Multi-value fields are collected from all their rendered input rows.
   */
  handleEvent(event: Event): Promise<Response> | boolean {
    const el = event.target as HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement;
    if (el.reportValidity() === false || el.hasAttribute('readonly')) {
      return false;
    }
    if (el.dataset.units === '1') {
      return this.updateUnit(event);
    }

    const fieldName = el.dataset.field;
    if (!fieldName) {
      return false;
    }

    // prevent self links
    if (el.dataset.target === document.getElementById('info').dataset.type
      && parseInt(el.value, 10) === parseInt(document.getElementById('info').dataset.id, 10)
    ) {
      notify.error('no-self-links');
      return false;
    }

    let value: string|number|Array<string|number>;
    if (el instanceof HTMLSelectElement && el.multiple) {
      value = Array.from(el.selectedOptions, option => option.value);
    } else {
      const inputValue = this.getInputValue(el);
      if (inputValue === null) {
        return false;
      }
      value = inputValue;

      // also create a link automatically for experiments, resources and compounds.
      if (value !== '' && [ExtraFieldInputType.Experiments.valueOf(), ExtraFieldInputType.Items.valueOf(), ExtraFieldInputType.Compounds.valueOf()].includes(el.dataset.target)) {
        const reloadTargets = el.dataset.target === ExtraFieldInputType.Compounds.valueOf()
          ? ['compoundDiv']
          : ['linksDiv', 'linksExpDiv'];
        ApiC.post(`${this.entity.type}/${this.entity.id}/${el.dataset.target}_links/${value}`).then(() => reloadElements(reloadTargets));
      }
    }

    const multiValueHolder = el.closest('[data-purpose="multi-value-holder"]') as HTMLElement | null;
    if (multiValueHolder) {
      // structural edits (add/remove row) shift value indexes: value_labels
      // must be rebuilt together with value, so persist both via a full
      // metadata save instead of the value-only field update.
      return this.saveMultiValueHolder(multiValueHolder);
    }

    return this.updateMetadataField(fieldName, value);
  }

  getInputValue(element: HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement): string|number|null {
    let value: string|number = element.value;
    if (element instanceof HTMLInputElement && element.type === 'checkbox') {
      value = element.checked ? 'on' : 'off';
    }

    if ([ExtraFieldInputType.Experiments.valueOf(), ExtraFieldInputType.Items.valueOf(), ExtraFieldInputType.Users.valueOf(), ExtraFieldInputType.Compounds.valueOf()].includes(element.dataset.target)) {
      const rawValue = String(value).trim();
      if (rawValue === '') {
        return '';
      }
      const match = rawValue.match(/^(\d+)(?:\s+-\s+.*)?$/);
      const id = match ? Number(match[1]) : NaN;
      return Number.isSafeInteger(id) ? id : null;
    }

    return value;
  }

  getMultiValues(holder: HTMLElement): Array<string|number>|null {
    const values: Array<string|number> = [];
    for (const row of Array.from(holder.querySelectorAll<HTMLElement>('[data-purpose="multi-value-row"]'))) {
      const radioHolder = row.querySelector('[data-purpose="radio-holder"]');
      if (radioHolder) {
        const checked = radioHolder.querySelector<HTMLInputElement>('input[type="radio"]:checked');
        values.push(checked?.value ?? '');
        continue;
      }

      const input = row.querySelector<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>('[data-metadata-value="true"]');
      if (!input) {
        values.push('');
        continue;
      }
      const value = this.getInputValue(input);
      if (value === null) {
        return null;
      }
      values.push(value);
    }
    return values;
  }

  updateMetadataField(fieldName: string, value: string|number|Array<string|number>): Promise<Response> {
    const params: Record<string, unknown> = {action: Action.UpdateMetadataField};
    params[fieldName] = value;
    return ApiC.patch(`${this.entity.type}/${this.entity.id}`, params).then(response => {
      this.editor.loadMetadata();
      return response;
    });
  }

  updateUnit(event: Event): Promise<Response> {
    const select = (event.target as HTMLSelectElement);
    const value = select.value;
    const name = select.dataset.field;
    if (!name) {
      return Promise.reject(new Error('Missing metadata field name.'));
    }
    return this.read().then(metadata => {
      metadata.extra_fields[name].unit = value;
      return this.save(metadata as ValidMetadata);
    });
  }
  /**
   * Save the whole json at once, coming from json editor save button
   */
  update(metadata: ValidMetadata): Promise<void> {
    return this.save(metadata).then(() => this.display('edit'));
  }

  save(metadata: ValidMetadata): Promise<Response> {
    return ApiC.patch(`${this.entity.type}/${this.entity.id}`, {'metadata': JSON.stringify(metadata)});
  }

  /**
   * Cleanup extra fields and group of extra fields
   */
  cleanupMetadata(metadata: ValidMetadata): void {
    // remove empty extra_fields
    if (metadata.extra_fields && Object.keys(metadata.extra_fields).length === 0) {
      delete metadata.extra_fields;
    }
    // clean extra_fields_groups
    if (metadata.elabftw?.extra_fields_groups) {
      // when there are no fields, all groups are unused
      if (!metadata.extra_fields) {
        delete metadata.elabftw.extra_fields_groups;
      } else {
        // otherwise, keep only groups still referenced
        const usedGroupIds = new Set<number>(
          Object.values(metadata.extra_fields)
            .map(f => f.group_id)
            .filter((id): id is number => typeof id === 'number'),
        );
        metadata.elabftw.extra_fields_groups =
          metadata.elabftw.extra_fields_groups.filter(group =>
            usedGroupIds.has(group.id),
          );
        if (metadata.elabftw.extra_fields_groups.length === 0) {
          delete metadata.elabftw.extra_fields_groups;
        }
      }
    }
    // final cleanup: remove empty elabftw object
    if (metadata.elabftw && Object.keys(metadata.elabftw).length === 0) {
      delete metadata.elabftw;
    }
  }

  /**
   * Build text areas for extra fields (default type)
   */
  buildTextArea(name, properties: ExtraFieldProperties): HTMLTextAreaElement {
    const element = document.createElement('textarea');

    // style it to look like an input & reset height
    element.classList.add('form-control', 'form-textarea');
    element.rows = 1;

    // dynamic height on input
    element.addEventListener('input', () => autoResize(element));

    // resize after rendering: ensures proper height on page load
    requestAnimationFrame(() => autoResize(element));

    if (properties.value) {
      element.value = properties.value as string;
    }
    if (Object.prototype.hasOwnProperty.call(properties, 'required')) {
      element.required = true;
    }
    element.dataset.field = name;
    element.addEventListener('change', this, false);
    return element;
  }

  /**
   * For radio we need a special build workflow
   */
  buildRadio(name: string, properties: ExtraFieldProperties): Element {
    // a div to hold the different elements so we can return a single Element
    const element = document.createElement('div');
    element.dataset.purpose = 'radio-holder';

    const radioInputs = [];
    const radiosName = this.getRandomId();
    for (const option of properties.options) {
      const radioInput = document.createElement('input');
      radioInput.classList.add('form-check-input');
      radioInput.type = 'radio';
      radioInput.checked = properties.value === option ? true : false;
      radioInput.value = option;
      // they all need to have the same name to work together
      radioInput.name = radiosName;
      radioInput.id = this.getRandomId();
      // add a data-field attribute so we know what to update on change
      radioInput.dataset.field = name;
      radioInput.disabled = properties.readonly === true;
      radioInputs.push(radioInput);
    }

    for (const input of radioInputs) {
      const wrapperDiv = document.createElement('div');
      wrapperDiv.classList.add('form-check', 'form-check-inline');
      element.appendChild(wrapperDiv);
      wrapperDiv.appendChild(input);
      const label = document.createElement('label');
      label.htmlFor = input.id;
      label.innerText = input.value;
      label.classList.add('form-check-label');
      wrapperDiv.appendChild(label);
    }
    element.addEventListener('change', this, false);
    return element;
  }

  buildMultiValueInput(name: string, properties: ExtraFieldProperties): HTMLElement {
    const holder = document.createElement('div');
    holder.dataset.purpose = 'multi-value-holder';
    holder.dataset.field = name;
    holder.dataset.inputType = properties.type ?? ExtraFieldInputType.Text;
    holder.id = this.getRandomId();

    const values = Array.isArray(properties.value)
      ? [...properties.value]
      : [properties.value ?? ''];
    if (values.length === 0) {
      values.push('');
    }

    for (const [index, value] of values.entries()) {
      holder.append(this.buildMultiValueRow(name, properties, value, holder, index));
    }

    if (properties.readonly !== true) {
      const addButton = document.createElement('button');
      addButton.type = 'button';
      addButton.classList.add('btn', 'btn-secondary', 'btn-sm');
      addButton.setAttribute('aria-label', i18next.t('add'));
      addButton.setAttribute('title', i18next.t('add'));
      const icon = document.createElement('i');
      icon.classList.add('fas', 'fa-plus');
      addButton.append(icon);
      addButton.addEventListener('click', () => {
        const row = this.buildMultiValueRow(name, properties, '', holder);
        holder.insertBefore(row, addButton);
        const input = row.querySelector<HTMLElement>('input, select, textarea');
        // Linked fields initialize their autocomplete from their click handler.
        // Initialize it before focusing a newly added row so typing works immediately.
        if (input?.dataset.action === 'autocomplete') {
          input.click();
        }
        input?.focus();
      });
      holder.append(addButton);
    }

    return holder;
  }

  buildMultiValueRow(
    name: string,
    properties: ExtraFieldProperties,
    value: string|number,
    holder: HTMLElement,
    index?: number,
  ): HTMLElement {
    const row = document.createElement('div');
    row.dataset.purpose = 'multi-value-row';
    row.classList.add('d-flex', 'align-items-start', 'mb-1');

    const inputWrapper = document.createElement('div');
    inputWrapper.classList.add('flex-grow-1');
    if (properties.type === ExtraFieldInputType.Checkbox) {
      inputWrapper.classList.add('form-check');
    }
    inputWrapper.append(this.generateSingleInput(name, {
      ...properties,
      value,
      allow_multi_values: false,
    }));
    row.append(inputWrapper);

    const label = properties.value_labels?.[index ?? -1];
    if (label) {
      const labelBadge = document.createElement('span');
      labelBadge.classList.add('badge', 'badge-pill', 'badge-light', 'ml-2');
      labelBadge.dataset.purpose = 'value-label';
      labelBadge.textContent = label;
      row.append(labelBadge);
    }

    if (properties.readonly !== true) {
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.classList.add('btn', 'btn-secondary', 'btn-sm', 'ml-2');
      removeButton.setAttribute('aria-label', i18next.t('remove'));
      removeButton.setAttribute('title', i18next.t('remove'));
      const icon = document.createElement('i');
      icon.classList.add('fas', 'fa-minus');
      removeButton.append(icon);
      removeButton.addEventListener('click', () => {
        row.remove();
        this.saveMultiValueHolder(holder);
      });
      row.append(removeButton);
    }

    return row;
  }

  /**
   * Collect the labels of the rendered multi-value rows, index-aligned with
   * the values. Rows without a badge yield an empty string so the
   * value <-> label association survives add/remove/reorder operations.
   */
  getMultiValueLabels(holder: HTMLElement): string[] {
    const labels: Array<string> = [];
    for (const row of Array.from(holder.querySelectorAll<HTMLElement>('[data-purpose="multi-value-row"]'))) {
      const badge = row.querySelector<HTMLElement>('[data-purpose="value-label"]');
      labels.push(badge?.textContent ?? '');
    }
    return labels;
  }

  saveMultiValueHolder(holder: HTMLElement): Promise<Response>|boolean {
    const fieldName = holder.dataset.field;
    if (!fieldName) {
      return false;
    }
    const values = this.getMultiValues(holder);
    if (values === null) {
      return false;
    }
    // persist value and value_labels together: a value-only PATCH leaves
    // stale labels behind after rows are removed or added.
    return this.read().then(metadata => {
      if (!metadata.extra_fields || !metadata.extra_fields[fieldName]) {
        return this.updateMetadataField(fieldName, values);
      }
      metadata.extra_fields[fieldName].value = values;
      const labels = this.getMultiValueLabels(holder);
      // only keep a labels array when at least one row actually has one,
      // so plain fields stay exactly as before
      if (labels.some(label => label !== '')) {
        metadata.extra_fields[fieldName].value_labels = labels;
      } else {
        delete metadata.extra_fields[fieldName].value_labels;
      }
      return this.save(metadata as ValidMetadata).then(response => {
        this.editor.loadMetadata();
        // re-render the fields so the UI reflects the persisted arrays
        return this.display('edit').then(() => response);
      });
    });
  }

  getRandomId(): string {
    return Math.random().toString(36).substring(2, 12);
  }

  generateElement(mode: string, name: string, properties: ExtraFieldProperties): Element {
    if (mode === 'view') {
      return this.generateViewableElement(name, properties);
    }
    try {
      return this.generateInput(name, properties);
    } catch (err) {
      notify.error('error-parsing-metadata');
      console.error(err);
    }
  }

  /**
   * Generate a non editable view of the extra fields
   */
  generateViewableElement(name: string, properties: ExtraFieldProperties): Element {
    const row = document.createElement('tr');
    const nameCell = document.createElement('td');
    nameCell.classList.add('align-top');

    // name + description
    const nameEl = document.createElement('strong');
    nameEl.innerText = name;
    nameCell.append(nameEl);
    nameCell.append(this.getDescription(properties));

    const valueCell = document.createElement('td');
    valueCell.classList.add('align-top');
    const values = Array.isArray(properties.value) ? properties.value : [properties.value];
    if (values.length === 0) {
      valueCell.append(this.generateViewableValue(properties, ''));
    } else {
      for (const [index, value] of values.entries()) {
        // keep each value and its label badge in a shared row container so
        // the badge sits beside its value instead of on a separate line
        const valueRow = document.createElement('div');
        valueRow.classList.add('d-flex', 'align-items-center');
        valueRow.append(this.generateViewableValue(properties, value));
        const label = properties.value_labels?.[index];
        if (label) {
          const labelBadge = document.createElement('span');
          labelBadge.classList.add('badge', 'badge-pill', 'badge-light', 'ml-2');
          labelBadge.dataset.purpose = 'value-label';
          labelBadge.textContent = label;
          valueRow.append(labelBadge);
        }
        valueCell.append(valueRow);
      }
    }

    row.append(nameCell, valueCell);
    return row;
  }

  generateViewableValue(properties: ExtraFieldProperties, value: string|number): HTMLElement {
    if (properties.type === ExtraFieldInputType.Checkbox) {
      const valueEl = document.createElement('input');
      valueEl.setAttribute('type', 'checkbox');
      valueEl.classList.add('d-block');
      valueEl.disabled = true;
      valueEl.checked = value === 'on';
      return valueEl;
    }

    const valueEl = document.createElement('div');
    let displayValue = String(value ?? '');
    if (displayValue && properties.unit) {
      displayValue += ' ' + properties.unit;
    }
    valueEl.innerText = displayValue || '—';
    // the link is generated with javascript so we can still use innerText and
    // not innerHTML with manual "<a href...>" which implicates security considerations
    if (properties.type === ExtraFieldInputType.Url && displayValue) {
      valueEl.dataset.genLink = 'true';
    }
    if (displayValue && [ExtraFieldInputType.Experiments.valueOf(), ExtraFieldInputType.Items.valueOf(), ExtraFieldInputType.Users.valueOf(), ExtraFieldInputType.Compounds.valueOf()].includes(properties.type)) {
      valueEl.dataset.replaceWithTitle = 'true';
      valueEl.dataset.endpoint = properties.type;
      valueEl.dataset.id = String(value);
    }
    return valueEl;
  }

  /**
   * Take the json description of the field and build an input element to be injected
   */
  generateInput(name: string, properties: ExtraFieldProperties): Element {
    if (properties.allow_multi_values === true && properties.type !== ExtraFieldInputType.Select) {
      return this.buildMultiValueInput(name, properties);
    }
    return this.generateSingleInput(name, properties);
  }

  generateSingleInput(name: string, properties: ExtraFieldProperties): Element {
    // we don't know yet which kind of element it will be
    let element: HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement;
    // generate a unique id for the element so we can associate the label properly
    const uniqid = this.getRandomId();

    // read the type of element
    switch (properties.type) {
    case ExtraFieldInputType.Checkbox:
    case ExtraFieldInputType.Date:
    case ExtraFieldInputType.DateTime:
    case ExtraFieldInputType.Email:
    case ExtraFieldInputType.Number:
    case ExtraFieldInputType.Time:
    case ExtraFieldInputType.Url:
      element = document.createElement('input');
      element.type = properties.type;
      // without this, it is not possible to enter floating point values
      if (properties.type === ExtraFieldInputType.Number) {
        element.setAttribute('step', 'any');
      }
      break;
    case ExtraFieldInputType.Select:
      element = document.createElement('select');
      if (properties.allow_multi_values === true) {
        element.toggleAttribute('multiple');
      }
      // add options to select element
      for (const option of properties.options) {
        const optionEl = document.createElement('option');
        optionEl.text = option;
        if (properties.allow_multi_values === true
          && (Array.isArray(properties.value) ? properties.value : [properties.value]).includes(option)
        ) {
          optionEl.setAttribute('selected', '');
        }
        element.add(optionEl);
      }
      break;
    case ExtraFieldInputType.Experiments:
    case ExtraFieldInputType.Items:
    case ExtraFieldInputType.Users:
    case ExtraFieldInputType.Compounds:
      element = document.createElement('input');
      element.type = 'text';
      break;
    case ExtraFieldInputType.Radio:
      return this.buildRadio(name, properties);
    default:
      element = this.buildTextArea(name, properties);
    }

    // add the unique id to the element
    element.id = uniqid;

    if (Object.prototype.hasOwnProperty.call(properties, 'value')) {
      if (element.type === 'checkbox') {
        (element as HTMLInputElement).checked = properties.value === 'on' ? true : false;
      }
      if (properties.allow_multi_values !== true) {
        element.value = String(properties.value ?? '');
      }
    }

    if (Object.prototype.hasOwnProperty.call(properties, 'required')) {
      element.required = true;
    }
    const mustDisable = element instanceof HTMLSelectElement ||
      (element instanceof HTMLInputElement && ['checkbox', 'radio'].includes(element.type));
    if (Object.prototype.hasOwnProperty.call(properties, 'readonly') && properties.readonly === true) {
      if (mustDisable) {
        element.disabled = true;
      } else {
        (element as HTMLInputElement | HTMLTextAreaElement).readOnly = true;
      }
    }

    // by default all inputs get this bootstrap class
    let cssClass = 'form-control';
    // but checkboxes/radios need a different one
    if (properties.type === 'checkbox') {
      cssClass = 'form-check-input';
    }
    element.classList.add(cssClass);

    // add data attributes so we know what to update on change and can collect multi-value rows
    element.dataset.field = name;
    element.dataset.metadataValue = 'true';
    // add an onChange listener to the element
    // so the json can be updated without having to click save
    // set the callback to the whole class so handleEvent is called and 'this' refers to the class
    // not the event in the function called
    element.addEventListener('change', this, false);

    // add a prepend button for "Now" for date and time types
    if (['time', 'date', 'datetime-local'].includes(element.type)) {
      const inputGroupDiv = document.createElement('div');
      inputGroupDiv.classList.add('input-group');
      const prependDiv = document.createElement('div');
      prependDiv.classList.add('input-group-prepend');
      // NOW/TODAY button
      const btn = document.createElement('button');
      btn.innerText = ['time', 'datetime-local'].includes(element.type) ? i18next.t('now') : i18next.t('today');
      btn.dataset.action = 'update-to-now';
      btn.classList.add('btn', 'btn-secondary');
      prependDiv.appendChild(btn);

      inputGroupDiv.appendChild(prependDiv);
      // now add the input
      inputGroupDiv.appendChild(element);
      // add the unique id to the input group for the label
      inputGroupDiv.id = uniqid;
      return inputGroupDiv;
    }

    // UNITS
    if (Object.prototype.hasOwnProperty.call(properties, 'units') && properties.units.length > 0) {
      const inputGroupDiv = document.createElement('div');
      inputGroupDiv.classList.add('input-group');
      const appendDiv = document.createElement('div');
      appendDiv.classList.add('input-group-append');
      const unitsSel = document.createElement('select');
      for (const unit of properties.units) {
        const optionEl = document.createElement('option');
        optionEl.text = unit;
        unitsSel.add(optionEl);
      }
      // if no default unit is set, auto-select first option. See #5680
      unitsSel.value = properties.unit || properties.units[0];
      unitsSel.classList.add('form-control', 'brl-none');
      // add this so we can differentiate the change event from the main input
      unitsSel.dataset.units = '1';
      unitsSel.dataset.field = name;
      unitsSel.addEventListener('change', this, false);
      appendDiv.appendChild(unitsSel);
      unitsSel.disabled = properties.readonly === true;
      // input first, then append div
      inputGroupDiv.appendChild(element);
      inputGroupDiv.appendChild(appendDiv);
      // add the unique id to the input group for the label
      inputGroupDiv.id = uniqid;
      return inputGroupDiv;
    }

    // USERS/EXPERIMENTS/ITEMS input have a prepend to the input with a magnifying glass
    if ([ExtraFieldInputType.Users, ExtraFieldInputType.Experiments, ExtraFieldInputType.Items, ExtraFieldInputType.Compounds].includes(properties.type)) {
      // set the target for autocomplete function
      element.dataset.target = properties.type;
      element.dataset.action = 'autocomplete';
      const inputGroupDiv = document.createElement('div');
      inputGroupDiv.classList.add('input-group');
      const prependDiv = document.createElement('div');
      prependDiv.classList.add('input-group-prepend');
      const icon = document.createElement('i');
      icon.classList.add('fas', 'fa-magnifying-glass');
      const iconWrapper = document.createElement('span');
      iconWrapper.classList.add('input-group-text');
      iconWrapper.appendChild(icon);
      prependDiv.appendChild(iconWrapper);
      inputGroupDiv.appendChild(prependDiv);
      inputGroupDiv.appendChild(element);
      // add the unique id to the input group for the label
      inputGroupDiv.id = uniqid;
      // we want to replace the bare id value with "id - title" or "userid - fullname" for users
      const targetId = parseInt(element.value.split(' ')[0]);
      if (!isNaN(targetId)) {
        element.dataset.replaceWithTitle = 'true';
        element.dataset.id = String(targetId);
        element.dataset.endpoint = properties.type;
      }

      return inputGroupDiv;
    }

    return element;
  }

  /**
   * Main public function to call to display the metadata in view or edit mode
   */
  display(mode: string): Promise<void> {
    let displayFunction = this.view;
    if (mode === 'edit') {
      displayFunction = this.edit;
    }

    // clear previous content
    return displayFunction.call(this).catch(e => {
      if (e instanceof ResourceNotFoundException) {
        // no metadata is associated but it's okay, it's not an error
        return;
      }
      // if there was an issue fetching metadata, log the error
      console.error(e);
    }).then(() => adjustHiddenState());
  }

  /**
   * In view mode, display the extra fields, currently only used with toggle body function
   */
  view(): Promise<void> {
    return this.read().then(json => {
      // do nothing more if there is no extra_fields in our json
      if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
        return;
      }
      const [groups, groupedArr] = this.getGroups('view', json as ValidMetadata);
      groups.forEach(group => {
        const groupWrapperDiv =  document.createElement('div');
        groupWrapperDiv.classList.add('mt-4');

        let headerEl = 'h4';
        // for the default group, don't show "default" but use hr instead
        if (group.id === -1) {
          headerEl = 'hr';
        }
        const groupHeader = document.createElement(headerEl);
        groupHeader.classList.add('d-inline');
        // only add content to the header if there are more than one group
        if (groups.length > 1 && groupHeader instanceof HTMLHeadingElement) {
          groupHeader.textContent = group.name;
        }

        groupWrapperDiv.append(groupHeader);

        const tableContainer = document.createElement('div');
        tableContainer.classList.add('pl-3', 'table-container');
        const table = document.createElement('table');
        table.classList.add('table', 'table-sm', 'table-borderless', 'mb-0');
        const tbody = document.createElement('tbody');

        // now display the names/values from extra_fields
        for (const element of groupedArr[group.id].sort((a: ExtraFieldProperties, b: ExtraFieldProperties) => a.position - b.position)) {
          tbody.append(element.element);
        }
        table.append(tbody);
        tableContainer.append(table);

        groupWrapperDiv.append(tableContainer);
        groupWrapperDiv.append(document.createElement('hr'));
        this.metadataDiv.append(groupWrapperDiv);
      });
      // refresh the existing groups select on update (see 5611)
      reloadElements(['newFieldGroupSelect', 'fieldsGroup']);
    }).then (() => {
      replaceWithTitle();
    });
  }

  // build a description element
  getDescription(properties: ExtraFieldProperties): HTMLSpanElement {
    const descriptionWrapper = document.createElement('div');
    if (properties.description) {
      const descriptionEl = document.createElement('p');
      descriptionEl.classList.add('smallgray');
      descriptionEl.innerText = properties.description;
      descriptionWrapper.append(descriptionEl);
    }
    return descriptionWrapper;
  }

  getGroups(mode: string, json: ValidMetadata) {
    // collect all extra fields, normalize position and group_id, add an element property
    const elements = [];
    for (const [name, properties] of Object.entries(json.extra_fields)) {
      // 0 is a valid position, so don't do something with "|| 9999"
      let position = parseInt(String(properties.position), 10);
      if (typeof position !== 'number') {
        position = 999;
      }
      elements.push({
        name: name,
        description: properties.description,
        element: this.generateElement(mode, name, properties),
        position: position,
        group_id: properties.group_id || -1,
        required: properties.required ? properties.required : false,
      });
    }

    // collect extra fields groups if they are defined
    let groups: Array<ExtraFieldsGroup> = [];
    if (Object.prototype.hasOwnProperty.call(json, 'elabftw')) {
      if (Object.prototype.hasOwnProperty.call(json.elabftw, 'extra_fields_groups')) {
        groups = groups.concat(json.elabftw.extra_fields_groups);
      }
    }

    // group the elements based on the group property
    const groupedArr = elements.reduce((grouped, el) => {
      // make sure the group_id is part of the defined elabftw.groups, or assign it to group -1
      const group = groups.some(grp => grp.id === parseInt(el.group_id, 10)) ? el.group_id : -1;
      grouped[group] = grouped[group] || [];
      grouped[group].push(el);
      return grouped;
    }, {});

    // add the undefined group at the end, but only if there are fields without groups
    if (elements.some(entry => entry.group_id === -1)) {
      groups = groups.concat([{id: -1, name: i18next.t('undefined-group')}]);
    }

    return [groups, groupedArr];
  }

  /**
   * Get the metadata json and add input elements to DOM
   */
  edit(): Promise<void> {
    return this.read().then(json => {
      this.editor.refresh(json as ValidMetadata);
      // clean groups field if they're removed via json editor
      if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
        this.metadataDiv.replaceChildren();
        reloadElements(['newFieldGroupSelect', 'fieldsGroup']);
        return;
      }
      const [groups, groupedArr] = this.getGroups('edit', json as ValidMetadata);
      // the full content of extra fields
      const wrapperDiv = document.createElement('div');

      groups.forEach(group => {
        // make sure there is an element in that group
        if (Object.prototype.hasOwnProperty.call(groupedArr, group.id)) {
          const groupWrapperDiv =  document.createElement('div');
          groupWrapperDiv.classList.add('mt-4');
          const groupHeader = document.createElement('h4');
          groupHeader.dataset.action='toggle-next';
          groupHeader.dataset.openedIcon='fa-caret-down';
          groupHeader.dataset.closedIcon='fa-caret-right';
          groupHeader.classList.add('d-inline', 'togglable-section-title');
          const groupHeaderIcon = document.createElement('i');
          groupHeaderIcon.classList.add('fas', 'fa-caret-down', 'fa-fw', 'mr-2');
          // only add content to the header if there are more than one group
          if (groups.length > 1 && groupHeader instanceof HTMLHeadingElement) {
            groupHeader.textContent = group.name;
            groupHeader.insertAdjacentElement('afterbegin', groupHeaderIcon);
          }

          const wrapperUl = document.createElement('ul');
          wrapperUl.classList.add('list-group', 'mt-2', 'sortable');
          wrapperUl.dataset.axis = 'y';
          wrapperUl.dataset.table = 'extra_fields';
          wrapperUl.dataset.saveHidden = `extra_fields_group_${this.entity.type}_${this.entity.id}_${group.id}`;

          for (const element of groupedArr[group.id].sort((a: ExtraFieldProperties, b: ExtraFieldProperties) => a.position - b.position)) {
            const listItem = document.createElement('li');
            listItem.classList.add('list-group-item');
            const labelDiv = document.createElement('div');
            labelDiv.classList.add('d-flex', 'justify-content-between');

            // LABEL
            const label = document.createElement('label');
            label.htmlFor = element.element.id;
            label.innerText = element.name as string;
            if (element.required) {
              label.classList.add('required-label');
            }
            label.classList.add('py-2');

            // div to hold the drag and delete buttons
            const fieldActionsDiv = document.createElement('div');
            fieldActionsDiv.classList.add('d-flex', 'align-items-center');

            // add a badge indicating the field's type
            const badgeContainer = document.createElement('div');
            const badge = document.createElement('span');
            badge.classList.add('badge', 'badge-pill', 'badge-light', 'mr-3');
            // define input type for badge. This "type" is just indicative for the badge.
            let inputType;
            if (element.element.tagName === 'INPUT' || element.element.tagName === 'SELECT') {
              inputType = element.element.type;
            } else if (element.element.tagName === 'TEXTAREA') {
              inputType = 'text';
            } else if (element.element.dataset.purpose === 'multi-value-holder') {
              inputType = element.element.dataset.inputType ?? 'unknown';
            } else if (element.element.classList.contains('input-group')) {
              // find the first input element within the input group
              const input = element.element.querySelector('input');
              inputType = input ? input.type : 'unknown';
            } else {
              // radio is only "special case" and too many conditions to identify it
              inputType = 'radio';
            }
            badge.innerText = inputType;
            badgeContainer.appendChild(badge);

            // add a button to set the position of the field
            const handle = document.createElement('div');
            handle.dataset.action = 'metadata-reposition-field';
            handle.classList.add('btn', 'mr-2');
            const handleIconSpan = document.createElement('span');
            handleIconSpan.classList.add('draggable', 'sortableHandle');
            const handleIcon = document.createElement('i');
            handleIcon.classList.add('fas', 'fa-grip-vertical');
            handleIcon.setAttribute('aria-label', i18next.t('sort'));
            handleIconSpan.appendChild(handleIcon);
            handle.appendChild(handleIconSpan);

            // button to edit the field
            const editBtn = document.createElement('button');
            editBtn.dataset.action = 'metadata-edit-field';
            editBtn.dataset.target = 'fieldBuilderModal';
            editBtn.classList.add('btn', 'btn-transparent', 'mr-2');
            editBtn.type = 'button';
            editBtn.setAttribute('aria-label', i18next.t('edit'));
            editBtn.setAttribute('title', i18next.t('edit'));
            const editIcon = document.createElement('i');
            editIcon.classList.add('fas', 'fa-pencil-alt');
            editBtn.appendChild(editIcon);

            // add a button to toggle read-only
            const readonlyBtn = document.createElement('button');
            readonlyBtn.classList.add('btn', 'btn-transparent', 'mr-2');
            readonlyBtn.type = 'button';
            readonlyBtn.setAttribute('aria-label', i18next.t('readonly'));
            readonlyBtn.setAttribute('title', i18next.t('readonly'));
            const readonlyIcon = document.createElement('i');
            readonlyIcon.classList.add('fas');
            readonlyBtn.appendChild(readonlyIcon);
            let isReadonly = (json as ValidMetadata)
              .extra_fields[element.name]?.readonly === true;
            readonlyIcon.classList.toggle('fa-lock', isReadonly);
            readonlyIcon.classList.toggle('fa-lock-open', !isReadonly);
            readonlyBtn.addEventListener('click', () => {
              isReadonly = !isReadonly;
              this.toggleReadonly(element.name, isReadonly).catch(() => {
                notify.error('error-saving-metadata');
              });
            });

            // add a button to delete the field
            const deleteBtn = document.createElement('button');
            deleteBtn.dataset.action = 'metadata-rm-field';
            deleteBtn.classList.add('btn', 'btn-transparent');
            deleteBtn.type = 'button';
            deleteBtn.setAttribute('aria-label', i18next.t('remove'));
            deleteBtn.setAttribute('title', i18next.t('remove'));
            const deleteIcon = document.createElement('i');
            deleteIcon.classList.add('fas', 'fa-trash-alt');
            deleteBtn.appendChild(deleteIcon);

            fieldActionsDiv.append(badgeContainer, handle, editBtn, readonlyBtn, deleteBtn);

            labelDiv.append(label, fieldActionsDiv);

            // for checkboxes the label comes second
            if (element.element.type === 'checkbox') {
              label.classList.add('form-check-label');
              const wrapperDiv = document.createElement('div');
              wrapperDiv.classList.add('form-check');
              listItem.append(wrapperDiv);
              wrapperDiv.append(element.element);
              wrapperDiv.append(labelDiv);
              wrapperDiv.append(this.getDescription(element));
            } else {
              listItem.append(labelDiv);
              listItem.append(this.getDescription(element));
              listItem.append(element.element);
            }

            // the data-name attribute is picked up by Sortable jquery-ui. Do not use the default id attribute as it's not a number.
            listItem.dataset.name = label.innerText;

            wrapperUl.append(listItem);
          }
          groupWrapperDiv.append(groupHeader);
          groupWrapperDiv.append(wrapperUl);
          wrapperDiv.append(groupWrapperDiv);
        }
      });

      this.metadataDiv.replaceChildren(wrapperDiv);
      reloadElements(['newFieldGroupSelect', 'fieldsGroup']);
    }).then(() => {
      makeSortableGreatAgain();
      replaceWithTitle();
    });
  }
}
