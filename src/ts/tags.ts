/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import FavTag from './FavTag.class';
import Tag from './Tag.class';
import i18next from 'i18next';
import { getCheckedBoxes, notif, reloadEntitiesShow, getEntity, reloadElement } from './misc';
import { Ajax } from './Ajax.class';
import { Payload, Method, Model, Action, Target } from './interfaces';

document.addEventListener('DOMContentLoaded', () => {
  let type = $('#info').data('type');
  if (type === undefined) {
    type = 'experiments_templates';
  }

  const AjaxC = new Ajax();
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
        $('#tags_div_' + entity.id).load(window.location.href + ' #tags_div_' + entity.id + ' > *');
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
  const cache = {};

  function addAutocompleteToTagInputs(): void {
    ($('[data-autocomplete="tags"]') as JQuery<HTMLInputElement>).autocomplete({
      source: function(request: any, response: any) {
        const term  = request.term;
        if (term in cache) {
          response(cache[term]);
          return;
        }
        const payload: Payload = {
          method: Method.GET,
          action: Action.Read,
          model: Model.Tag,
          entity: entity,
          target: Target.List,
          content: term,
        };
        AjaxC.send(payload).then(json => {
          cache[term] = json.value;
          response(json.value);
        });
      },
    });
  }

  addAutocompleteToTagInputs();
  if (document.getElementById('favtagsPanel')) {
    new MutationObserver(() => addAutocompleteToTagInputs())
      .observe(document.getElementById('favtagsPanel'), {childList: true, subtree: true});
  }

  // make the tag editable (on admin.ts)
  $(document).on('mouseenter', '.tag-editable', function() {
    ($(this) as any).editable(function(value) {
      // we need to have an entity so the Tags model is built correctly
      // also it's a mandatory constructor param for Tag.class.ts
      TagC.update(value, $(this).data('tagid'));
      return (value);
    }, {
      tooltip : i18next.t('click-to-edit'),
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline',
    });
  });

  // UNREFERENCE (remove link between tag and entity)
  $(document).on('click', '.tagUnreference', function() {
    if (confirm(i18next.t('tag-delete-warning'))) {
      TagC.unreference($(this).data('tagid')).then(() => {
        $('#tags_div_' + entity.id).load(window.location.href + ' #tags_div_' + entity.id + ' > *');
      });
    }
  });

  // DEDUPLICATE (from admin panel/tag manager)
  $(document).on('click', '.tagDeduplicate', function() {
    TagC.deduplicate().then(json => {
      $('#tag_manager').load(window.location.href + ' #tag_manager > *');
      // TODO notif this in js from json.value
      //   $Response->setData(array('res' => true, 'msg' => sprintf(_('Deduplicated %d tags'), $deduplicated)));
    });
  });

  // DESTROY (from admin panel/tag manager)
  $('#tag_manager').on('click', '.tagDestroy', function() {
    if (confirm(i18next.t('tag-delete-warning'))) {
      TagC.destroy($(this).data('tagid')).then(() => {
        $('#tag_manager').load(window.location.href + ' #tag_manager > *');
      });
    }
  });
});
