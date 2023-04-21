/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from 'i18next';
import { InputType, Malle } from '@deltablot/malle';
import { Api } from './Apiv2.class';
import { getEntity, updateCategory, relativeMoment, reloadElement, showContentPlainText } from './misc';
import { EntityType, Model } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {

  if (!document.getElementById('info')) {
    return;
  }
  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;

  // only run in view mode
  if (about.page !== 'view') {
    return;
  }

  // add the title in the page name (see #324)
  document.title = document.getElementById('documentTitle').textContent + ' - eLabFTW';

  const entity = getEntity();
  const ApiC = new Api();

  // Add click listener and do action based on which element is clicked
  document.querySelector('.real-container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // SHOW CONTENT OF PLAIN TEXT FILES
    if (el.matches('[data-action="show-plain-text"]')) {
      showContentPlainText(el);
    // CREATE COMMENT
    } else if (el.matches('[data-action="create-comment"]')) {
      const content = (document.getElementById('commentsCreateArea') as HTMLTextAreaElement).value;
      ApiC.post(`${entity.type}/${entity.id}/${Model.Comment}`, {'comment': content}).then(() => reloadElement('commentsDiv'));

    // DESTROY COMMENT
    } else if (el.matches('[data-action="destroy-comment"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        ApiC.delete(`${entity.type}/${entity.id}/${Model.Comment}/${el.dataset.id}`).then(() => reloadElement('commentsDiv'));
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
      reloadElement('commentsDiv');
      return json.comment;
    },
    inputType: InputType.Textarea,
    listenOn: '.comment.editable',
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // UPDATE MALLEABLE CATEGORY
  let category;
  // TODO make it so it calls only on trigger!
  if (entity.type === EntityType.Experiment) {
    category = ApiC.getJson(`${Model.Team}/${about.team}/status`).then(json => Array.from(json));
  } else {
    category = ApiC.getJson(`${EntityType.ItemType}`).then(json => Array.from(json));
  }
  const malleableCategory = new Malle({
    // use the after hook to change the background color of the new element
    after: (original, _, value) => {
      category.then(categoryArr => {
        const cat = categoryArr.find(cat => cat.category === value);
        original.style.setProperty('--bg', `#${cat.color}`);
      });
      return true;
    },
    cancel : i18next.t('cancel'),
    cancelClasses: ['btn', 'btn-danger', 'mt-2', 'ml-1'],
    inputClasses: ['form-control'],
    fun: value => updateCategory(entity, value),
    inputType: InputType.Select,
    selectOptionsValueKey: 'category_id',
    selectOptionsTextKey: 'category',
    selectOptions: category.then(categoryArr => categoryArr),
    listenOn: '.malleableCategory',
    submit : i18next.t('save'),
    submitClasses: ['btn', 'btn-primary', 'mt-2'],
    tooltip: i18next.t('click-to-edit'),
  });

  // listen on existing comments
  malleableComments.listen();
  malleableCategory.listen();

  new MutationObserver(() => {
    malleableCategory.listen();
  }).observe(document.getElementById('main_section'), {childList: true});

  // add an observer so new comments will get an event handler too
  new MutationObserver(() => {
    malleableComments.listen();
    relativeMoment();
  }).observe(document.getElementById('commentsDiv'), {childList: true});
  // END COMMENTS
});
