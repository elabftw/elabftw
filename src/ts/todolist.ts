/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
import 'jquery-jeditable/src/jquery.jeditable.js';
import Todolist from './Todolist.class';
import i18next from 'i18next';

$(document).ready(function() {
  const TodolistC = new Todolist();

  // reopen todolist panel if it was previously opened
  if (localStorage.getItem('isTodolistOpen') === '1') {
    TodolistC.toggle();
  }
  // TOGGLE
  // use shortcut
  if ($('#todoSc').length) {
    key($('#todoSc').data('toggle'), function() {
      TodolistC.toggle();
    });
  }
  // or click the button
  $(document).on('click', '.todoToggle', function() {
    TodolistC.toggle();
  });

  // EDIT
  $(document).on('mouseenter', '.todoItem', function() {
    ($(this) as any).editable(function(value) {
      $.post('app/controllers/Ajax.php', {
        action: 'update',
        what: 'todolist',
        params: {
          template: value,
          id: $(this).data('id'),
        },
      });

      return(value);
    }, {
      tooltip : i18next.t('click-to-edit'),
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline'
    });
  });

  $('#todo-form').submit(function(e) {
    TodolistC.create(e);
  });

  $(document).on('click', '.destroyTodoItem', function() {
    TodolistC.destroy($(this).data('id'));
  });
});
