/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2023 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
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

  function createButton(action: string, btnType: string, content?: string): HTMLButtonElement {
    const btn: HTMLButtonElement = document.createElement('button');
    btn.setAttribute('type', 'button');
    btn.dataset.action = action;
    btn.classList.add('btn', btnType);
    btn.textContent = content ?? '';
    return btn;
  }

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    if (el.matches('[data-action="metadata-edit-field"]')) {
      $('#' + el.dataset.target).modal('toggle');
      if (!document.getElementById('fieldBuilderModal')) {
        return;
      }
      // convert save button from modal to edit button
      const saveButton = document.querySelector('[data-action="save-new-field"]') as HTMLButtonElement;
      if (saveButton) {
        saveButton.dataset.action="edit-extra-field";
        saveButton.textContent = i18next.t('Edit');
      }
      // convert "default value" to current value to edit
      const label = document.querySelector('label[for="newFieldValueInput"]') as HTMLLabelElement;
      if (label) label.textContent = i18next.t('Current value');

      MetadataC.read().then(metadata => {
        const extraField = el.parentElement.parentElement.closest('div').querySelector('label');
        if (!extraField) {
          notifError(new Error(i18next.t('Field label not found')));
          return;
        }
        // store current field for update
        const fieldName = extraField.innerText.trim();

        // MetadataC.read().then(metadata => {
        //   delete metadata.extra_fields[fieldName];
        //   MetadataC.save(metadata as ValidMetadata);
        // });

        // once modal is up, check fields to update
        const fieldGroupSelect = document.getElementById('newFieldGroupSelect') as HTMLSelectElement;
        const fieldTypeSelect = document.getElementById('newFieldTypeSelect') as HTMLSelectElement;
        const fieldNameInput = document.getElementById('newFieldKeyInput') as HTMLInputElement;
        const fieldDescriptionInput = document.getElementById('newFieldDescriptionInput') as HTMLInputElement;
        const fieldValueInput = document.getElementById('newFieldValueInput') as HTMLInputElement;
        // prefill modal with current extraField values
        const fieldData = metadata.extra_fields[fieldName];
        if (!fieldData) {
          notifError(new Error(i18next.t('Field not found in metadata')));
          return;
        }
        fieldGroupSelect.value = fieldData.group_id ?? '-1';
        fieldTypeSelect.value = fieldData.type;
        fieldNameInput.value = fieldName;
        fieldDescriptionInput.value = fieldData.description ?? '';
        fieldValueInput.value = fieldData.value ?? '';

        // delete current metadata before sending new one
        delete metadata.extra_fields[fieldName];
        MetadataC.save(metadata as ValidMetadata);
      });
    }
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
          // keep track of the selected group, so it stays the same and it's easy to add another input in the same group afterwards
          const selectedGroup = grpSel.value;
          // clear all fields
          form.reset();
          // restore original value
          grpSel.value = selectedGroup;
        });
      });
    // EDIT EXTRA FIELD
    } else if(el.matches('[data-action="edit-extra-field"]')) {
      let json = {};
      MetadataC.read().then(metadata => {
        const fieldNameInput = document.getElementById('newFieldKeyInput') as HTMLInputElement;
        const fieldKey = (document.getElementById('newFieldKeyInput') as HTMLInputElement).value.trim();
        const fieldTypeSelect = document.getElementById('newFieldTypeSelect') as HTMLSelectElement;
        const fieldGroupSelect = document.getElementById('newFieldGroupSelect') as HTMLSelectElement;
        const fieldDescriptionInput = document.getElementById('newFieldDescriptionInput') as HTMLInputElement;
        const currentValueInput = document.getElementById('newFieldValueInput') as HTMLInputElement;

        // console.log('metadata before', metadata);
        console.log("fieldnameinput",fieldNameInput);
        console.log("fieldnameinput value",fieldNameInput.value);
        delete metadata.extra_fields[fieldNameInput.value];
        // console.log('metadata after', metadata);

        MetadataC.save(metadata as ValidMetadata);

        if (metadata) {
          json = metadata;
        }
        // if (!Object.prototype.hasOwnProperty.call(json, 'extra_fields')) {
        //   json['extra_fields'] = {};
        // }

        const object = {
          type: fieldTypeSelect.value,
          group_id: fieldGroupSelect.value !== '-1' ? parseInt(fieldGroupSelect.value) : '-1',
          description: fieldDescriptionInput.value,
          value: currentValueInput.value
        };

        json['extra_fields'][fieldKey] = object;
        // console.log(json, metadata);
        MetadataC.update(json as ValidMetadata).then(() => {
          const form = (document.getElementById('newFieldForm') as HTMLFormElement);
          // remove all extra inputs (dropdown and radio)
          form.querySelectorAll('.is-extra-input').forEach(i => i.parentElement.remove());
          // keep track of the selected group, so it stays the same and it's easy to add another input in the same group afterwards
          const selectedGroup = grpSel.value;
          // clear all fields
          form.reset();
          // restore original value
          grpSel.value = selectedGroup;
        });
        //
        // MetadataC.update(metadata as ValidMetadata).then(() => {
        //   const form = (document.getElementById('newFieldForm') as HTMLFormElement);
        //   // remove all extra inputs (dropdown and radio)
        //   form.querySelectorAll('.is-extra-input').forEach(i => i.parentElement.remove());
        //   // keep track of the selected group, so it stays the same and it's easy to add another input in the same group afterwards
        //   const selectedGroup = grpSel.value;
        //   // clear all fields
        //   form.reset();
        //   // restore original value
        //   grpSel.value = selectedGroup;
        // });
        //
        // // new field select inputs
        // const fieldGroupSelect = document.getElementById('newFieldGroupSelect') as HTMLSelectElement;
        // const fieldTypeSelect = document.getElementById('newFieldTypeSelect') as HTMLSelectElement;
        // const fieldNameInput = document.getElementById('newFieldKeyInput') as HTMLInputElement;
        // const fieldDescriptionInput = document.getElementById('newFieldDescriptionInput') as HTMLInputElement;
        // const fieldValueInput = document.getElementById('newFieldValueInput') as HTMLInputElement;
        //
        // fieldNameInput.dataset.name = fieldNameInput.value;
        //
        // const originalValue = fieldNameInput.value;
        // console.log(fieldNameInput.dataset.name, fieldValueInput.value);
        //
        // // console.log(originalValue, fieldNameInput.value);
        // return;
        // // const updatedFieldName = fieldNameInput.value.trim();
        // // const originalFieldName = fieldNameInput.dataset.originalName ?? updatedFieldName; // get original name if available
        // //
        // // console.log(updatedFieldName, originalFieldName);
        // //
        // // if (!metadata.extra_fields[originalFieldName]) {
        // //   notifError(new Error(i18next.t('Field not found in metadata')));
        // //   return;
        // // }
        // //
        // // console.log("before:", updatedFieldName, originalFieldName);
        // // if (updatedFieldName !== originalFieldName) {
        // //   console.log("after", updatedFieldName, originalFieldName);
        // //   return;
        // //   // Rename key: Copy old data to new key, then delete old key
        // //   metadata.extra_fields[updatedFieldName] = { ...metadata.extra_fields[originalFieldName] };
        // //   delete metadata.extra_fields[originalFieldName];
        // // }
        // //
        // // // Update metadata with new values
        // // metadata.extra_fields[updatedFieldName].type = fieldTypeSelect.value;
        // // metadata.extra_fields[updatedFieldName].group_id = fieldGroupSelect.value !== '-1' ? parseInt(fieldGroupSelect.value) : undefined;
        // // metadata.extra_fields[updatedFieldName].description = fieldDescriptionInput.value;
        // // metadata.extra_fields[updatedFieldName].value = currentValueInput.value;
        // //
        // // // Save the updated metadata
        // // MetadataC.update(metadata as ValidMetadata);
      })
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
          notifError(new Error(i18next.t('Group not found')));
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
