/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { ApiC } from './api';
import { notify } from './notify';
import { collectForm, rebuildTomSelectOptions, reloadElements, TomSelect } from './misc';
import i18next from './i18n';
import { on } from './handlers';

type TomSelectElement = HTMLSelectElement & { tomselect?: TomSelect };

// populate selects for category/templates when importing csv
const populateSelect = async (select: TomSelectElement, endpoint?: string): Promise<void> => {
  // remove all options except the first one ("Do not use ...")
  for (let i = select.options.length - 1; i >= 1; i--) {
    select.remove(i);
  }
  if (!endpoint) {
    rebuildTomSelectOptions(select);
    return;
  }
  const entries = await ApiC.getJson(endpoint);
  entries.forEach(entry => {
    const newOption = document.createElement('option');
    newOption.value = entry.id;
    newOption.text = entry.title;
    select.add(newOption);
  });
  rebuildTomSelectOptions(select);
};

if (window.location.pathname === '/profile.php') {
  ['importSelectTemplate', 'importSelectCategory', 'importSelectOwner'].forEach(id => {
    const select = document.getElementById(id) as HTMLSelectElement | null;
    if (select) {
      new TomSelect(select, {
        plugins: ['dropdown_input', 'no_active_items'],
      });
    }
  });

  // we use a normal button to trigger the actual file input
  on('show-file-input', () => document.getElementById('importFileInput').click());

  on('create-export', () => {
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
    }).then(() => reloadElements(['exportedFilesTable']));
  });

  on('destroy-export', (el: HTMLElement) => ApiC.delete(`exports/${el.dataset.id}`)
    .then(() => reloadElements(['exportedFilesTable'])));

  on('get-compounds-history', (el: HTMLElement, event: Event) => {
    event.preventDefault();
    const form = document.getElementById('compoundsHistoryForm');
    const params = collectForm(form);
    window.location.href = `/api/v2/reports?scope=compounds_history&format=csv&start=${encodeURIComponent(params['start'])}&end=${encodeURIComponent(params['end'])}`;
  });

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
    const targetRadio = document.getElementById(targetElement) as HTMLInputElement;
    targetRadio.checked = true;
    targetRadio.dispatchEvent(new Event('change', {bubbles: true}));
  });

  // when selecting the target type, change the category and template listings
  document.getElementById('importRadioEntityType').addEventListener('change', async function(event) {
    const el = event.target as HTMLInputElement;
    const selectCategoryDiv = document.getElementById('selectCategoryDiv') as HTMLElement;
    const selectTemplateDiv = document.getElementById('selectTemplateDiv') as HTMLElement;
    const categorySelect = document.getElementById('importSelectCategory') as TomSelectElement;
    const templateSelect = document.getElementById('importSelectTemplate') as TomSelectElement;

    // template select
    const templateTypes: Record<string, string> = {
      experiments: 'experiments_templates',
      items: 'items_types',
    };
    const templateType = templateTypes[el.value];
    const supportsTemplate = templateType !== undefined;
    selectTemplateDiv.hidden = !supportsTemplate;
    templateSelect.disabled = !supportsTemplate;
    if (supportsTemplate) {
      templateSelect.tomselect?.enable();
    } else {
      templateSelect.tomselect?.disable();
    }
    templateSelect.tomselect?.setValue('null', true);
    await populateSelect(templateSelect, templateType);

    // categories select
    selectCategoryDiv.hidden = ['items_types', 'null'].includes(el.value);
    if (selectCategoryDiv.hidden) return;
    categorySelect.tomselect?.setValue('null', true);
    const categoryTypes: Record<string, string> = {
      experiments_templates: 'experiments',
      items: 'resources',
    };
    const categoryType = categoryTypes[el.value] ?? el.value;
    await populateSelect(categorySelect, `teams/current/${categoryType}_categories`);
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
    if (formData.get('template') === 'null') {
      formData.delete('template');
    }
    fetch(form.action, {
      method: 'POST',
      body: formData,
    }).then(async response => {
      if (response.status === 201) {
        notify.success('file-imported');
      } else {
        const error = await response.json();
        notify.error(error.message);
      }
    }).catch(error => {
      notify.error(`Import error: ${error.message}`);
    }).finally(() => {
      submitBtn.removeAttribute('disabled');
      submitBtn.textContent = originalBtnContent;
    });
  });
}
