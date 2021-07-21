/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Action, Todoitem, EntityType, UnfinishedExperiments, Target, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';
import { relativeMoment, makeSortableGreatAgain } from './misc';
import i18next from 'i18next';

export default class Todolist {

  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.Todolist,
    this.sender = new Ajax();
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
      let html = '<ul id="todoItems-list" class="sortable" data-axis="y" data-table="todolist">';
      for (const entry of json.value as Array<Todoitem>) {
        html += `<li data-todoitemid=${entry.id} id='todoItem_${entry.id}'>
        <a class='clickable float-right' data-action='destroy-todoitem' data-todoitemid='${entry.id}' title='` + i18next.t('generic-delete-warning') + `'>
          <i class='fas fa-trash-alt'></i>
        </a>
        <span style='font-size:90%;display:block;'><span class='draggable sortableHandle'><i class='fas fa-sort'></i></span> <span class='relative-moment' title='${entry.creation_time}'></span></span>
        <span class='todoItem editable' data-todoitemid='${entry.id}'>${entry.body}</span>
      </li>`;
      }
      html += '</ul>';
      $('#todoItemsDiv').html(html);
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


  getSteps(): Promise<void> {
    const payload: Payload = {
      method: Method.GET,
      action: Action.Read,
      entity: {
        type: EntityType.Experiment,
        id: null,
      },
      model: Model.Step,
      target: Target.All,
    };
    return this.sender.send(payload).then(json => {
      let html = '';
      for (const exp of json.value as Array<UnfinishedExperiments>) {
        html += `<li><h3><a href='experiments.php?mode=view&id=${exp.id}'>${exp.title}</a></h3>`;
        for (const stepsData of Object.entries(exp.steps)) {
          const stepId = stepsData[1][0];
          const stepBody = stepsData[1][1];
          html += `<div><input type='checkbox' class='stepbox mr-1' id='todo_step_${stepId}' data-id='${exp.id}' data-type='${EntityType.Experiment}' data-stepid='${stepId}' />${stepBody}</div>`;
        }
        html += '</li>';
      }
      $('#todoStepsDiv').html(html);
    });
  }

  // TOGGLE TODOLIST VISIBILITY
  toggle(): void {
    if ($('#todoList').is(':visible')) {
      $('#container').css('width', '100%').css('margin-right', 'auto');
      localStorage.setItem('isTodolistOpen', '0');
    } else {
      $('#container').css('width', '70%').css('margin-right', '0');
      this.read();
      this.getSteps();
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
