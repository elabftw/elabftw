/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import Tag from './Tag.class';
import i18next from 'i18next';
import { getCheckedBoxes, notif } from './misc';
import { CheckableItem } from './interfaces';

$(document).ready(function() {
  let type = $('#info').data('type');
  if (type === undefined) {
    type = 'experiments_templates';
  }

  const TagC = new Tag(type);

  // CREATE TAG
  $(document).on('keypress blur', '.createTagInput', function(e) {
    if ($(this).val() === '') {
      return;
    }
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      const itemId = $(this).data('id');
      TagC.save($(this).val() as string, itemId);
      $(this).val('');
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
          'res': false
        };
        notif(json);
        return;
      }
      $.each(checked, function(index) {
        TagC.save($('#createTagInputMultiple').val() as string, checked[index]['id']);
      });
      $(this).val('');
    }
  });

  // AUTOCOMPLETE
  const cache = {};
  ($('.createTagInput') as any).autocomplete({
    source: function(request: any, response: any) {
      const term  = request.term;
      if (term in cache) {
        response(cache[term]);
        return;
      }
      request.what = 'tag';
      request.action = 'getList';
      request.params = {
        name: term,
      };
      $.getJSON('app/controllers/Ajax.php', request, function(data) {
        cache[term] = data;
        response(data);
      });
    }
  });

  // make the tag editable
  $(document).on('mouseenter', '.tag-editable', function() {
    ($(this) as any).editable(function(value) {
      $.post('app/controllers/Ajax.php', {
        action: 'update',
        what: 'tag',
        params: {
          tag: value,
          id: $(this).data('tagid'),
        },
      });

      return(value);
    }, {
      tooltip : i18next.t('click-to-edit'),
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline',
    });
  });

  // UNREFERENCE (remove link between tag and entity)
  $(document).on('click', '.tagUnreference', function() {
    TagC.unreference($(this).data('tagid'), $(this).data('id'));
  });

  // DEDUPLICATE (from admin panel/tag manager)
  $(document).on('click', '.tagDeduplicate', function() {
    TagC.deduplicate();
  });

  // DESTROY (from admin panel/tag manager)
  $(document).on('click', '.tagDestroy', function() {
    TagC.destroy($(this).data('tagid'));

  });
});
