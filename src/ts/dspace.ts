/**
 * @author Nicolas CARPi #Deltablot
 * @author Mouss #Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 *
 * All logic related to DSpace export modal. Located in toolbar on view/edit pages
 */
import { ApiC } from './api';
import i18next from './i18n';
import { Action, FileType, Method } from './interfaces';
import { rememberLastSelected, selectLastSelected } from './localStorage';
import { notify } from './notify';
import { entity } from './getEntity';
import { TomSelect, collectForm, mkSpin, mkSpinStop, reloadElements } from './misc';
import { on } from './handlers';
import $ from 'jquery';
import JsonEditorHelper from './JsonEditorHelper.class';
import { Metadata } from './Metadata.class';
import { ExtraFieldInputType, ValidMetadata } from './metadataInterfaces';

async function saveDspaceIdAsExtraField(itemUuid: string): Promise<void> {
  const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
  const raw = await MetadataC.read();
  const metadata = (raw || {}) as ValidMetadata;
  if (!metadata.extra_fields) {
    metadata.extra_fields = {};
  }

  metadata.extra_fields['DSpace Item Public URL'] = {
    type: ExtraFieldInputType.Url,
    value: itemUuid,
    description: 'Link to item in DSpace repository',
    readonly: true,
  };

  const mode = new URLSearchParams(window.location.search).get('mode');
  await MetadataC.save(metadata).then(() => mode === 'edit'
    ? MetadataC.display('edit')
    : reloadElements(['extraFieldsSection']));
}

interface DspaceCollection {
  uuid: string;
  name: string;
  [key: string]: unknown;
}

interface DspaceVocabularyEntry {
  value: string;
  display: string;
  [key: string]: unknown;
}

interface DspaceSubmissionForm {
  id: string;
  name: string;
  rows: DspaceRow[];
}

interface DspaceRow {
  fields: DspaceField[];
}

interface DspaceField {
  label: string;
  mandatory: boolean;
  repeatable: boolean;
  input: {
    type: DspaceInputType;
  };
  selectableMetadata: DspaceSelectableMetadata[];
  selectableRelationship?: unknown;
  hints?: string;
}

interface DspaceSelectableMetadata {
  metadata: string;
  label?: string | null;
  controlledVocabulary?: string;
  closed?: boolean;
}

type DspaceInputType = | 'onebox' | 'textarea' | 'dropdown' | 'lookup' | 'date' | 'series' | string;

interface UiField {
  name: string;
  label: string;
  type: DspaceInputType;
  required: boolean;
  repeatable: boolean;
  vocabulary?: string;
  metadataOptions: DspaceSelectableMetadata[];
  section: string;
}

function extractUiFields(forms: DspaceSubmissionForm[]): UiField[] {
  const fields: UiField[] = [];
  forms.forEach(form => {
    (form.rows || []).forEach(row => {
      (row.fields || []).forEach(field => {
        const metadataOptions = field.selectableMetadata || [];
        if (metadataOptions.length === 0) {
          return;
        }

        const firstMetadata = metadataOptions[0];

        fields.push({
          name: firstMetadata.metadata,
          label: field.label,
          type: field.input?.type || 'onebox',
          required: field.mandatory,
          repeatable: field.repeatable,
          vocabulary: firstMetadata.controlledVocabulary,
          metadataOptions,
          section: form.name,
        });
      });
    });
  });
  return fields;
}

function prepareUiFields(forms: DspaceSubmissionForm[]): UiField[] {
  const extractedFields = extractUiFields(forms);

  const uniqueFields = Object.values(
    Object.fromEntries(
      extractedFields.map(field => [
        `${field.label}::${field.metadataOptions.map(opt => opt.metadata).join('|')}`,
        field,
      ]),
    ),
  );
  uniqueFields.sort((a, b) => Number(b.required) - Number(a.required));
  return uniqueFields;
}

function collectDynamicMetadata(): { key: string; value: string; section: string }[] {
  const metadata: { key: string; value: string; section: string }[] = [];
  const fieldWrappers = document.querySelectorAll('#dspaceDynamicFields .form-group');

  fieldWrappers.forEach(wrapperEl => {
    const wrapper = wrapperEl as HTMLElement;

    const valueInput = wrapper.querySelector(
      'input[name]:not([name$="__metadata"]), textarea[name], select[name]:not([name$="__metadata"])',
    ) as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null;

    if (!valueInput || !valueInput.value) {
      return;
    }

    const metadataSelector = wrapper.querySelector(
      'select[name$="__metadata"]',
    ) as HTMLSelectElement | null;

    const section = wrapper.dataset.section;
    if (!section) {
      return;
    }

    metadata.push({
      key: metadataSelector ? metadataSelector.value : valueInput.name,
      value: valueInput.value,
      section,
    });
  });

  return metadata;
}

function buildInput(field: UiField, types: DspaceVocabularyEntry[]): HTMLElement {
  const wrapper = document.createElement('div');
  wrapper.dataset.section = field.section;
  wrapper.className = 'form-group';

  const label = document.createElement('label');
  label.setAttribute('for', `dspace-${field.name}`);
  label.textContent = field.required ? `${field.label} *` : field.label;
  wrapper.appendChild(label);

  if (field.metadataOptions.length > 1) {
    const metadataSelect = document.createElement('select');
    metadataSelect.className = 'form-control mb-2';
    metadataSelect.name = `${field.name}__metadata`;

    field.metadataOptions.forEach(option => {
      const opt = document.createElement('option');
      opt.value = option.metadata;
      opt.textContent = option.label || option.metadata;
      metadataSelect.appendChild(opt);
    });

    wrapper.appendChild(metadataSelect);
  }

  let input: HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

  if (field.vocabulary === 'common_types') {
    const select = document.createElement('select');
    select.name = field.name;
    select.id = `dspace-${field.name}`;
    select.className = 'form-control';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = i18next.t('Select an option');
    placeholder.disabled = field.required;
    placeholder.selected = true;
    select.appendChild(placeholder);

    types.forEach(type => {
      const option = document.createElement('option');
      option.value = type.value;
      option.textContent = type.display;
      select.appendChild(option);
    });

    input = select;
  } else {
    switch (field.type) {
    case 'textarea': {
      const textarea = document.createElement('textarea');
      textarea.id = `dspace-${field.name}`;
      textarea.name = field.name;
      textarea.className = 'form-control';
      input = textarea;
      break;
    }
    case 'date': {
      const dateInput = document.createElement('input');
      dateInput.type = 'date';
      dateInput.id = `dspace-${field.name}`;
      dateInput.name = field.name;
      dateInput.className = 'form-control';
      input = dateInput;
      break;
    }
    default: {
      const textInput = document.createElement('input');
      textInput.type = 'text';
      textInput.id = `dspace-${field.name}`;
      textInput.name = field.name;
      textInput.className = 'form-control';
      input = textInput;
    }
    }
  }

  if (field.required) {
    input.required = true;
  }

  wrapper.appendChild(input);
  return wrapper;
}

on('export-to-dspace', async (el, event: Event) => {
  const btn = el as HTMLButtonElement;
  event.preventDefault();
  const form = document.getElementById('dspaceExportForm') as HTMLFormElement;
  const params = collectForm(form);
  const format = FileType.Eln;
  const metadata = collectDynamicMetadata();

  const oldHTML = mkSpin(btn);
  const prevNotifOnSaved = ApiC.notifOnSaved;
  const prevNotifOnError = ApiC.notifOnError;

  try {
    ApiC.notifOnSaved = false;
    ApiC.notifOnError = false;
    const res = await ApiC.send(Method.PATCH, 'dspace', {
      collection: params['collection'],
      metadata,
      entity,
      format,
    });

    const data = await res.json();
    const itemPublicUrl = data.publicUrl;
    await saveDspaceIdAsExtraField(itemPublicUrl);
    notify.success('export-success');
    $('#dspaceExportModal').modal('hide');
  } catch (e) {
    notify.error(e);
  } finally {
    ApiC.notifOnSaved = prevNotifOnSaved;
    ApiC.notifOnError = prevNotifOnError;
    mkSpinStop(btn, oldHTML);
  }
});

// populate lists with tomSelect on modal show
on('open-dspace-modal', async () => {
  $('#dspaceExportModal').modal('toggle');

  const collectionSelect = document.getElementById('dspaceCollection') as HTMLSelectElement & { tomselect?: TomSelect };
  const typeSelect = document.getElementById('dspaceType') as HTMLSelectElement & { tomselect?: TomSelect };
  const dynamicContainer = document.getElementById('dspaceDynamicFields');

  collectionSelect.innerHTML = `<option disabled selected>${i18next.t('loading')}...</option>`;
  typeSelect.innerHTML = `<option disabled selected>${i18next.t('loading')}...</option>`;

  if (dynamicContainer) {
    dynamicContainer.innerHTML = '';
  }

  try {
    const [collections, types, forms] = await Promise.all([
      ApiC.getJson<DspaceCollection[]>('dspace', { action: Action.GetCollections }),
      ApiC.getJson<DspaceVocabularyEntry[]>('dspace', { action: Action.GetTypes }),
      ApiC.getJson<DspaceSubmissionForm[]>('dspace', { action: Action.GetSubmissionForms }),
    ]);

    if (collectionSelect.tomselect) {
      collectionSelect.tomselect.destroy();
    }
    if (typeSelect.tomselect) {
      typeSelect.tomselect.destroy();
    }

    collectionSelect.innerHTML = '';
    collections.forEach(col => {
      const option = document.createElement('option');
      option.value = col.uuid;
      option.textContent = `${col.name} (${col.uuid})`;
      collectionSelect.appendChild(option);
    });

    typeSelect.innerHTML = '';
    types.forEach(type => {
      const option = document.createElement('option');
      option.value = type.value;
      option.textContent = type.display;
      typeSelect.appendChild(option);
    });

    ['dspaceCollection', 'dspaceType'].forEach(id => {
      new TomSelect(`#${id}`, {
        plugins: ['dropdown_input', 'no_active_items'],
        onChange: rememberLastSelected(id),
        onInitialize: selectLastSelected(id),
      });
    });

    collectionSelect.tomselect?.on('change', () => {
      if (!dynamicContainer) {
        return;
      }

      const filteredForms = forms.filter(form =>
        form.name === 'publicationStep' || form.name === 'traditionalPageTwo',
      );
      const uiFields = prepareUiFields(filteredForms);

      dynamicContainer.innerHTML = '';

      uiFields.forEach(field => {
        dynamicContainer.appendChild(buildInput(field, types));
      });
    });

    const initialCollectionValue = collectionSelect.tomselect?.getValue();
    if (initialCollectionValue && dynamicContainer) {
      const filteredForms = forms.filter(form => form.name === 'publicationStep');
      const uiFields = prepareUiFields(filteredForms);

      dynamicContainer.innerHTML = '';

      uiFields.forEach(field => {
        dynamicContainer.appendChild(buildInput(field, types));
      });
    }
  } catch (e) {
    collectionSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Collections' })}</option>`;
    typeSelect.innerHTML = `<option disabled selected>${i18next.t('error-fetch-request', { error: 'Types' })}</option>`;
    console.error(e);
  }
});
