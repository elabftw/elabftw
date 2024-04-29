/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { InputType, Malle, SelectOptions } from '@deltablot/malle';
import { Api } from './Apiv2.class';
import { getEntity, updateCatStat, relativeMoment, reloadElement } from './misc';
import { EntityType, Model } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {

  if (!document.getElementById('info')) {
    return;
  }
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in view mode
  if (about.page !== 'view' && about.page !== 'template-view') {
    return;
  }

  // add the title in the page name (see #324)
  document.title = document.getElementById('documentTitle').textContent + ' - eLabFTW';

  const entity = getEntity();
  const ApiC = new Api();

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CREATE COMMENT
    if (el.matches('[data-action="create-comment"]')) {
      const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
      ApiC.post(`${entity.type}/${entity.id}/${Model.Comment}`, {'comment': content}).then(() => {
        reloadElement('commentsDiv').then(() => {
          malleableComments.listen();
          relativeMoment();
        });
      });

    // DESTROY COMMENT
    } else if (el.matches('[data-action="destroy-comment"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/${Model.Comment}/${el.dataset.id}`).then(() => el.parentElement.parentElement.remove());
      }
    }
  });

  if (about.isanon) {
    return;
  }

  // UPDATE MALLEABLE COMMENT
  const malleableComments = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: async (value, original) => {
      const resp = await ApiC.patch(`${entity.type}/${entity.id}/${Model.Comment}/${original.dataset.id}`, {'comment': value});
      const json = await resp.json();
      // we reload all so the edition date is also reloaded
      reloadElement('commentsDiv').then(() => {
        malleableComments.listen();
        relativeMoment();
      });
      return json.comment;
    },
    inputType: InputType.Textarea,
    listenOn: '.comment.editable',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // UPDATE MALLEABLE STATUS
  interface Status extends SelectOptions {
    id: number;
    color: string;
    title: string;
  }

  const notsetOpts = {id: null, title: i18next.t('not-set'), color: 'bdbdbd'};

  let categoryEndpoint = `${EntityType.ItemType}`;
  let statusEndpoint = `${Model.Team}/current/items_status`;
  if (entity.type === EntityType.Experiment || entity.type === EntityType.Template) {
    categoryEndpoint = `${Model.Team}/current/experiments_categories`;
    statusEndpoint = `${Model.Team}/current/experiments_status`;
  }

  const malleableStatus = new Malle({
    // use the after hook to add the colored circle before text
    after: (elem: HTMLElement, _: Event, value: string) => {
      const icon = document.createElement('i');
      icon.classList.add('fas', 'fa-circle', 'mr-1');
      icon.style.color = `#${value}`;
      elem.insertBefore(icon, elem.firstChild);
      return true;
    },
    // use the onEdit hook to set the correct selected option (because of the circle icon interference)
    onEdit: async (original: HTMLElement, _: Event, input: HTMLInputElement|HTMLSelectElement) => {
      // the options can be a promise, so we need to use await or its length will be 0 here
      const opts = await (input as HTMLSelectElement).options;
      for (let i = 0; i < opts.length; i++) {
        if (opts.item(i).textContent === original.textContent.trim()) {
          opts.item(i).selected = true;
          break;
        }
      }
      return true;
    },
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value: string, original: HTMLElement) => updateCatStat(original.dataset.target, entity, value).then(color => {
      original.style.setProperty('--bg', `#${color}`);
      return color;
    }),
    inputType: InputType.Select,
    selectOptionsValueKey: 'id',
    selectOptionsTextKey: 'title',
    selectOptions: ApiC.getJson(statusEndpoint).then(json => Array.from(json)).then((statusArr: Array<Status>) => {
      statusArr.unshift(notsetOpts);
      return statusArr;
    }),
    listenOn: '.malleableStatus',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // UPDATE MALLEABLE CATEGORY

  const malleableCategory = new Malle({
    // use the after hook to change the background color of the new element
    after: (elem: HTMLElement, _: Event, value: string) => {
      elem.style.setProperty('--bg', `#${value}`);
      return true;
    },
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: (value: string, original: HTMLElement) => updateCatStat(original.dataset.target, entity, value),
    inputType: InputType.Select,
    selectOptionsValueKey: 'id',
    selectOptionsTextKey: 'title',
    selectOptions: ApiC.getJson(categoryEndpoint).then(json => [notsetOpts, ...Array.from(json)]),
    listenOn: '.malleableCategory',
    returnedValueIsTrustedHtml: false,
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // listen on existing comments
  malleableComments.listen();
  malleableStatus.listen();
  malleableCategory.listen();
});
