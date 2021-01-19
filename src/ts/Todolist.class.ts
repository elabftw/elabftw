/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Crud from './Crud.class';
import { relativeMoment, makeSortableGreatAgain } from './misc';

export default class Todolist extends Crud {

  constructor() {
    super('app/controllers/Ajax.php');
  }

  // add a todo item
  create(e): void {
    e.preventDefault();
    const body = $('#todo').val();
    if (body !== '') {
      this.send({
        action: 'create',
        what: 'todolist',
        params: {
          template: body,
        },
      }).then((response) => {
        if (response.res) {
          // reload the todolist
          this.read();
          // and clear the input
          $('#todo').val('');
        }
      });
    }
  }

  read(): void {
    $.get('app/controllers/Ajax.php', {
      action: 'read',
      what: 'todolist',
    }).done(function(json) {
      let html = '<ul id="todoItems-list" class="sortable" data-axis="y" data-table="todolist">';
      for (const entry of json.msg) {
        html += `<li id='todoItem_${entry.id}'>
        <i class='fas fa-trash-alt clickable align-right destroyTodoItem' data-id='${entry.id}'></i>
        <span style='font-size:90%;display:block;'><i class='fas fa-sort draggable sortableHandle'></i> <span class='relative-moment' title='${entry.creation_time}'></span></span>
        <span class='todoItem editable' data-id='${entry.id}'>${entry.body}</span>
      </li>`;
      }
      html += '</ul>';
      $('#todoItemsDiv').html(html);
      makeSortableGreatAgain();
      relativeMoment();
    });
  }

  getSteps(): void {
    $.get('app/controllers/Ajax.php', {
      action: 'readAll',
      what: 'step',
      type: 'experiments',
    }).done(function(json) {
      let html = '';
      for (const exp of json.msg) {
        html += `<li><h3><a href='experiments.php?mode=view&id=${exp.id}'>${exp.title}</a></h3>`;
        for (const stepsData of Object.entries(exp.steps)) {
          const stepId = stepsData[1][0];
          const stepBody = stepsData[1][1];
          html += `<div><input type='checkbox' class='stepbox mr-1' id='todo_step_${stepId}' data-id='${exp.id}' data-type='experiments' data-stepid='${stepId}' />${stepBody}</div>`;
        }
        html += '</li>';
      }
      $('#todoStepsDiv').html(html);
    });
  }

  // remove one todo item
  destroy(id): void {
    this.send({
      action: 'destroy',
      what: 'todolist',
      params: {
        id: id,
      },
    }).then((response) => {
      if (response.res) {
        // hide item
        $('#todoItem_' + id).css('background', '#29AEB9');
        $('#todoItem_' + id).toggle('blind');
      }
    });
  }

  // TOGGLE
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
}
