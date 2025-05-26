/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import {
  getEntity,
  addAutocompleteToExtraFieldsKeyInputs,
  normalizeFieldName,
} from './misc';
import { Metadata } from './Metadata.class';
import { ValidMetadata, ExtraFieldInputType } from './metadataInterfaces';
import JsonEditorHelper from './JsonEditorHelper.class';
import { JsonEditorActions } from './JsonEditorActions.class';
import { Api } from './Apiv2.class';
import i18next from 'i18next';
import { merge } from 'lodash-es';
import $ from 'jquery';
import { Notification } from './Notifications.class';


document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('metadataDiv')) {
    return;
  }
  const entity = getEntity();
  if (!entity.id) {
    return;
  }

  const notify = new Notification();
  // add extra fields elements from metadata json
  const JsonEditorHelperC = new JsonEditorHelper(entity);
  // only run if there is the json-editor block
  if (document.getElementById('json-editor')) {
    const JsonEditorActionsC = new JsonEditorActions();
    JsonEditorActionsC.init(JsonEditorHelperC, true);
  }
  const MetadataC = new Metadata(entity, JsonEditorHelperC);
  MetadataC.display('edit');

  function createButton(action: string, btnType: string, content?: string): HTMLButtonElement {
    const btn: HTMLButtonElement = document.createElement('button');
    btn.setAttribute('type', 'button');
    btn.dataset.action = action;
    btn.classList.add('btn', btnType);
    btn.textContent = content ?? '';
    return btn;
  }

  const saveButton = document.querySelector('[data-action="save-new-field"]') as HTMLButtonElement;
  const editButton = document.querySelector('[data-action="edit-extra-field"]') as HTMLButtonElement;
  const multiSelectDiv = document.getElementById('allowMultiSelectDiv');

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    if (el.matches('[data-action="metadata-edit-field"]')) {
      $('#' + el.dataset.target).modal('toggle');
      // toggle buttons for edit modal
      saveButton.setAttribute('hidden', 'hidden');
      editButton.removeAttribute('hidden');

      // once modal is up, check fields to update
      const fieldGroupSelect = document.getElementById('newFieldGroupSelect') as HTMLSelectElement;
      const fieldDescriptionInput = document.getElementById('newFieldDescriptionInput') as HTMLInputElement;

      const extraField = el.parentElement.parentElement.closest('div');
      if (!extraField) {
        notify.error('not-found');
        return;
      }
      // store current name as attribute, to use as field's key and update
      const fieldNameInput = document.getElementById('newFieldKeyInput') as HTMLInputElement;
      const fieldName = extraField.querySelector('label').innerText.trim();
      fieldNameInput.dataset.name = fieldName;

      // populate modal with current extraField values
      MetadataC.read().then(metadata => {
        // handle keys with extra spaces (e.g. from manual json editor) by matching trimmed names
        const realKey = Object.keys(metadata.extra_fields).find(
          key => key.trim() === fieldName,
        );
        const fieldData = realKey ? metadata.extra_fields[realKey] : undefined;
        const fieldType = fieldData.type?.trim() || 'text';
        // set field type
        const fieldTypeSelect = document.getElementById('newFieldTypeSelect') as HTMLSelectElement;
        fieldTypeSelect.value = fieldType as string;
        // type may be null due to json editing,if null return a default value
        if (fieldType === ExtraFieldInputType.Select && multiSelectDiv.hidden) {
          multiSelectDiv.removeAttribute('hidden');
        }
        // prefill switches
        (document.getElementById('blankValueOnDuplicateSwitch') as HTMLInputElement).checked = !!fieldData.blank_value_on_duplicate;
        (document.getElementById('requiredSwitch') as HTMLInputElement).checked = !!fieldData.required;
        (document.getElementById('readonlySwitch') as HTMLInputElement).checked = !!fieldData.readonly;
        (document.getElementById('newFieldAllowMultiSelect') as HTMLInputElement).checked = !!fieldData.allow_multi_values;

        let containerId, sourceArray, toggleDiv;
        // same behaviour is applied for select, radio and number. Only div name is different
        if (fieldType === ExtraFieldInputType.Select || fieldType === ExtraFieldInputType.Radio) {
          containerId = 'choicesInputDiv';
          sourceArray = fieldData.options;
          toggleDiv = 'selectradio';
        } else if (fieldType === ExtraFieldInputType.Number) {
          containerId = 'unitChoicesInputDiv';
          sourceArray = fieldData.units;
          toggleDiv = 'number';
        }

        if (toggleDiv) {
          toggleContentDiv(toggleDiv);
          const fieldValueInputDiv = document.getElementById(containerId);
          fieldValueInputDiv.innerHTML = '';

          // populate the corresponding container
          if (sourceArray) {
            sourceArray.forEach(item => {
              // Create group
              const newGroup = document.createElement('div');
              newGroup.classList.add('input-group', 'mb-1');
              // Create input
              const input = document.createElement('input');
              input.classList.add('form-control', 'is-extra-input');
              input.type = 'text';
              input.value = item;
              // Create button append div
              const appendDiv = document.createElement('div');
              appendDiv.classList.add('input-group-append');
              // Create self-remove button
              const btn = createButton('remove-self', 'btn-secondary', '-');
              appendDiv.appendChild(btn);
              // Assemble elements
              newGroup.append(input, appendDiv);
              fieldValueInputDiv.append(newGroup);
            });
          }
        } else if (fieldType === ExtraFieldInputType.Checkbox) {
          toggleContentDiv('checkbox');
          const checkboxSelect = document.getElementById('newFieldCheckboxDefaultSelect') as HTMLSelectElement;
          checkboxSelect.value = fieldData.value === 'on' ? 'checked' : 'unchecked';
        } else {
          // Default handling for simple text-based inputs
          const fieldValueInput = document.getElementById('newFieldValueInput') as HTMLInputElement;
          fieldValueInput.value = fieldData.value || '';
          fieldValueInput.type = fieldType;
        }

        fieldGroupSelect.value = fieldData.group_id ?? '-1';
        fieldTypeSelect.value = fieldType;
        fieldNameInput.value = fieldName;
        fieldDescriptionInput.value = fieldData.description ?? null;
      });
    }
    // DELETE EXTRA FIELD
    if (el.matches('[data-action="metadata-rm-field"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        MetadataC.read().then(metadata => {
          const name = el.parentElement.parentElement.closest('div').querySelector('label').innerText.trim();
          delete metadata.extra_fields[name];
          MetadataC.update(metadata as ValidMetadata).then(() => document.getElementById('metadataDiv').scrollIntoView());
        });
      }
    }
  });

  // FIELD BUILDER CODE
  if (!document.getElementById('fieldBuilderModal')) {
    return;
  }

  function clearForm() {
    // remove all extra inputs (dropdown and radio)
    const form = (document.getElementById('newFieldForm') as HTMLFormElement);
    form.querySelectorAll('.is-extra-input').forEach(i => i.parentElement.remove());
    form.reset();
  }

  $('#fieldBuilderModal').on('hidden.bs.modal', () => {
    // reset to default state on close
    if (!editButton.hasAttribute('hidden') && saveButton.hasAttribute('hidden')) {
      editButton.setAttribute('hidden', 'hidden');
      saveButton.removeAttribute('hidden');
    }
    // reset all input fields except classic text (default)
    document.querySelectorAll('[id^="newFieldContentDiv_"]:not([id="newFieldContentDiv_classic"])')
      .forEach(div => {
        (div as HTMLDivElement).hidden = true;
      });
    document.getElementById('newFieldContentDiv_classic').hidden = false;
    multiSelectDiv.setAttribute('hidden', 'hidden');
    clearForm();
  });

  addAutocompleteToExtraFieldsKeyInputs();


  function toggleContentDiv(key: string) {
    const keys = ['classic', 'selectradio', 'checkbox', 'number'];
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
    multiSelectDiv.setAttribute('hidden', 'hidden');

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
      multiSelectDiv.removeAttribute('hidden');
      toggleContentDiv('selectradio');
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
        notify.error('form-validation-error');
        return;
      }

      const fieldKeyValue = (document.getElementById('newFieldKeyInput') as HTMLInputElement).value.trim();
      const fieldKey = normalizeFieldName(fieldKeyValue);

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
        } else if (field['type'] === ExtraFieldInputType.Number) {
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

        } else if (field['type'] === ExtraFieldInputType.Checkbox) {
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
        if ((document.getElementById('requiredSwitch') as HTMLInputElement).checked) {
          field['required'] = true;
        }
        if ((document.getElementById('readonlySwitch') as HTMLInputElement).checked) {
          field['readonly'] = true;
        }
        if ((document.getElementById('newFieldAllowMultiSelect') as HTMLInputElement).checked) {
          field['allow_multi_values'] = true;
        }
        if (grpSel.value !== '-1') {
          field['group_id'] = parseInt(grpSel.value);
        }

        json['extra_fields'][fieldKey] = field;

        // jQuery selector required for .modal()
        MetadataC.update(json as ValidMetadata).then(() => {
          // store the currently selected group before reset, to maintain selection when adding a new input
          const selectedGroup = grpSel.value;
          clearForm();
          // restore original value
          grpSel.value = selectedGroup;
          // and finally close the modal
          $('#fieldBuilderModal').modal('toggle');
        });
      });
    // EDIT EXTRA FIELD
    } else if (el.matches('[data-action="edit-extra-field"]')) {
      // prevent form invalid data
      if ((document.getElementById('newFieldForm') as HTMLFormElement).reportValidity() === false) {
        notify.error('form-validation-error');
        return;
      }
      // get field to update's current value
      const fieldNameInput = document.getElementById('newFieldKeyInput') as HTMLInputElement;
      const originalFieldKey = fieldNameInput.dataset.name; // store previous key
      const newFieldKeyValue = fieldNameInput.value.trim(); // new key from input
      const newFieldKey = normalizeFieldName(newFieldKeyValue);

      let json = {};
      MetadataC.read().then(metadata => {
        if (metadata) {
          json = metadata;
        }
        // If the key (name) is being changed, remove previous field else it will create two separate ones
        if (originalFieldKey && originalFieldKey !== newFieldKey) {
          delete json['extra_fields'][originalFieldKey];
        }
        const field = {};
        // handle field inputs : type, desc, and different type values
        field['type'] = (document.getElementById('newFieldTypeSelect') as HTMLSelectElement).value;
        if (grpSel.value !== '-1') {
          field['group_id'] = parseInt(grpSel.value);
        }
        const fieldDescriptionInput = document.getElementById('newFieldDescriptionInput') as HTMLInputElement;
        if (fieldDescriptionInput.value) {
          field['description'] = fieldDescriptionInput.value.trim();
        }
        // handle values depending on type
        if (['text', 'date', 'datetime-local', 'email', 'time', 'url'].includes(field['type'])) {
          field['value'] = (document.getElementById('newFieldValueInput') as HTMLInputElement).value.trim();
        } else if (['select', 'radio'].includes(field['type'])) {
          field['options'] = [];
          document.getElementById('choicesInputDiv').querySelectorAll('input').forEach(opt => {
            if (opt.value.trim()) {
              field['options'].push(opt.value.trim());
            }
          });
          // make sure at least one value is set
          field['value'] = field['options'][0] || '';
        } else if (field['type'] === ExtraFieldInputType.Number) {
          field['value'] = (document.getElementById('newFieldValueInput') as HTMLInputElement).value.trim();
          field['units'] = [];
          document.getElementById('unitChoicesInputDiv').querySelectorAll('input').forEach(opt => {
            if (opt.value.trim()) {
              field['units'].push(opt.value.trim());
            }
          });
          field['unit'] = field['units'].length > 0 ? field['units'][0] : '';
        } else if (field['type'] === ExtraFieldInputType.Checkbox) {
          field['value'] = (document.getElementById('newFieldCheckboxDefaultSelect') as HTMLSelectElement).value === 'checked' ? 'on' : '';
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

        json['extra_fields'][newFieldKey] = field;

        MetadataC.update(json as ValidMetadata).then(() => {
          clearForm();
          $('#fieldBuilderModal').modal('toggle');
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
      const btn = createButton('remove-self','btn-secondary','-');
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
        // Display new groups and allow editing/deleting them
        const fieldsGroup = document.getElementById('fieldsGroup') as HTMLElement;
        // don't use the update method because we don't need to refresh the inputs
        MetadataC.save(metadata).then(() => {
          const newInputGroup: HTMLDivElement = document.createElement('div');
          newInputGroup.classList.add('input-group', 'mb-1');
          newInputGroup.setAttribute('data-target', 'group-item');
          newInputGroup.setAttribute('data-group-id', String(groupId));

          const inputEl: HTMLInputElement = document.createElement('input');
          inputEl.classList.add('form-control', 'group-name-input');
          inputEl.setAttribute('value', grpOption.text);
          inputEl.setAttribute('data-target', 'group-item');

          const appendDiv = document.createElement('div');
          appendDiv.classList.add('input-group-append');

          const updateButton = createButton('update-fields-group', 'btn-primary');
          const deleteButton = createButton('remove-fields-group', 'btn-secondary');
          const saveIcon = document.createElement('i');
          saveIcon.classList.add('fas', 'fa-save', 'text-white');
          updateButton.appendChild(saveIcon);
          const deleteIcon = document.createElement('i');
          deleteIcon.classList.add('fas', 'fa-trash-alt', 'text-white');
          deleteButton.appendChild(deleteIcon);
          // Append buttons to the appendDiv
          appendDiv.appendChild(updateButton);
          appendDiv.appendChild(deleteButton);
          // Append input and appendDiv to the main groupDiv
          newInputGroup.appendChild(inputEl);
          newInputGroup.appendChild(appendDiv);
          // Append the groupDiv to the parent container
          fieldsGroup.appendChild(newInputGroup);
        });
        // clear input value
        nameInput.value = '';
      });
      // EDIT GROUP
    } else if (el.matches('[data-action="update-fields-group"]')) {
      const groupDiv: HTMLDivElement = el.closest('[data-target="group-item"]');
      const groupId: number = parseInt(groupDiv.dataset.groupId, 10);
      const nameInput = groupDiv.querySelector('.group-name-input') as HTMLInputElement;
      const updatedGroupName = nameInput.value.trim();

      MetadataC.read().then((metadata: ValidMetadata) => {
        const group = metadata.elabftw.extra_fields_groups.find(group => group.id === groupId);
        group.name = updatedGroupName;

        // Update the group in the <select> dropdown
        const optionToUpdate = grpSel.querySelector(`option[value="${groupId}"]`);
        if (optionToUpdate) {
          optionToUpdate.textContent = updatedGroupName;
        }

        MetadataC.update(metadata as ValidMetadata);
      });
      // DELETE GROUP
    } else if (el.matches('[data-action="remove-fields-group"]')) {
      if (!confirm(i18next.t('generic-delete-warning'))) return;

      MetadataC.read().then((metadata: ValidMetadata) => {
        const groupDiv: HTMLDivElement = el.closest('[data-target="group-item"]');
        const groupId: number = parseInt(groupDiv.dataset.groupId, 10);

        // Check if group exists in metadata
        const groupIndex: number = metadata.elabftw.extra_fields_groups.findIndex(group => group.id === groupId);
        if (groupIndex === -1) {
          notify.error('not-found');
          return;
        }

        // Remove the group from `extra_fields_groups`
        metadata.elabftw.extra_fields_groups.splice(groupIndex, 1);
        // Remove the group from the <select> dropdown
        const optionToRemove = grpSel.querySelector(`option[value="${groupId}"]`);
        if (optionToRemove) {
          optionToRemove.remove();
        }

        // Update extra fields from deleted group by moving them to 'Undefined group'
        for (const key in metadata.extra_fields) {
          if (metadata.extra_fields[key].group_id === groupId) {
            delete metadata.extra_fields[key].group_id;
          }
        }

        // Remove the elabftw property if no groups remain
        if (metadata.elabftw.extra_fields_groups.length === 0) {
          delete metadata.elabftw;
        }

        MetadataC.update(metadata as ValidMetadata);
        groupDiv.remove();
      });
    }
  });
});
