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

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }

  const TodolistC = new Todolist();

  const pagesWithoutTodo = ['login', 'register', 'change-pass'];
  if (pagesWithoutTodo.includes(document.getElementById('info').dataset.page)) {
    return;
  }

  // TOGGLE
  // reopen todolist panel if it was previously opened
  if (localStorage.getItem('isTodolistOpen') === '1') {
    TodolistC.toggle();
  }
  // use shortcut
  if ($('#todoSc').length) {
    key($('#todoSc').data('toggle'), function() {
      TodolistC.toggle();
    });
  }

  // UPDATE TODOITEM
  $(document).on('mouseenter', '.todoItem', function(ev) {
    ($(ev.currentTarget) as any).editable(function(input) {
      TodolistC.update(
        ev.currentTarget.dataset.todoitemid,
        input,
      );
      return (input);
    }, {
      tooltip : i18next.t('click-to-edit'),
      indicator : 'Saving...',
      onblur: 'submit',
      style : 'display:inline',
    });
  });

  // to avoid duplicating code between listeners (keydown and click on add)
  function createTodoitem(): void {
    const todoInput = (document.getElementById('todo') as HTMLInputElement);
    const content = todoInput.value;
    if (!content) { return; }

    TodolistC.create(content).then(json => {
      if (json.res) {
        // reload the todolist
        TodolistC.read();
        // and clear the input
        todoInput.value = '';
      }
    });
  }

  // save todo on enter
  document.getElementById('todo').addEventListener('keydown', (event: KeyboardEvent) => {
    if (event.keyCode === 13) {
      createTodoitem();
    }
  });

  // Add click listener and do action based on which element is clicked
  document.getElementById('container').addEventListener('click', (event) => {
    const el = (event.target as HTMLElement);
    // CREATE TODOITEM
    if (el.matches('[data-action="create-todoitem"]')) {
      createTodoitem();

    // DESTROY TODOITEM
    } else if (el.matches('[data-action="destroy-todoitem"]')) {
      if (confirm(i18next.t('generic-delete-warning'))) {
        const todoitemId = parseInt(el.dataset.todoitemid);
        TodolistC.destroy(todoitemId).then(json => {
          if (json.res) {
            // hide item
            $('#todoItem_' + todoitemId).css('background', '#29AEB9').toggle('blind');
          }
        });
      }
    // TOGGLE TODOITEM
    } else if (el.matches('[data-action="toggle-todolist"]')) {
      TodolistC.toggle();
    }
  });
});
