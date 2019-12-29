/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/sortable';
import 'bootstrap/js/dist/modal.js';
import { relativeMoment, notif, displayMolFiles } from './misc';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // TOGGLABLE
  $(document).on('click', '.togglableNext', function() {
    $(this).next().toggle();
  });

  // Toggle modal
  $('.modalToggle').on('click', function() {
    ($('#' + $(this).data('modal')) as any).modal('toggle');
  });

  // SORTABLE ELEMENTS
  // need an axis and a table via data attribute
  $('.sortable').sortable({
    // limit to horizontal dragging
    axis : $(this).data('axis'),
    helper : 'clone',
    handle : '.sortableHandle',
    // we don't want the Create new pill to be sortable
    cancel: 'nonSortable',
    // do ajax request to update db with new order
    update: function() {
      // send the orders as an array
      const ordering = $(this).sortable('toArray');

      $.post('app/controllers/SortableAjaxController.php', {
        table: $(this).data('table'),
        ordering: ordering
      }).done(function(json) {
        notif(json);
      });
    }
  });

  relativeMoment();
  displayMolFiles();
});
