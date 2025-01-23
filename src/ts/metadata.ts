/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  getEntity,
  notifError,
  addAutocompleteToExtraFieldsKeyInputs,
} from './misc';
import { Metadata } from './Metadata.class';
import { ValidMetadata, ExtraFieldInputType } from './metadataInterfaces';
import JsonEditorHelper from './JsonEditorHelper.class';
import { JsonEditorActions } from './JsonEditorActions.class';
import { Api } from './Apiv2.class';
import i18next from 'i18next';
import { merge } from 'lodash-es';


document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('metadataDiv')) {
    return;
  }
  const entity = getEntity();
  if (!entity.id) {
    return;
  }

  // add extra fields elements from metadata json
  const JsonEditorHelperC = new JsonEditorHelper(entity);
  // only run if there is the json-editor block
  if (document.getElementById('json-editor')) {
    const JsonEditorActionsC = new JsonEditorActions();
    JsonEditorActionsC.init(JsonEditorHelperC, true);
  }
  const MetadataC = new Metadata(entity, JsonEditorHelperC);
  MetadataC.display('edit');
  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // DELETE EXTRA FIELD
    if (el.matches('[data-action="metadata-rm-field"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        MetadataC.read().then(metadata => {
          const name = el.parentElement.parentElement.closest('div').querySelector('label').innerText;
          delete metadata.extra_fields[name];
          MetadataC.update(metadata as ValidMetadata);
        });
      }
    }
  });

  // FIELD BUILDER CODE
  if (!document.getElementById('fieldBuilderModal')) {
    return;
  }

  addAutocompleteToExtraFieldsKeyInputs();

  function toggleContentDiv(key: string) {
    const keys = ['classic', 'select', 'selectradio', 'checkbox', 'number'];
    document.getElementById('newFieldContentDiv_' + key).toggleAttribute('hidden', false);
    // remove the shown one from the list and hide all others
    keys.filter(k => k !== key).forEach(k => {
      document.getElementById('newFieldContentDiv_' + k).toggleAttribute('hidden', true);
    });
  }

  // when the type is selected, the default value input's type is changed to match
  document.getElementById('newFieldTypeSelect').addEventListener('change', event => {
    const fieldType = (event.target as HTMLSelectElement).value;
    const valueInput = document.getElementById('newFieldValueInput');

    switch (fieldType as ExtraFieldInputType) {
    case ExtraFieldInputType.Text:
    case ExtraFieldInputType.Date:
    case ExtraFieldInputType.DateTime:
    case ExtraFieldInputType.Email:
    case ExtraFieldInputType.Url:
    case ExtraFieldInputType.Time:
    case ExtraFieldInputType.Experiments:
    case ExtraFieldInputType.Items:
    case ExtraFieldInputType.Users:
      valueInput.setAttribute('type', fieldType);
      toggleContentDiv('classic');
      break;
    case ExtraFieldInputType.Select:
      document.getElementById('newFieldContentDiv_select').removeAttribute('hidden');
      toggleContentDiv('selectradio');
      // special case for the select block to allow multiple values
      document.getElementById('newFieldContentDiv_select').toggleAttribute('hidden', false);
      break;
    case ExtraFieldInputType.Radio:
      toggleContentDiv('selectradio');
      break;
    case ExtraFieldInputType.Number:
    case ExtraFieldInputType.Checkbox:
      toggleContentDiv(fieldType);
      break;
    default:
      break;
    }
  });

  document.getElementById('fieldLoaderModal').addEventListener('click', async event => {
    const el = (event.target as HTMLElement);
    const ApiC = new Api();
    // LOAD METADATA FROM TEMPLATE/CATEGORY
    if (el.matches('[data-action="load-metadata-from"]')) {
      const select = (document.getElementById(`loadMetadataSelect_${el.dataset.target}`) as HTMLSelectElement);
      const selectedIndex = select.selectedIndex;
      const id = select.options[selectedIndex].value;
      const textarea = (document.getElementById('loadMetadataTextarea') as HTMLInputElement);
      ApiC.getJson(`${el.dataset.target}/${id}`).then(json => {
        const jsonObj = JSON.parse(json.metadata);
        textarea.value = JSON.stringify(jsonObj, null, 2);
        // prevent saving an empty value
        if (jsonObj === null) {
          return;
        }
        const applyBtn = (document.getElementById('applyMetadataLoadBtn') as HTMLButtonElement);
        applyBtn.removeAttribute('disabled');
      });
    } else if (el.matches('[data-action="load-metadata-from-textarea"]')) {
      const textarea = (document.getElementById('loadMetadataTextarea') as HTMLInputElement);
      const MetadataC = new Metadata(entity, new JsonEditorHelper(entity));
      const currentMetadata = await ApiC.getJson(`${entity.type}/${entity.id}`).then(json => JSON.parse(json.metadata));
      // we need to use lodash's merge because Object.assign() or spread operator will only do shallow merge
      const mergedMetadata = merge(JSON.parse(textarea.value), currentMetadata);
      ApiC.patch(`${entity.type}/${entity.id}`, {metadata: JSON.stringify(mergedMetadata)}).then(() => {
        MetadataC.display('edit');
        textarea.value = '';
      }).then(() => document.getElementById('metadataDiv').scrollIntoView());
    }
  });

  document.getElementById('fieldBuilderModal').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    const grpSel = (document.getElementById('newFieldGroupSelect') as HTMLSelectElement);
    // SAVE NEW EXTRA FIELD
    if (el.matches('[data-action="save-new-field"]')) {
      if ((document.getElementById('newFieldForm') as HTMLFormElement).reportValidity() === false) {
        notifError(new Error('Error validating the form.'));
        return;
      }

      const fieldKey = (document.getElementById('newFieldKeyInput') as HTMLInputElement).value.trim();

      let json = {};
      // get the current metadata
      MetadataC.read().then(metadata => {
        if (metadata) {
          json = metadata;
        }
        if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
          json['extra_fields'] = {};
        }
        // build the new field
        const field = {};
        field['type'] = (document.getElementById('newFieldTypeSelect') as HTMLSelectElement).value;
        let fieldValue: string;
        if (['text', 'date', 'datetime-local', 'email', 'time', 'url'].includes(field['type'])) {
          fieldValue = (document.getElementById('newFieldValueInput') as HTMLInputElement).value.trim();
        } else if (['select', 'radio'].includes(field['type'])) {
          field['options'] = [];
          document.getElementById('choicesInputDiv').querySelectorAll('input').forEach(opt => field['options'].push(opt.value));
          // just take the first one as selected value
          fieldValue = field['options'][0];
        } else if (field['type'] === 'number') {
          fieldValue = (document.getElementById('newFieldValueInput') as HTMLInputElement).value;
          field['units'] = [];
          document.getElementById('unitChoicesInputDiv').querySelectorAll('input').forEach(opt => {
            const unitValue = opt.value;
            // only add non empty values
            if (unitValue) {
              field['units'].push(unitValue);
            }
          });
          field['unit'] = '';
          // if there is at least one value in "units", add it to "unit"
          if (field['units'].length > 0) {
            field['unit'] = field['units'][0];
          }

        } else if (field['type'] === 'checkbox') {
          fieldValue = (document.getElementById('newFieldCheckboxDefaultSelect') as HTMLSelectElement).value === 'checked' ? 'on' : '';
        }
        field['value'] = fieldValue || '';
        // get the description
        if ((document.getElementById('newFieldDescriptionInput') as HTMLInputElement).value) {
          field['description'] = (document.getElementById('newFieldDescriptionInput') as HTMLInputElement).value.trim();
        }
        // deal with the blank_on_value
        if ((document.getElementById('blankValueOnDuplicateSwitch') as HTMLInputElement).checked) {
          field['blank_value_on_duplicate'] = true;
        }
        // deal with the required attribute
        if ((document.getElementById('requiredSwitch') as HTMLInputElement).checked) {
          field['required'] = true;
        }
        // deal with the readonly attribute
        if ((document.getElementById('readonlySwitch') as HTMLInputElement).checked) {
          field['readonly'] = true;
        }
        // deal with the multi select
        if ((document.getElementById('newFieldAllowMultiSelect') as HTMLInputElement).checked) {
          field['allow_multi_values'] = true;
        }
        if (grpSel.value !== '-1') {
          field['group_id'] = parseInt(grpSel.value);
        }

        json['extra_fields'][fieldKey] = field;

        // jQuery selector required for .modal()
        MetadataC.update(json as ValidMetadata).then(() => {
          const form = (document.getElementById('newFieldForm') as HTMLFormElement);
          // remove all extra inputs (dropdown and radio)
          form.querySelectorAll('.is-extra-input').forEach(i => i.parentElement.remove());
          // clear all fields
          form.reset();
        });
      });
    // ADD OPTION FOR SELECT OR RADIO
    } else if (el.matches('[data-action="new-field-add-option"]')) {
      const newGroup = document.createElement('div');
      newGroup.classList.add('input-group', 'mb-1');
      const newInput = document.createElement('input');
      // the is-extra-input class is used to remove them upon save
      newInput.classList.add('form-control', 'is-extra-input');
      const appendDiv = document.createElement('div');
      appendDiv.classList.add('input-group-append');
      const btn = document.createElement('button');
      btn.setAttribute('type', 'button');
      btn.dataset.action = 'remove-self';
      btn.classList.add('btn', 'btn-secondary');
      btn.textContent = '−';
      appendDiv.appendChild(btn);

      newGroup.appendChild(newInput);
      newGroup.appendChild(appendDiv);
      el.parentElement.querySelector('div').append(newGroup);
    // REMOVE INPUT FROM MULTI INPUT TYPES (DROPDOWN, RADIO)
    } else if (el.matches('[data-action="remove-self"]')) {
      el.parentElement.parentElement.remove();
    // SAVE NEW GROUP
    } else if (el.matches('[data-action="save-new-fields-group"]')) {
      const nameInput = (document.getElementById('newFieldsGroupKeyInput') as HTMLInputElement);

      // get the current metadata
      MetadataC.read().then((metadata: ValidMetadata) => {
        // make sure we have an elabftw property
        if (!Object.prototype.hasOwnProperty.call(metadata, 'elabftw')) {
          metadata['elabftw'] = {};
        }
        // default groupid
        let groupId = 1;
        if (Object.prototype.hasOwnProperty.call(metadata.elabftw, 'extra_fields_groups')) {
          // find out what will be the next group id by looking for the highest group.id and adding 1
          groupId = metadata.elabftw.extra_fields_groups.reduce((prev, current) => {
            return (prev.id > current.id) ? prev : current;
          }).id + 1;
        } else {
          // create an empty array if no groups exist
          metadata.elabftw.extra_fields_groups = [];
        }

        const grpOption = document.createElement('option');
        grpOption.value = String(groupId);
        grpOption.text = nameInput.value;
        grpSel.add(grpOption);
        // select the freshly added group because it is the most likely to be picked now that we just added it
        grpSel.selectedIndex = grpSel.options.length - 1;

        // save the new group in metadata
        metadata.elabftw.extra_fields_groups.push({'id': groupId, 'name': nameInput.value});
        // don't use the update method because we don't need to refresh the inputs
        MetadataC.save(metadata);
        // clear input value
        nameInput.value = '';
      });
    }
  });
});
