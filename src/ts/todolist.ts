/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
import { relativeMoment, notif } from './misc';
import 'jquery-jeditable/src/jquery.jeditable.js';

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
          Todolist.getTodoItems();
          // and clear the input
          $('#todo').val('');
        } else {
          notif(json);
        }
      });
    }
  },

  getTodoItems: function(): void {
    $.get('app/controllers/AjaxController.php', {
      getTodoItems: true,
    }).done(function(json) {
      let html = '';
      for (const entry of json.msg) {
        html += `<li id='todoItem_${entry.id}'>
        <i class='fas fa-trash-alt clickable align_right destroyTodoItem' data-id='${entry.id}'></i>
        <span style='font-size:90%;display:block;'><i class='fas fa-sort sortableHandle'></i> <span class='relative-moment' title='${entry.creation_time}'></span></span>
        <span class='todoItem editable' data-id='${entry.id}'>${entry.body}</span>
      </li>`;
      }
      $('#todoItemsDiv').html(html);
      relativeMoment();
    });
  },

  getSteps: function(): void {
    $.get('app/controllers/AjaxController.php', {
      getExperimentsSteps: true,
    }).done(function(json) {
      let html = '';
      for (const exp of json.msg) {
        html += `<li><h3><a href='experiments.php?mode=view&id=${exp.id}'>${exp.title}</a></h3>`;
        for (const [stepId, stepBody] of Object.entries(exp.steps)) {
          html += `<div><input type='checkbox' class='stepbox mr-1' id='todo_step_${stepId}' data-id='${exp.id}' data-type='experiments' data-stepid='${stepId}' />${stepBody}</div>`;
        }
        html += '</li>';
      }
      $('#todoStepsDiv').html(html);
    });
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

  // TOGGLE
  toggle: function(): void {
    if ($('#todoList').is(':visible')) {
      $('#container').css('width', '100%').css('margin-right', 'auto');
      localStorage.setItem('isTodolistOpen', '0');
    } else {
      $('#container').css('width', '70%').css('margin-right', '0');
      Todolist.getTodoItems();
      Todolist.getSteps();
      localStorage.setItem('isTodolistOpen', '1');
    }
    $('#todoList').toggle();
  }
};

$(document).ready(function() {
  // reopen todolist panel if it was previously opened
  if (localStorage.getItem('isTodolistOpen') === '1') {
    Todolist.toggle();
  }
  // TOGGLE
  // use shortcut
  if ($('#todoSc').length) {
    key($('#todoSc').data('toggle'), function() {
      Todolist.toggle();
    });
  }
  // or click the button
  $(document).on('click', '.todoToggle', function() {
    Todolist.toggle();
  });

  // EDIT
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
