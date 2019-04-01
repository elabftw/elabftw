/**
 * todolist.js - for the todolist
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
(function() {
  'use strict';

  // TODOLIST TOGGLE
  // use shortcut
  key($('#todoSc').data('toggle'), function() {
    $('#todoList').toggle();
  });
  // or click the button
  $(document).on('click', '.todoToggle', function() {
    $('#todoList').toggle();
  });


  var Todolist = {
    controller: 'app/controllers/TodolistController.php',
    // add a todo item
    create: function(e) {
      e.preventDefault();
      var body = $('#todo').val();
      var currentdate = new Date();
      var datetime = currentdate.getFullYear() + '-' +
                (currentdate.getMonth()+1)  + '-' +
                currentdate.getDate() + ' ' +
                currentdate.getHours() + ':' +
                currentdate.getMinutes() + ':' +
                currentdate.getSeconds();
      if (body !== '') {
        $.post(this.controller, {
          create: true,
          body: body
        }).done(function(json) {
          if (json.res) {
            // add the todoitem
            $('#todoItems-list').prepend('<li class="todoItem" id="todoItem_' +
                            json.id + '"><i class="fas fa-trash-alt destroyTodoItem" data-id="' +
                            json.id+ '"></i><span style="font-size:60%;display:block;">' +
                            datetime + '</span><span id="todoItemBody_' + json.id + '" class="editable">' + body +
                            '</li>');
            // make it editable right away
            makeEditableTodoitem($('#todoItemBody_' + json.id));
            // and clear the input
            $('#todo').val('');
          } else {
            notif(json);
          }
        });
      }
    },
    // remove one todo item
    destroy: function(id) {
      $.post(this.controller, {
        destroy: true,
        id: id
      }).done(function(json) {
        notif(json);
        if (json.res) {
          // hide item
          $('#todoItem_' + id).css('background', '#29AEB9');
          $('#todoItem_' + id).toggle('blind');
        }
      });
    },
    // clear all the items
    destroyAll: function() {
      $.post(this.controller, {
        destroyAll: true
      }).done(function(json) {
        notif(json);
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
}());
