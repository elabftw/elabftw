/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import { Malle } from '@deltablot/malle';
import FavTag from './FavTag.class';
import Tag from './Tag.class';
import i18next from 'i18next';
import { addAutocompleteToTagInputs, getCheckedBoxes, notif, reloadEntitiesShow, getEntity, reloadElement } from './misc';

document.addEventListener('DOMContentLoaded', () => {
  let type = $('#info').data('type');
  if (type === undefined) {
    type = 'experiments_templates';
  }

  const entity = getEntity();
  const TagC = new Tag(entity);

  // CREATE TAG
  $(document).on('keypress blur', '.createTagInput', function(e) {
    if ($(this).val() === '') {
      return;
    }
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      TagC.create($(this).val() as string).then(json => {
        if (json.res === false) {
          notif(json);
        }
        reloadElement('tags_div_' + entity.id);
        $(this).val('');
      });
    }
  });

  // CREATE TAG for several entities
  $(document).on('keypress blur', '.createTagInputMultiple', function(e) {
    if ($(this).val() === '') {
      return;
    }
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      // get the ids of selected entities
      const checked = getCheckedBoxes();
      if (checked.length === 0) {
        const json = {
          'msg': 'Nothing selected!',
          'res': false,
        };
        notif(json);
        return;
      }

      // loop over it and add tags
      const results = [];
      checked.forEach(checkBox => {
        results.push(TagC.create((document.getElementById('createTagInputMultiple') as HTMLInputElement).value as string, checkBox['id']));
      });

      Promise.all(results).then(() => {
        reloadEntitiesShow();
      });

      $(this).val('');
    }
  });

  // CREATE FAVORITE TAG
  $(document).on('keypress blur', '.createFavTagInput', function(e) {
    const FavTagC = new FavTag();
    if ($(this).val() === '') {
      return;
    }
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      FavTagC.create($(this).val() as string).then(json => {
        if (json.res === false) {
          notif(json);
        }
        reloadElement('favtagsPanel');
        $(this).val('');
      });
    }
  });

  // AUTOCOMPLETE

  addAutocompleteToTagInputs();
  if (document.getElementById('favtagsPanel')) {
    new MutationObserver(() => addAutocompleteToTagInputs())
      .observe(document.getElementById('favtagsPanel'), {childList: true, subtree: true});
  }

  // make the tag editable (on admin page)
  const malleableTags = new Malle({
    cancel : i18next.t('cancel'),
    cancelClasses: ['button', 'btn', 'btn-danger', 'ml-1'],
    inputClasses: ['form-control'],
    formClasses: ['d-inline-flex'],
    fun: (value, original) => {
      TagC.update(value, parseInt(original.dataset.tagid, 10));
      return value;
    },
    listenOn: '.tag.editable',
    tooltip: i18next.t('click-to-edit'),
    submit : i18next.t('save'),
    submitClasses: ['button', 'btn', 'btn-primary', 'ml-1'],
  }).listen();

  if (document.getElementById('tagMgrDiv')) {
    new MutationObserver(() => {
      malleableTags.listen();
    }).observe(document.getElementById('tagMgrDiv'), {childList: true});
  }

  // MAIN ACTION LISTENER
  document.querySelector('.real-container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    // DEDUPLICATE (from admin panel/tag manager)
    if (el.matches('[data-action="deduplicate-tag"]')) {
      TagC.deduplicate().then(json => {
        notif({
          'res': true,
          'msg': `Deduplicated ${json.value} tags`,
        });
        reloadElement('tagMgrDiv');
      });
    // UNREFERENCE (remove link between tag and entity)
    } else if (el.matches('[data-action="unreference-tag"]')) {
      if (confirm(i18next.t('tag-delete-warning'))) {
        TagC.unreference(parseInt(el.dataset.tagid, 10)).then(() => reloadElement(`tags_div_${entity.id}`));
      }
    // DESTROY (from admin panel/tag manager)
    } else if (el.matches('[data-action="destroy-tag"]')) {
      if (confirm(i18next.t('tag-delete-warning'))) {
        TagC.destroy(parseInt(el.dataset.tagid, 10)).then(() => reloadElement('tagMgrDiv'));
      }
    }
  });

});
