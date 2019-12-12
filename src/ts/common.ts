/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import 'jquery-ui/ui/widgets/sortable';
import { relativeMoment, notif, displayMolFiles } from './misc';

$.ajaxSetup({
headers: {
  'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
}
});

$(document).ready(function() {
  // TOGGLABLE
  $(document).on('click', '.togglableNext', function() {
    $(this).next().toggle();
  });
  $('.togglableHidden').hide();

  // Toggle modal
  $('.modalToggle').on('click', function() {
    (<any>$('#' + $(this).data('modal'))).modal('toggle');
  });

  // SORTABLE ELEMENTS
  // need an axis and a table via data attribute
  (<any>$('.sortable')).sortable({
    // limit to horizontal dragging
    axis : $(this).data('axis'),
    helper : 'clone',
    handle : '.sortableHandle',
    // we don't want the Create new pill to be sortable
    cancel: 'nonSortable',
    // do ajax request to update db with new order
    update: function() {
      // send the orders as an array
      var ordering = (<any>$(this)).sortable('toArray');

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
