/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model, Todoitem, EntityType, UnfinishedEntities } from './interfaces';
import SidePanel from './SidePanel.class';
import { relativeMoment, makeSortableGreatAgain } from './misc';
import FavTag from './FavTag.class';
import { Api } from './Apiv2.class';

export default class Todolist extends SidePanel {

  unfinishedStepsScope: string;
  initialLoad = true;
  api: Api;

  constructor() {
    super(Model.Todolist);
    this.panelId = 'todolistPanel';
    this.unfinishedStepsScope = 'user';
    this.api = new Api();
  }

  create(content: string): Promise<Response> {
    return this.api.post(`${this.model}`, {'content': content});
  }

  readAll() {
    return this.api.getJson(`${this.model}`);
  }

  display(): Promise<void> {
    return this.readAll().then(json => {
      let html = '';
      for (const entry of json as Array<Todoitem>) {
        html += `<div data-todoitemid=${entry.id} id='todoItem_${entry.id}' class='side-panel-item d-flex align-items-center'>
        <div>
          <div>
            <span class='draggable sortableHandle'><i class='fas fa-grip-vertical fa-fw mr-1'></i></span>
            <input type='checkbox' class='mr-2' data-action='destroy-todoitem' data-todoitemid='${entry.id}' />
            <span class='todoItem editable' data-todoitemid='${entry.id}'>${entry.body}</span></div>
            <div class='relative-moment' title='${entry.creation_time}'></div>
          </div>
        </div>`;
      }
      document.getElementById('todoItems').innerHTML = html;
      makeSortableGreatAgain();
      relativeMoment();
    });
  }

  toggleUnfinishedStepsScope(): void {
    localStorage.setItem(this.model + 'StepsShowTeam', (localStorage.getItem(this.model + 'StepsShowTeam') === '1' ? '0' : '1'));
    this.unfinishedStepsScope = (this.unfinishedStepsScope === 'user' ? 'team' : 'user');
    this.loadUnfinishedStep();
  }

  loadUnfinishedStep(): void {
    this.getUnfinishedStep(EntityType.Experiment);
    this.getUnfinishedStep(EntityType.Item);
  }

  getUnfinishedStep(type: EntityType): Promise<void> {
    return this.api.getJson(`unfinished_steps?scope=${this.unfinishedStepsScope}`).then(json => {
      let html = '';
      for (const entity of json[type] as Array<UnfinishedEntities>) {
        html += `<div class='side-panel-item'><p><a href='${type === EntityType.Item ? 'database' : 'experiments'}.php?mode=view&id=${entity.id}'>${entity.title}</a></p>`;
        for (const stepsData of Object.entries(entity.steps)) {
          const stepId = stepsData[1][0];
          const stepBody = stepsData[1][1];
          html += `<div><input type='checkbox' class='stepbox mr-2' id='todo_step_${stepId}' data-id='${entity.id}' data-type='${type}' data-stepid='${stepId}' />${stepBody}</div>`;
        }
        html += '</div>';
      }
      const typeIdName = 'todoSteps' + type.charAt(0).toUpperCase() + type.slice(1);
      document.getElementById(typeIdName).innerHTML = html;
    });
  }

  // TOGGLE TODOLIST VISIBILITY
  toggle(): void {
    // force favtags to close if it's open
    (new FavTag).hide();
    super.toggle();
    // lazy load content only once
    if (!document.getElementById(this.panelId).hasAttribute('hidden') && this.initialLoad) {
      this.display();
      this.loadUnfinishedStep();
      this.initialLoad = false;
    }
  }

  destroy(id: number): Promise<Response> {
    return this.api.delete(`${this.model}/${id}`);
  }
}
