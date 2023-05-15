/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity, notifError } from './misc';
import { Metadata } from './Metadata.class';
import { ValidMetadata } from './metadataInterfaces';

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('fieldBuilderModal')) {
    return;
  }

  function toggleContentDiv(key: string) {
    const keys = ['classic', 'selectradio', 'checkbox'];
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
    // start by hiding this one, which is only shown for select
    document.getElementById('newFieldContentDiv_select').toggleAttribute('hidden', true);

    switch (fieldType) {
    case 'text':
    case 'date':
    case 'number':
    case 'url':
      valueInput.setAttribute('type', fieldType);
      toggleContentDiv('classic');
      break;
    case 'select':
      document.getElementById('newFieldContentDiv_select').removeAttribute('hidden');
      toggleContentDiv('selectradio');
      break;
    case 'radio':
      toggleContentDiv('selectradio');
      break;
    case 'checkbox':
      toggleContentDiv(fieldType);
      break;
    default:
      break;
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

      const fieldKey = (document.getElementById('newFieldKeyInput') as HTMLInputElement).value;

      const entity = getEntity();
      const MetadataC = new Metadata(entity);
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
        if (['text', 'date', 'number', 'url'].includes(field['type'])) {
          fieldValue = (document.getElementById('newFieldValueInput') as HTMLInputElement).value;
        } else if (['select', 'radio'].includes(field['type'])) {
          field['options'] = [];
          document.querySelectorAll('.newFieldOption').forEach(opt => field['options'].push((opt as HTMLInputElement).value));
          // just take the first one as selected value
          fieldValue = field['options'][0];

        } else if (field['type'] === 'checkbox') {
          fieldValue = (document.getElementById('newFieldCheckboxDefaultSelect') as HTMLSelectElement).value === 'checked' ? 'on' : '';
        }
        field['value'] = fieldValue;
        // get the description
        if ((document.getElementById('newFieldDescriptionInput') as HTMLInputElement).value) {
          field['description'] = (document.getElementById('newFieldDescriptionInput') as HTMLInputElement).value;
        }
        // deal with the blank_on_value
        if ((document.getElementById('blankValueOnDuplicateSwitch') as HTMLInputElement).checked) {
          field['blank_value_on_duplicate'] = true;
        }
        // deal with the multi select
        if ((document.getElementById('newFieldAllowMultiSelect') as HTMLInputElement).checked) {
          field['allow_multi_values'] = true;
        }
        console.log(grpSel.value);
        if (grpSel.value !== '-1') {
          field['group_id'] = grpSel.value;
        }

        json['extra_fields'][fieldKey] = field;

        MetadataC.update(json as ValidMetadata).then(() => { document.location.reload(); });
      });
    // ADD OPTION FOR SELECT OR RADIO
    } else if (el.matches('[data-action="new-field-add-option"]')) {
      const newInput = document.createElement('input');
      newInput.classList.add('form-control');
      newInput.classList.add('newFieldOption');
      newInput.classList.add('mb-1');
      document.getElementById('choicesInputDiv').appendChild(newInput);
    // SAVE NEW GROUP
    } else if (el.matches('[data-action="save-new-fields-group"]')) {
      const nameInput = (document.getElementById('newFieldsGroupKeyInput') as HTMLInputElement);

      const entity = getEntity();
      const MetadataC = new Metadata(entity);
      // get the current metadata
      MetadataC.read().then((metadata: ValidMetadata) => {
        // make sure we have an elabftw property
        if (!Object.prototype.hasOwnProperty.call(metadata, 'elabftw')) {
          metadata['elabftw'] = {};
        }
        // default groupid
        let groupId = 1;
        if (Object.prototype.hasOwnProperty.call(metadata.elabftw, 'groups')) {
          // find out what will be the next group id by looking for the highest group.id and adding 1
          groupId = metadata.elabftw.groups.reduce((prev, current) => {
            return (prev.id > current.id) ? prev : current;
          }).id + 1;
        } else {
          // create an empty array if no groups exist
          metadata.elabftw.groups = [];
        }

        const grpOption = document.createElement('option');
        grpOption.value = String(groupId);
        grpOption.text = nameInput.value;
        grpSel.add(grpOption);
        // select the freshly added group because it is the most likely to be picked now that we just added it
        grpSel.selectedIndex = grpSel.options.length - 1;

        // save the new group in metadata
        metadata.elabftw.groups.push({'id': groupId, 'name': nameInput.value});
        // don't use the update method because we don't need to refresh the inputs
        MetadataC.save(metadata);
        // clear input value
        nameInput.value = '';
      });
    }
  });
});
