/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
import { getCheckedBoxes, notif, reloadEntitiesShow, getEntity, reloadElement } from './misc';
import 'bootstrap/js/src/modal.js';
import i18next from 'i18next';
import EntityClass from './Entity.class';
import FavTag from './FavTag.class';
import { MathJaxObject } from 'mathjax-full/js/components/startup';
declare const MathJax: MathJaxObject;

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }
  const about = document.getElementById('info').dataset;
  // only run in show mode or on search page (which is kinda show mode too)
  const pages = ['show', 'search'];
  if (!pages.includes(about.page)) {
    return;
  }

  const entity = getEntity();
  const limit = parseInt(about.limit, 10);
  const EntityC = new EntityClass(entity.type);
  const FavTagC = new FavTag();

  // CREATE EXPERIMENT or DATABASE item with shortcut
  key(document.getElementById('shortcuts').dataset.create, function() {
    if (about.type === 'experiments') {
      const el = document.querySelector('[data-action="create-entity"]') as HTMLButtonElement;
      const tplid = el.dataset.tplid;
      const urlParams = new URLSearchParams(document.location.search);
      const tags = urlParams.getAll('tags[]');
      EntityC.create(tplid, tags).then(json => {
        if (json.res) {
          window.location.replace(`?mode=edit&id=${json.value}`);
        } else {
          notif(json);
        }
      });
    } else {
      // for database items, show a selection modal
      // modal plugin requires jquery
      ($('#createModal') as JQuery).modal('toggle');
    }
  });

  // validate the form upon change. fix #451
  // add to the input itself, not the form for more flexibility
  // for instance the tags input allow multiple selection, so we don't want to submit on change
  $('.autosubmit').on('change', function() {
    $(this).closest('form').submit();
  });

  // THE CHECKBOXES
  const nothingSelectedError = {
    'msg': i18next.t('nothing-selected'),
    'res': false,
  };

  const bgColor = '#c4f9ff';

  // CHECK A BOX
  $('.item input[type=checkbox]').on('click', function() {
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
    $('.item input[type=checkbox]').prop('checked', true);
    $('.item input[type=checkbox]').parent().parent().css('background-color', bgColor);
    $('#advancedSelectOptions').show();
    $('#withSelected').show();
    // also disable pagination because this will select all even the hidden ones
    $('section.item:hidden').show();
    $('#loadAllButton').hide(); // hide load button when there is nothing more to show
    $('#loadButton').hide(); // hide load button when there is nothing more to show
  });

  // UNSELECT ALL
  $('#unselectAllBoxes').on('click', function() {
    $('.item input:checkbox').prop('checked', false);
    $('.item input[type=checkbox]').parent().parent().css('background-color', '');
    // hide menu
    $('#withSelected').hide();
    $('#advancedSelectOptions').hide();
  });

  // INVERT SELECTION
  $('#invertSelection').on('click', function() {
    ($('.item input[type=checkbox]') as JQuery<HTMLInputElement>).each(function() {
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
  $('.item input[type=checkbox]').on('click', function() {
    $('#advancedSelectOptions').show();
    $('#withSelected').show();
  });

  // UPDATE THE STATUS/ITEM TYPE OF SELECTED BOXES ON SELECT CHANGE
  $('#catChecked').on('change', function() {
    const ajaxs = [];
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      notif(nothingSelectedError);
      return;
    }
    // loop on it and update the status/item type
    $.each(checked, function(index) {
      ajaxs.push($.post('app/controllers/EntityAjaxController.php', {
        updateCategory : true,
        id: checked[index]['id'],
        categoryId : $('#catChecked').val(),
        type : about.type,
      }));
    });
    // reload the page once it's done
    // a simple reload would not work here
    // we need to use when/then
    $.when.apply(null, ajaxs).then(function(){
      window.location.reload();
    });
  });

  // UPDATE THE VISIBILITY OF AN EXPERIMENT ON SELECT CHANGE
  $('#visChecked').on('change', function() {
    const ajaxs = [];
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      notif(nothingSelectedError);
      return;
    }
    // loop on it and update the status/item type
    $.each(checked, function(index) {
      ajaxs.push($.post('app/controllers/EntityAjaxController.php', {
        updatePermissions : true,
        rw: 'read',
        id: checked[index]['id'],
        value: $('#visChecked').val(),
        type : about.type,
      }));
    });
    // reload the page once it's done
    // a simple reload would not work here
    // we need to use when/then
    $.when.apply(null, ajaxs).then(function(){
      window.location.reload();
    });
    notif({'msg': 'Saved', 'res': true});
  });

  // Export selected menu
  $('#exportChecked').on('change', function() {
    const what = $('#exportChecked').val();
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      notif(nothingSelectedError);
      return;
    }
    window.location.href = `make.php?what=${what}&type=${about.type}&id=${checked.map(value => value.id).join('+')}`;
  });

  // THE LOCK BUTTON FOR CHECKED BOXES
  $('#lockChecked').on('click', function() {
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      notif(nothingSelectedError);
      return;
    }

    // loop over it and lock entities
    const results = [];
    checked.forEach(checkBox => {
      results.push(EntityC.lock(checkBox['id']));
    });

    Promise.all(results).then(() => {
      reloadEntitiesShow();
    });
  });

  // THE TIMESTAMP BUTTON FOR CHECKED BOXES
  $('#timestampChecked').on('click', function() {
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      notif(nothingSelectedError);
      return;
    }
    // loop on it and delete stuff
    $.each(checked, function(index) {
      $.post('app/controllers/EntityAjaxController.php', {
        timestamp: true,
        type: 'experiments',
        id: checked[index]['id'],
      }).done(function(json) {
        notif(json);
      });
    });
  });

  // THE DELETE BUTTON FOR CHECKED BOXES
  $('#deleteChecked').on('click', function() {
    // get the item id of all checked boxes
    const checked = getCheckedBoxes();
    if (checked.length === 0) {
      notif(nothingSelectedError);
      return;
    }
    if (!confirm(i18next.t('entity-delete-warning'))) {
      return;
    }
    // loop on it and delete stuff
    $.each(checked, function(index) {
      EntityC.destroy(checked[index]['id']).then(json => {
        if (json.res) {
          $('#parent_' + checked[index]['randomid']).hide(200);
        }
      });
    });
  });

  // change sort-icon based on sort value: asc or desc
  $('.orderBy').each(function() {
    if ($('select[name="order"]').val() === $(this).data('orderby')) {
      $(this).find('[data-fa-i2svg]').removeClass('fa-sort').addClass($('select[name="sort"]').val() === 'asc' ? 'fa-sort-up': 'fa-sort-down');
    }
  });

  // Sort column in tabular mode
  $(document).on('click', '.orderBy', function() {
    // The attribute data-orderby of the anchor element next to the title contains the value of the corresponding
    // option of the select field <select name='order'> that will be selected in the form.
    // For example: <a class='clickable orderBy' data-orderby='title'>...</a>, will select the option 'title'
    // in the <select name='order'>
    const targetSort = $(this).data('orderby');
    const selectOrder = $('select[name="order"]');
    const selectSort = $('select[name="sort"]');

    // I guess the default expectation is sort ascending, but if the user clicks twice, the
    // order should invert. For this, we check whether the select value is already set
    // to targetSort
    if (selectOrder.val() == targetSort) {
      selectSort.val(selectSort.val() == 'desc' ? 'asc': 'desc' );
    } else {
      $('select[name="sort"]').val('asc');
    }
    selectOrder.val(targetSort);
    selectOrder.closest('form').trigger('submit');
  });

  document.getElementById('favtagsPanel').addEventListener('keyup', event => {
    const el = (event.target as HTMLInputElement);
    const query = el.value;
    if (el.matches('[data-action="favtags-search"]')) {
      // find all links that are endpoints
      document.querySelectorAll('[data-action="add-tag-filter"]').forEach(el => {
        // begin by showing all so they don't stay hidden
        el.removeAttribute('hidden');
        // now simply hide the ones that don't match the query
        if (!(el as HTMLElement).innerText.toLowerCase().includes(query)) {
          el.setAttribute('hidden', '');
        }
      });
    }
  });

  // get offset as number
  function getOffset(): number {
    const params = new URLSearchParams(document.location.search);
    let currentOffset = params.get('offset');
    if (!currentOffset) {
      currentOffset = '0';
    }
    return parseInt(currentOffset, 10);
  }

  // Add click listener and do action based on which element is clicked
  document.getElementById('container').addEventListener('click', event => {
    const el = (event.target as HTMLElement);
    const params = new URLSearchParams(document.location.search);
    // previous page
    if (el.matches('[data-action="previous-page"]')) {
      params.set('offset', String(getOffset() - limit));
      history.replaceState(null, '', `?${params.toString()}`);
      reloadEntitiesShow();

    // next page
    } else if (el.matches('[data-action="next-page"]')) {
      params.set('offset', String(getOffset() + limit));
      history.replaceState(null, '', `?${params.toString()}`);
      reloadEntitiesShow();

    // TOGGLE FAVTAGS PANEL
    } else if (el.matches('[data-action="toggle-favtags"]')) {
      FavTagC.toggle();

    // TOGGLE text input to add a new favorite tag
    } else if (el.matches('[data-action="toggle-addfav"]')) {
      const input = document.getElementById('createFavTagInput');
      input.toggleAttribute('hidden');
      input.focus();

    // a tag has been clicked/selected, add it in url and load the page
    } else if (el.matches('[data-action="add-tag-filter"]')) {
      params.set('tags[]', el.dataset.tag);
      // clear out any offset from a previous query
      params.delete('offset');
      history.replaceState(null, '', `?${params.toString()}`);
      document.querySelectorAll('[data-action="add-tag-filter"]').forEach(el => {
        el.classList.remove('selected');
      });
      el.classList.add('selected');
      reloadEntitiesShow(el.dataset.tag);

    // clear the filter input for favtags
    } else if (el.matches('[data-action="clear-favtags-search"]')) {
      const searchInput = (document.querySelector('[data-action="favtags-search"]') as HTMLInputElement);
      searchInput.value = '';
      searchInput.focus();
      document.querySelectorAll('[data-action="add-tag-filter"]').forEach(el => {
        el.removeAttribute('hidden');
      });

    // toggle visibility of the trash icon for favtags
    } else if (el.matches('[data-action="toggle-favtags-edit"]')) {
      document.querySelectorAll('[data-action="destroy-favtags"]').forEach(el => {
        el.toggleAttribute('hidden');
      });

    // remove a favtag
    } else if (el.matches('[data-action="destroy-favtags"]')) {
      FavTagC.destroy(parseInt(el.dataset.id, 10)).then(() => reloadElement('favtagsPanel'));

    } else if (el.matches('[data-action="toggle-body"]')) {
      const randId = el.dataset.randid;
      // transform the + in - and vice versa
      $(el).find('[data-fa-i2svg]').toggleClass('fa-minus-circle fa-plus-circle');
      // get the id to show the toggleBody
      const id = $(el).data('id');
      // get html of body
      $.get('app/controllers/EntityAjaxController.php', {
        getBody: true,
        id: id,
        type: el.dataset.type,
        editor: 'tiny',
        // and put it in the div and show the div
      }).done(function(data) {
        // get the width of the parent. The -30 is to make it smaller than parent even with the margins
        const width = $('#parent_' + randId).width() - 30;
        // add html content and adjust the width of the children
        const div = document.getElementById(randId);
        div.innerHTML = data.msg;
        div.style.width = String(width);
        div.toggleAttribute('hidden');
        // ask mathjax to reparse the page
        MathJax.typeset();
      });
    }
  });

  document.getElementById('favtags-opener').removeAttribute('hidden');

  // FAVTAGS PANEL
  if (localStorage.getItem('isfavtagOpen') === '1') {
    FavTagC.toggle();
  }
});
