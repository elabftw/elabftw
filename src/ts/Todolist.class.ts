/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare let key: any;
import { Payload, Method, Model, Action, Todoitem, EntityType, UnfinishedEntities, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';
import { relativeMoment, makeSortableGreatAgain } from './misc';
import i18next from 'i18next';

export default class Todolist {

  model: Model;
  sender: Ajax;
  unfinishedStepsScope: string;
  initialLoad = true;

  constructor() {
    this.model = Model.Todolist,
    this.sender = new Ajax();
    this.unfinishedStepsScope = 'user';

    // unfinished steps scopeSwitch i.e. user (0) or team (1)
    const scopeSwitch = document.getElementById('todolistStepsShowTeam') as HTMLInputElement;
    const storageScopeSwitch = localStorage.getItem('todolistStepsShowTeam');
    // local storage has priority over default setting todolist_steps_show_team
    if (scopeSwitch.checked && storageScopeSwitch === '0') {
      scopeSwitch.checked = false;

    // set storage value if default setting is team
    } else if (scopeSwitch.checked) {
      localStorage.setItem('todolistStepsShowTeam', '1');
      this.unfinishedStepsScope = 'team';

    // check box if it was checked before
    } else if (storageScopeSwitch === '1') {
      scopeSwitch.checked = true;
      this.unfinishedStepsScope = 'team';
    }

    scopeSwitch.addEventListener('change', () => {
      this.toogleUnfinishedStepsScope();
    });

    // TOGGLE
    // reopen to-do list panel if it was previously opened
    if (localStorage.getItem('isTodolistOpen') === '1') {
      this.toggle();
    }
    // use shortcut
    const todoSc = document.getElementById('todoSc');
    if (todoSc) {
      key(todoSc.dataset.toggle, () => {
        this.toggle();
      });
    }

    // actual lists i.e. to-do list and unfinished item/experiment steps
    const lists = ['todoItems', 'todoStepsExperiment', 'todoStepsItem'];
    lists.forEach(list => {
      if (localStorage.getItem(list + '-isClosed') === '1') {
        document.getElementById(list).toggleAttribute('hidden');
      }
    });
  }

  create(content: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      content: content,
    };
    return this.sender.send(payload);
  }

  read(): Promise<void> {
    const payload: Payload = {
      method: Method.GET,
      action: Action.Read,
      model: this.model,
    };
    return this.sender.send(payload).then(json => {
      let html = '';
      for (const entry of json.value as Array<Todoitem>) {
        html += `<li data-todoitemid=${entry.id} id='todoItem_${entry.id}'>
        <a class='clickable float-right' data-action='destroy-todoitem' data-todoitemid='${entry.id}' title='` + i18next.t('generic-delete-warning') + `'>
          <i class='fas fa-trash-alt'></i>
        </a>
        <span style='font-size:90%;display:block;'><span class='draggable sortableHandle'><i class='fas fa-sort'></i></span> <span class='relative-moment' title='${entry.creation_time}'></span></span>
        <span class='todoItem editable' data-todoitemid='${entry.id}'>${entry.body}</span>
      </li>`;
      }
      document.getElementById('todoItems').innerHTML = html;
      makeSortableGreatAgain();
      relativeMoment();
    });
  }

  update(id: number, content: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      id : id,
      content: content,
    };
    return this.sender.send(payload);
  }

  toogleUnfinishedStepsScope(): void {
    localStorage.setItem('todolistStepsShowTeam', (localStorage.getItem('todolistStepsShowTeam') === '1' ? '0' : '1'));
    this.unfinishedStepsScope = (this.unfinishedStepsScope === 'user' ? 'team' : 'user');
    this.loadUnfinishedStep();
  }

  loadUnfinishedStep(): void  {
    this.getUnfinishedStep(EntityType.Experiment);
    this.getUnfinishedStep(EntityType.Item);
  }

  getUnfinishedStep(type: EntityType): Promise<void> {
    const payload: Payload = {
      method: Method.GET,
      action: Action.Read,
      model: Model.UnfinishedSteps,
      entity: {
        type: type,
        id: null,
      },
      extraParams: {'scope': this.unfinishedStepsScope},
    };
    return this.sender.send(payload).then(json => {
      if (json.res) {
        let html = '';
        for (const entity of json.value as Array<UnfinishedEntities>) {
          html += `<li><h4><a href='${type === EntityType.Item ? 'database' : 'experiments'}.php?mode=view&id=${entity.id}'>${entity.title}</a></h4>`;
          for (const stepsData of Object.entries(entity.steps)) {
            const stepId = stepsData[1][0];
            const stepBody = stepsData[1][1];
            html += `<div><input type='checkbox' class='stepbox mr-1' id='todo_step_${stepId}' data-id='${entity.id}' data-type='${type}' data-stepid='${stepId}' />${stepBody}</div>`;
          }
          html += '</li>';
        }
        const typeIdName = 'todoSteps' + type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById(typeIdName).innerHTML = html;
      }
    });
  }

  // TOGGLE TODOLIST VISIBILITY
  toggle(): void {
    if ($('#todoList').is(':visible')) {
      $('#container').css('width', '100%').css('margin-right', 'auto');
      localStorage.setItem('isTodolistOpen', '0');
    } else {
      $('#container').css('width', '70%').css('margin-right', '0');
      // lazy load content only once
      if (this.initialLoad) {
        this.read();
        this.loadUnfinishedStep();
        this.initialLoad = false;
      }
      localStorage.setItem('isTodolistOpen', '1');
    }
    $('#todoList').toggle();
  }

  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      id : id,
    };
    return this.sender.send(payload);
  }
}
