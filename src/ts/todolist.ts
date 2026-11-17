/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Todolistc from './Todolist.class';
import { EntityType, Model } from './interfaces';
import Step from './Step.class';

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

const TodolistC = new Todolistc();
TodolistC.unfinishedStepsScope = unfinishedStepsScope;

scopeSwitch = document.getElementById(TodolistC.model + 'StepsShowTeam') as HTMLInputElement;
scopeSwitch.addEventListener('change', () => {
  if (!document.getElementById(TodolistC.panelId).hasAttribute('hidden')){
    TodolistC.toggleUnfinishedStepsScope();
  }
});

// FINISH a step from the Todolist panel
// this lives here and not in steps-links.ts because that file is only loaded on pages with a stepsDiv (not on the dashboard)
document.getElementById(TodolistC.panelId)?.addEventListener('change', (event: Event) => {
  const input = event.target;
  if (input instanceof HTMLInputElement && input.classList.contains('todo-stepbox')) {
    (new Step({ type: input.dataset.type as EntityType, id: parseInt(input.dataset.id, 10) })).finishFromCheckbox(input);
  }
});
