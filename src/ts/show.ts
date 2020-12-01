/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
declare let MathJax: any;
import { insertParamAndReload, notif } from './misc';
import 'bootstrap/js/src/modal.js';
import i18next from 'i18next';
import { CheckableItem } from './interfaces';

$(document).ready(function(){
  if ($('#info').data('page') !== 'show') {
    return;
  }

  // CREATE EXPERIMENT
  key($('#shortcuts').data('create'), function() {
    window.location.href = 'experiments.php?create=true';
  });

  // validate the form upon change. fix #451
  // add to the input itself, not the form for more flexibility
  // for instance the tags input allow multiple selection, so we don't want to submit on change
  $('.autosubmit').on('change', function() {
    $(this).closest('form').submit();
  });

  // TOGGLE BODY
  // toggleBody is the little +/- image
  $('.toggleBody').on('click', function() {
    const randId = $(this).data('randid');
    // transform the + in - and vice versa
    $(this).find('[data-fa-i2svg]').toggleClass('fa-minus-circle fa-plus-circle');
    // get the id to show the toggleBody
    const id = $(this).data('id');
    // get html of body
    $.get('app/controllers/EntityAjaxController.php', {
      getBody: true,
      id: id,
      type: $(this).data('type'),
      editor: 'tiny',
      // and put it in the div and show the div
    }).done(function(data) {
      // get the width of the parent. The -30 is to make it smaller than parent even with the margins
      const width = $('#parent_' + randId).width() - 30;
      // add html content and adjust the width of the children
      $('#' + randId).html(data.msg)
        .css('width', width)
        .toggle();
      // ask mathjax to reparse the page
      MathJax.typeset();
    });
  });

  // PAGINATION
  // previous page
  $('.pageButtons').on('click', '.previousPage', function() {
    insertParamAndReload('offset', $('#info').data('offset') - $('#info').data('limit'));
  });
  // next page
  $('.pageButtons').on('click', '.nextPage', function() {
    insertParamAndReload('offset', $('#info').data('offset') + $('#info').data('limit'));
  });
  // END PAGINATION

  // THE CHECKBOXES
  function getCheckedBoxes(): Array<CheckableItem> {
    const checkedBoxes = [];
    $('input[type=checkbox]:checked').each(function() {
      checkedBoxes.push({
        id: $(this).data('id'),
        // the randomid is used to get the parent container and hide it when delete
        randomid: $(this).data('randomid'),
      });
    });
    return checkedBoxes;
  }

  const bgColor = '#c4f9ff';

  // CHECK A BOX
  $('input[type=checkbox]').on('click', function() {
    if ($(this).prop('checked')) {
      $(this).parent().parent().css('background-color', bgColor);
    } else {
      $(this).parent().parent().css('background-color', '');
    }
  });

  // EXPAND ALL
  $('#expandAll').on('click', function() {
    if ($(this).data('status') === 'closed') {
      $(this).data('status', 'opened');
      $(this).text($(this).data('collapse'));
    } else {
      $(this).data('status', 'closed');
      $(this).text($(this).data('expand'));
    }
    $('.toggleBody').each(function() {
      $(this).trigger('click');
    });
  });

  // SELECT ALL
  $('#selectAllBoxes').on('click', function() {
    $('input[type=checkbox]').prop('checked', true);
    $('input[type=checkbox]').parent().parent().css('background-color', bgColor);
    $('#advancedSelectOptions').show();
    $('#withSelected').show();
    // also disable pagination because this will select all even the hidden ones
    $('section.item:hidden').show();
    $('#loadAllButton').hide(); // hide load button when there is nothing more to show
    $('#loadButton').hide(); // hide load button when there is nothing more to show
  });

  // UNSELECT ALL
  $('#unselectAllBoxes').on('click', function() {
    $('input:checkbox').prop('checked', false);
    $('input[type=checkbox]').parent().parent().css('background-color', '');
    // hide menu
    $('#withSelected').hide();
    $('#advancedSelectOptions').hide();
  });

  // INVERT SELECTION
  $('#invertSelection').on('click', function() {
    ($('input[type=checkbox]') as any).each(function () {
      this.checked = !this.checked;
      if ($(this).prop('checked')) {
        $(this).parent().parent().css('background-color', bgColor);
      } else {
        $(this).parent().parent().css('background-color', '');
      }
    });
  });

  // hide the "with selected" block if no checkboxes are checked
  $('#withSelected').hide();
  // no need to show the unselect/invert links if no one is selected
  $('#advancedSelectOptions').hide();
  $('input[type=checkbox]').on('click', function() {
    $('#advancedSelectOptions').show();
    $('#withSelected').show();
  });

  // UPDATE THE STATUS/ITEM TYPE OF SELECTED BOXES ON SELECT CHANGE
  $('#catChecked').on('change', function() {
    const ajaxs = [];
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      const json = {
        'msg': 'Nothing selected!',
        'res': false
      };
      notif(json);
      return;
    }
    // loop on it and update the status/item type
    $.each(checked, function(index) {
      ajaxs.push($.post('app/controllers/EntityAjaxController.php', {
        updateCategory : true,
        id: checked[index]['id'],
        categoryId : $('#catChecked').val(),
        type : $('#type').data('type')
      }));
    });
    // reload the page once it's done
    // a simple reload would not work here
    // we need to use when/then
    $.when.apply(null, ajaxs).then(function (){
      window.location.reload();
    });
  });

  // UPDATE THE VISIBILTY OF AN EXPERIMENT ON SELECT CHANGE
  $('#visChecked').on('change', function() {
    const ajaxs = [];
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      const json = {
        'msg': 'Nothing selected!',
        'res': false
      };
      notif(json);
      return;
    }
    // loop on it and update the status/item type
    $.each(checked, function(index) {
      ajaxs.push($.post('app/controllers/EntityAjaxController.php', {
        updatePermissions : true,
        rw: 'read',
        id: checked[index]['id'],
        value: $('#visChecked').val(),
        type : $('#type').data('type')
      }));
    });
    // reload the page once it's done
    // a simple reload would not work here
    // we need to use when/then
    $.when.apply(null, ajaxs).then(function (){
      window.location.reload();
    });
    notif({'msg': 'Saved', 'res': true});
  });

  // Export selected menu
  $('#exportChecked').on('change', function() {
    const what = $('#exportChecked').val();
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      const json = {
        'msg': 'Nothing selected!',
        'res': false
      };
      notif(json);
      return;
    }
    window.location.href = `make.php?what=${what}&type=${$('#type').data('type')}&id=${checked.map(value => value.id).join('+')}`;
  });

  // THE DELETE BUTTON FOR CHECKED BOXES
  $('#deleteChecked').on('click', function() {
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      const json = {
        'msg': 'Nothing selected!',
        'res': false
      };
      notif(json);
      return;
    }
    if (!confirm(i18next.t('entity-delete-warning'))) {
      return;
    }
    // loop on it and delete stuff
    $.each(checked, function(index) {
      $.post('app/controllers/EntityAjaxController.php', {
        destroy: true,
        id: checked[index]['id'],
        type: $('#type').data('type')
      }).done(function(json) {
        notif(json);
        if (json.res) {
          $('#parent_' + checked[index]['randomid']).hide(200);
        }
      });
    });
  });
});
