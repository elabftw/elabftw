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
import { Type, Entity } from './interfaces';
import { getCheckedBoxes, notif } from './misc';

$(document).ready(function() {
  let type = $('#info').data('type');
  if (type === undefined) {
    type = 'experiments_templates';
  }

  // holds info about the page through data attributes
  const about = document.getElementById('info').dataset;
  let entityType: Type;
  if (about.type === 'experiments') {
    entityType = Type.Experiment;
  }
  if (about.type === 'items') {
    entityType = Type.Item;
  }
  if (about.type === 'experiments_templates') {
    entityType = Type.ExperimentTemplate;
  }

  const entity: Entity = {
    type: entityType,
    id: parseInt(about.id),
  };

  const TagC = new Tag(entity);

  // CREATE TAG
  $(document).on('keypress blur', '.createTagInput', function(e) {
    if ($(this).val() === '') {
      return;
    }
    // Enter is ascii code 13
    if (e.which === 13 || e.type === 'focusout') {
      TagC.create($(this).val() as string).then(() => {
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
          'res': false
        };
        notif(json);
        return;
      }
      $.each(checked, function(index) {
        TagC.create($('#createTagInputMultiple').val() as string, checked[index]['id']);
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
      // so we need to have a type here so we can get an entity (Database will be default) and the Tags object is correctly built by the Processor
      request.type = 'not-important-but-needs-to-be-here';
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
