/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { getEntity, notifError } from './misc';
import { Metadata } from './Metadata.class';

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
    switch (fieldType) {
    case 'text':
    case 'date':
    case 'number':
    case 'url':
      valueInput.setAttribute('type', fieldType);
      toggleContentDiv('classic');
      break;
    case 'select':
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
        // make sure we have an extra_fields
        if (! ('extra_fields' in json)) {
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
        json['extra_fields'][fieldKey] = field;
        MetadataC.update(json).then(() => { document.location.reload(); });
      });
    // ADD OPTION FOR SELECT OR RADIO
    } else if (el.matches('[data-action="new-field-add-option"]')) {
      const newInput = document.createElement('input');
      newInput.classList.add('form-control');
      newInput.classList.add('newFieldOption');
      document.getElementById('choicesInputDiv').appendChild(newInput);
    }
  });
});
