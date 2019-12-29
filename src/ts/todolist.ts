/**
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
import { relativeMoment, notif } from './misc';
import 'jquery-jeditable/src/jquery.jeditable.js';

$(document).ready(function() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // TODOLIST TOGGLE
  // use shortcut
  key($('#todoSc').data('toggle'), function() {
    $('#todoList').toggle();
  });
  // or click the button
  $(document).on('click', '.todoToggle', function() {
    $('#todoList').toggle();
  });
  $(document).on('mouseenter', '.todoItem', function() {
    ($(this) as any).editable(function(value) {
      $.post('app/controllers/TodolistController.php', {
        update: true,
        body: value,
        id: $(this).data('id'),
      });

      return(value);
    }, {
      tooltip : 'Click to edit',
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline'
    });
  });


  const Todolist = {
    controller: 'app/controllers/TodolistController.php',
    // add a todo item
    create: function(e): void {
      e.preventDefault();
      const body = $('#todo').val();
      if (body !== '') {
        $.post(this.controller, {
          create: true,
          body: body
        }).done(function(json) {
          if (json.res) {
            // reload the todolist
            $('#todoItems-list').load('? #todoItems-list>*', function() {
              relativeMoment();
            });
            // and clear the input
            $('#todo').val('');
          } else {
            notif(json);
          }
        });
      }
    },
    // remove one todo item
    destroy: function(id): void {
      $.post(this.controller, {
        destroy: true,
        id: id
      }).done(function(json) {
        if (json.res) {
          // hide item
          $('#todoItem_' + id).css('background', '#29AEB9');
          $('#todoItem_' + id).toggle('blind');
        }
      });
    },
    // clear all the items
    destroyAll: function(): void {
      $.post(this.controller, {
        destroyAll: true
      }).done(function(json) {
        if (json.res) {
          // hide all items
          $('#todoItems-list').children().toggle('blind');
        }
      });
    },
  };

  $('#todo-form').submit(function(e) {
    Todolist.create(e);
  });
  $(document).on('click', '.todoDestroyAll', function() {
    Todolist.destroyAll();
  });

  $(document).on('click', '.destroyTodoItem', function() {
    Todolist.destroy($(this).data('id'));
  });
});
