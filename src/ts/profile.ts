/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Api } from './Apiv2.class';
import { Notification } from './Notifications.class';
import Tab from './Tab.class';
import { collectForm, relativeMoment, reloadElements } from './misc';
import i18next from 'i18next';

document.addEventListener('DOMContentLoaded', () => {
  if (window.location.pathname !== '/profile.php') {
    return;
  }

  const ApiC = new Api();
  const notify = new Notification();
  const TabMenu = new Tab();
  TabMenu.init(document.querySelector('.tabbed-menu'));

  document.getElementById('importFileInput')?.addEventListener('change', async function(event) {
    const importOptionsDiv = document.getElementById('importOptionsDiv') as HTMLElement;
    const attachedFile = document.getElementById('attachedFile') as HTMLElement;
    attachedFile.removeAttribute('hidden');
    const input = event.target as HTMLInputElement;
    // display the selected file name on screen
    const fileName = input.files[0]?.name || '';
    const fileNameDiv = document.getElementById('fileName');
    fileNameDiv.textContent = fileName;
    // make sure previous error message is removed first
    fileNameDiv.classList.remove('alert-danger', 'm-2', 'p-2', 'rounded', 'border');
    // when the file is selected, validate the file size before showing options or proceeding
    const maxsize = await ApiC.getJson('import').then(json => json.max_filesize);
    if (input.files[0].size > maxsize) {
      notify.error('file-too-large');
      fileNameDiv.classList.add('alert-danger', 'm-2', 'p-2', 'rounded', 'border');
      importOptionsDiv.setAttribute('hidden', 'hidden');
      return;
    }
    importOptionsDiv.removeAttribute('hidden');
    // toggle the eln/csv options depending on file extension
    const isEln = input.files[0].name.endsWith('.eln');
    document.querySelectorAll('[data-showif="eln"]')
      .forEach((el: HTMLElement) => isEln ? el.removeAttribute('hidden') : el.hidden = true);
    // we want to let the .eln file decide which kind of entry it is by default
    const targetElement = 'entityTypeRadio' + (isEln ? 'None' : 'Experiments');
    (document.getElementById(targetElement) as HTMLInputElement).checked = true;
  });

  // when selecting the target type, change the category listing
  document.getElementById('importRadioEntityType').addEventListener('change', async function(event) {
    const el = (event.target as HTMLInputElement);
    const selectCategoryDiv = document.getElementById('selectCategoryDiv') as HTMLElement;
    selectCategoryDiv.removeAttribute('hidden');
    if (['items_types', 'null'].includes(el.value)) {
      selectCategoryDiv.hidden = true;
      return;
    }
    const categorySelect = document.getElementById('importSelectCategory') as HTMLSelectElement;
    // Remove all options except the first one
    for (let i = categorySelect.options.length - 1; i >= 1; i--) {
      categorySelect.remove(i);
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
    if (formData.get('entity_type') === 'null') {
      formData.delete('entity_type');
    }
    if (formData.get('category') === 'null') {
      formData.delete('category');
    }
    fetch(form.action, {
      method: 'POST',
      body: formData,
    }).then(async response => {
      if (response.status === 201) {
        notify.success('file-imported');
      } else {
        const error = await response.json();
        notify.error(error.description);
      }
    }).catch(error => {
      notify.error(`Import error: ${error.message}`);
    }).finally(() => {
      submitBtn.removeAttribute('disabled');
      submitBtn.textContent = originalBtnContent;
    });
  });

  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);

    if (el.matches('[data-action="show-file-input"]')) {
      document.getElementById('importFileInput').click();
    }
    // CREATE EXPORT
    if (el.matches('[data-action="create-export"]')) {
      const params = collectForm(document.getElementById('exportForm'));
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
