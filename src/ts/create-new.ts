/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { ApiC } from './api';
import 'bootstrap/js/src/modal.js';
import {
  getEntityTypeFromPage,
  toggleIcon,
} from './misc';
import i18next from './i18n';
import { EntityType } from './interfaces';
import { on } from './handlers';

//////////////////////////////////////
// CREATE NEW CODE                  //
// code related to create new modal //
//////////////////////////////////////
function setTypeRadio(type: EntityType, scope: string = '') {
  const createBtn = document.getElementById('createNoTplBtn');
  if (!scope) {
    scope = (document.querySelector('#createNewTemplateScope button[data-action="scope-change"].active') as HTMLButtonElement).dataset.value;
  }
  document.getElementById('createNoTplBtn').dataset.type = type;
  createBtn.innerText = i18next.t(`create-one-${type}`);
  const templateToggleBtn = document.getElementById('createTplToggleBtn');
  const createNewTemplatesDiv = document.getElementById('createNewTemplatesDiv');
  templateToggleBtn.removeAttribute('disabled');
  // scope
  document.getElementById('createNewTemplateScope').querySelectorAll('button').forEach(btn => {
    btn.dataset.target = `scope_${type}`;
    btn.dataset.setInJs = 'createNewTemplateScope_selected';
  });
  // set radio button checked
  document.querySelectorAll(`input[type="radio"][name="type"]`).forEach((radio: HTMLInputElement)=> {
    radio.removeAttribute('checked');
    if (radio.value === type) {
      radio.checked = true;
    }
  });
  const manageTplLink = document.getElementById('manageTplLink') as HTMLAnchorElement;
  manageTplLink.href = type === EntityType.Experiment || type === EntityType.Template ? 'templates.php' : 'resources-templates.php';
  if (type === EntityType.Template || type === EntityType.ItemType) {
    templateToggleBtn.setAttribute('disabled', 'disabled');
    toggleIcon(templateToggleBtn, true);
    createNewTemplatesDiv.setAttribute('hidden', 'hidden');
  } else {
    const templatesEndpoint = type === EntityType.Experiment ? EntityType.Template : EntityType.ItemType;
    ApiC.getJson(`${templatesEndpoint}/?fastq&scope=${scope}`).then(templates => {
      renderTemplates(templates);
      toggleCategoryList(type);
    });
  }
}

function toggleCategoryList(type: EntityType) {
  document.querySelectorAll('.createNewCategoryList').forEach(el => el.setAttribute('hidden', 'hidden'));
  document.getElementById(`createNewCategoryList_${type}`).removeAttribute('hidden');
}
function onTypeChange(ev: Event) {
  const el = ev.currentTarget;
  if (!(el instanceof HTMLInputElement)) return;
  // with radios, change fires on the one that became checked
  setTypeRadio(el.value as EntityType);
}

on('filter-category', (el: HTMLElement) => {
  const table = document.getElementById(el.dataset.target);
  document.querySelectorAll('[data-action="filter-category"]').forEach(btn => btn.classList.remove('btnPressed'));
  el.classList.add('btnPressed');
  table.querySelectorAll('tr').forEach((row: HTMLTableRowElement|HTMLUListElement) => {
    if (row.dataset.catid === el.dataset.catid) {
      row.removeAttribute('hidden');
    } else {
      row.hidden = true;
    }
  });
  document.querySelector('[data-action="reset-filter-category"]').removeAttribute('disabled');
});

on('reset-filter-category', () => {
  const table = document.getElementById('tplCreateNewTable');
  table.querySelectorAll('tr')?.forEach((row: HTMLTableRowElement|HTMLUListElement) => row.removeAttribute('hidden'));
  document.querySelector('[data-action="reset-filter-category"]').setAttribute('disabled', 'disabled');
  document.querySelectorAll('[data-action="filter-category"]').forEach(btn => btn.classList.remove('btnPressed'));
});

on('toggle-create-modal', (el: HTMLElement) => {
  // if a data-page is present, force type
  const entityType = el.dataset.entityType ? el.dataset.entityType as EntityType : getEntityTypeFromPage();
  setTypeRadio(entityType);
  $('#createModal').modal('toggle');
});

on('toggle-templates', (el: HTMLElement) => {
  const createNewTemplatesDiv = document.getElementById('createNewTemplatesDiv');
  toggleIcon(el, !createNewTemplatesDiv.hasAttribute('hidden'));
  createNewTemplatesDiv.toggleAttribute('hidden');
});

interface Templates {
  id: number;
  title: string;
  fullname: string;
}

const templateCols: (keyof Templates)[] = [
  'title',
  'fullname',
  'id',
];

function renderTemplates(templates: Templates[]): void {
  const tbody = document.getElementById('tplCreateNewTable') as HTMLTableSectionElement;
  const templateRow = document.getElementById('templateRow') as HTMLTemplateElement;

  tbody.replaceChildren(
    ...templates.map(template => {
      const row = templateRow.content.firstElementChild!.cloneNode(true) as HTMLTableRowElement;
      row.dataset.catid = String(template['category'] ?? -1);
      const cells = Array.from(row.children) as HTMLTableCellElement[];

      templateCols.forEach((key, i) => {
        // ACTIONS
        if (key === 'id') {
          const createBtn = cells[i].querySelector('button[data-action="create-entity"]') as HTMLButtonElement;
          createBtn.dataset.type = template['type'];
          createBtn.dataset.tplid = String(template[key]);
          const viewLink = cells[i].querySelector('a') as HTMLAnchorElement;
          viewLink.href = `${template['page']}?mode=view&id=${template['id']}`;
          viewLink.classList.add('btn', 'btn-ghost');
          viewLink.title = i18next.t('view-template');
          viewLink.ariaLabel = i18next.t('view-template');

        // TITLE
        } else if (key === 'title') {
          const catspan = document.createElement('span');
          if (template['category_title']) {
            catspan.classList.add('catstat-btn', 'category-btn', 'mr-2');
            catspan.title = i18next.t('filter-category');
            catspan.dataset.action='filter-category';
            catspan.dataset.target='tplCreateNewTable';
            catspan.dataset.catid=String(template['category']);
            catspan.style.setProperty('--bg', `#${template['category_color']}`);
            catspan.innerText = template['category_title'];
          }
          const statusspan = document.createElement('span');
          if (template['status_title']) {
            statusspan.classList.add('catstat-btn', 'status-btn', 'mr-2');
            statusspan.style.setProperty('--bg', `#${template['status_color']}`);
            statusspan.innerText = template['status_title'];
          }
          cells[i].textContent = String(template[key]);
          cells[i].prepend(statusspan);
          cells[i].prepend(catspan);
        } else {
          cells[i].textContent = String(template[key]);
        }

      });

      return row;
    }),
  );
}

const typeRadios = document.querySelectorAll<HTMLInputElement>(
  'input[type="radio"][name="type"]'
);

typeRadios.forEach(radio => {
  radio.addEventListener('change', onTypeChange);
});
