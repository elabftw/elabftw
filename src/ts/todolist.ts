/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import 'jquery-jeditable/src/jquery.jeditable.js';
import Todolist from './Todolist.class';
import i18next from 'i18next';
import { Model } from './interfaces';
declare let key: any;

document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('info')) {
    return;
  }

  const pagesWithoutTodo = ['login', 'register', 'change-pass'];
  if (pagesWithoutTodo.includes(document.getElementById('info').dataset.page)) {
    return;
  }

  let unfinishedStepsScope = 'user';
  // unfinished steps scopeSwitch i.e. user (0) or team (1)
  let scopeSwitch = document.getElementById(Model.Todolist + 'StepsShowTeam') as HTMLInputElement;
  const storageScopeSwitch = localStorage.getItem(Model.Todolist + 'StepsShowTeam');
  // adjust scope from localStorage
  if (scopeSwitch.checked && storageScopeSwitch === '0') {
    scopeSwitch.checked = false;

  // set storage value if default setting is team
  } else if (scopeSwitch.checked) {
    localStorage.setItem(Model.Todolist + 'StepsShowTeam', '1');
    unfinishedStepsScope = 'team';

  // check box if it was checked before
  } else if (storageScopeSwitch === '1') {
    scopeSwitch.checked = true;
    unfinishedStepsScope = 'team';
  }

  // actual lists i.e. to-do list and unfinished item/experiment steps
  const lists = ['todoItems', 'todoStepsExperiment', 'todoStepsItem'];
  lists.forEach(list => {
    if (localStorage.getItem(list + '-isClosed') === '1') {
      document.getElementById(list).toggleAttribute('hidden');
    }
  });

  const TodolistC = new Todolist();
  TodolistC.unfinishedStepsScope = unfinishedStepsScope;

  // TOGGLE
  // reopen to-do list panel if it was previously opened
  if (localStorage.getItem(`is${TodolistC.model}Open`) === '1') {
    TodolistC.toggle();
  }
  // use shortcut
  const todoSc = document.getElementById('todoSc');
  if (todoSc) {
    key(todoSc.dataset.toggle, () => {
      TodolistC.toggle();
    });
  }

  scopeSwitch = document.getElementById(TodolistC.model + 'StepsShowTeam') as HTMLInputElement;
  scopeSwitch.addEventListener('change', () => {
    if (!document.getElementById(TodolistC.panelId).hasAttribute('hidden')){
      TodolistC.toogleUnfinishedStepsScope();
    }
  });

  // UPDATE TODOITEM
  $(document).on('mouseenter', '.todoItem', ev => {
    ($(ev.currentTarget) as any).editable(input => {
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
  document.getElementById('container').addEventListener('click', event => {
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

    // TOGGLE SUBLISTS i.e. actual todo-list and unfinished item/experiment steps
    } else if (el.matches('[data-action="toggle-next"]')) {
      const sublist = el.nextElementSibling.id + '-isClosed';
      if (!localStorage.getItem(sublist)) {
        localStorage.setItem(sublist, '1');
      } else if (localStorage.getItem(sublist) === '1') {
        localStorage.removeItem(sublist);
      }
    }
  });
});
