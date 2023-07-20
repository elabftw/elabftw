/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity, notifError } from './misc';
import { Metadata } from './Metadata.class';
import { ValidMetadata, ExtraFieldInputType } from './metadataInterfaces';
import { Api } from './Apiv2.class';
import $ from 'jquery';


document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('fieldBuilderModal')) {
    return;
  }

  const entity = getEntity();

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

    switch (fieldType as ExtraFieldInputType) {
    case ExtraFieldInputType.Text:
    case ExtraFieldInputType.Date:
    case ExtraFieldInputType.DateTime:
    case ExtraFieldInputType.Email:
    case ExtraFieldInputType.Number:
    case ExtraFieldInputType.Url:
    case ExtraFieldInputType.Time:
      valueInput.setAttribute('type', fieldType);
      toggleContentDiv('classic');
      break;
    case ExtraFieldInputType.Select:
      document.getElementById('newFieldContentDiv_select').removeAttribute('hidden');
      toggleContentDiv('selectradio');
      break;
    case ExtraFieldInputType.Radio:
      toggleContentDiv('selectradio');
      break;
    case ExtraFieldInputType.Checkbox:
      toggleContentDiv(fieldType);
      break;
    default:
      break;
    }
  });

  document.getElementById('fieldLoaderModal').addEventListener('click', event => {
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
        const applyBtn = (document.getElementById('applyMetadataLoadBtn') as HTMLButtonElement);
        applyBtn.removeAttribute('disabled');
        const warningTxt = document.getElementById('loadMetadataWarning');
        warningTxt.removeAttribute('hidden');
      });
    } else if (el.matches('[data-action="load-metadata-from-textarea"]')) {
      const textarea = (document.getElementById('loadMetadataTextarea') as HTMLInputElement);
      const MetadataC = new Metadata(entity);
      ApiC.patch(`${entity.type}/${entity.id}`, {metadata: textarea.value}).then(() => {
        MetadataC.display('edit');
        textarea.value = '';
        $('#fieldLoaderModal').modal('hide');
      });
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
        if (['text', 'date', 'datetime-local', 'email', 'number', 'time', 'url'].includes(field['type'])) {
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
        // deal with the required attribute
        if ((document.getElementById('requiredSwitch') as HTMLInputElement).checked) {
          field['required'] = true;
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
        MetadataC.update(json as ValidMetadata).then(() => { $('#fieldBuilderModal').modal('hide'); });
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
