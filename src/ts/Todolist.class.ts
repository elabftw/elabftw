/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model, EntityType, UnfinishedEntities } from './interfaces';
import SidePanel from './SidePanel.class';
import { escapeHTML } from './misc';
import FavTag from './FavTag.class';
import { ApiC } from './api';
import { mount } from 'svelte';
import TodolistSv from './components/Todolist.svelte';

export default class Todolist extends SidePanel {

  unfinishedStepsScope: string;
  private static mounted = false;


  constructor() {
    super(Model.Todolist);
    this.panelId = 'todolistPanel';
    this.unfinishedStepsScope = 'user';
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
    return ApiC.getJson(`unfinished_steps?scope=${this.unfinishedStepsScope}`).then(json => {
      let html = '';
      for (const entity of json[type] as Array<UnfinishedEntities>) {
        html += `<div class='side-panel-item'><p><a href='${type === EntityType.Item ? 'database' : 'experiments'}.php?mode=view&id=${entity.id}'>${escapeHTML(entity.title)}</a></p>`;
        for (const stepsData of Object.entries(entity.steps)) {
          const stepId = stepsData[1][0];
          const stepBody = stepsData[1][1];
          html += `<div><input type='checkbox' class='stepbox mr-2' id='todo_step_${stepId}' data-id='${entity.id}' data-type='${type}' data-stepid='${stepId}' />${escapeHTML(stepBody)}</div>`;
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
    (new FavTag()).hide();
    super.toggle();
    const panel = document.getElementById(this.panelId);
    const isOpen = !!panel && !panel.hasAttribute('hidden');
    if (isOpen) {
      const host = document.getElementById('todolist');
      // Prevent mounting the Svelte component multiple times
      // (can happen when toggling via both click and keyboard shortcut)
      if (host && !Todolist.mounted && host.childElementCount === 0) {
        mount(TodolistSv, {
          target: host,
        });
        Todolist.mounted = true;
      }

      this.loadUnfinishedStep();
    }
  }
}
