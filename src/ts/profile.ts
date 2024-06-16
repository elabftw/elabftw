/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Api } from './Apiv2.class';
import Tab from './Tab.class';
import { collectForm, relativeMoment, reloadElements, notif, notifError } from './misc';
import i18next from 'i18next';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/profile.php') {
    return;
  }

  const ApiC = new Api();

  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  // when selecting the target type, change the category listing
  document.getElementById('importRadioEntityType').addEventListener('change', event => {
    const el = (event.target as HTMLInputElement);
    const categorySelect = document.getElementById('importSelectCategory') as HTMLSelectElement;
    // Remove all options
    while (categorySelect.options.length > 0) {
      categorySelect.remove(1);
    }
    let entityType = el.value;
    if (el.value === 'experiments_templates') {
      entityType = 'experiments';
    }
    ApiC.getJson(`teams/current/${entityType}_categories`).then(categories => {
      // Append new options
      categories.forEach(category => {
        const newOption = document.createElement('option');
        newOption.value = category.id;
        newOption.text = category.title;
        categorySelect.add(newOption);
      });
    });
  });

  // when the file is selected, check for its size, so we can display an error before submit
  document.getElementById('importFileInput')?.addEventListener('change', async function(event) {
    const input = event.target as HTMLInputElement;
    const errorDivId = input.id + '_errorDiv';
    // make sure previous error message is removed first
    document.getElementById(errorDivId)?.remove();
    const maxsize = await ApiC.getJson('import').then(json => json.max_filesize);
    if (input.files[0].size > maxsize) {
      const errorDiv = document.createElement('div');
      errorDiv.classList.add('alert-danger', 'm-2', 'p-2', 'rounded', 'border');
      errorDiv.id = errorDivId;
      errorDiv.innerText = 'Error: file is too large!';
      input.parentNode.appendChild(errorDiv);
    }
  });

  document.getElementById('importFileForm')?.addEventListener('submit', function(event) {
    event.preventDefault();
    // start by making sure the result div is empty
    const resultDiv = document.getElementById('importResultDiv');
    resultDiv.innerHTML = '';
    // disable the submit button and show "please wait"
    const submitBtn = document.getElementById('importFileBtn') as HTMLButtonElement;
    submitBtn.disabled = true;
    const originalBtnContent = submitBtn.textContent;
    submitBtn.textContent = i18next.t('please-wait');
    // now submit the form
    const form = event.target as HTMLFormElement;
    const formData = new FormData(form);
    // prevent the browser from redirecting us
    formData.set('extraParam', 'noRedirect');
    // for templates, we need to force the entity_type
    if (formData.get('entity_type') === 'experiments_templates') {
      formData.set('force_entity_type', '1');
    }
    fetch(form.action, {
      method: 'POST',
      body: formData,
    }).then(async response => {
      if (response.status === 201) {
        notif({msg: 'File imported successfully', res: true});
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnContent;
      } else {
        const msg = await response.text();
        notifError(new Error('Import error: ' + msg));
      }
    }).catch(error => {
      notifError(new Error('Import error: ' + error.message));
    });
  });

  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);

    // CREATE EXPORT
    if (el.matches('[data-action="create-export"]')) {
      const params = collectForm(document.getElementById('exportForm'), false);
      const urlParams = new URLSearchParams(params as URLSearchParams);
      ApiC.post('exports', {
        experiments: urlParams.get('experiments'),
        experiments_templates: urlParams.get('experiments_templates'),
        items: urlParams.get('items'),
        items_types: urlParams.get('items_types'),
        format: urlParams.get('format'),
        changelog: urlParams.get('changelog'),
        pdfa: urlParams.get('pdfa'),
        json: urlParams.get('json'),
      }).then(() => reloadElements(['exportedFilesTable']).then(() => relativeMoment()));

    // DESTROY EXPORT
    } else if (el.matches('[data-action="destroy-export"]')) {
      ApiC.delete(`exports/${el.dataset.id}`).then(() => reloadElements(['exportedFilesTable']).then(() => relativeMoment()));
    }
  });
});
