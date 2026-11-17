/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model, Target, Action } from './interfaces';
import type { Entity } from './interfaces';
import { ApiC } from './api';
import { getEditor } from './Editor.class';
import i18next from './i18n';
import { reloadElements } from './misc';

export default class Step {
  entity: Entity;
  model: Model;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = Model.Step;
  }

  create(content: string, groupId: number|null = null): Promise<Response> {
    return ApiC.post(`${this.entity.type}/${this.entity.id}/${this.model}`, {body: content, group_id: groupId});
  }

  update(id: number, content: string|null, target = Target.Body): Promise<Response> {
    const params = {};
    params[target] = content;
    // if we edit the body of the step, also change it in the editor body
    if (target === Target.Body) {
      const editor = getEditor();
      // read the old step and replace it in the entity body
      ApiC.getJson(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`).then(json => {
        editor.replaceContent(editor.getContent().replace(json.body, content));
      });
    }
    return ApiC.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, params);
  }

  finish(id: number): Promise<Response> {
    return this.genericPatch(id, Action.Finish);
  }

  // toggle the finished state of a step from its checkbox, which can be in the steps of the page or in the Todolist panel
  // the browser has already changed the checkbox state when this is called
  finishFromCheckbox(input: HTMLInputElement): Promise<void> {
    // an unchecked box means the step was finished before: ask for confirmation
    if (!input.checked && !confirm(i18next.t('step-unfinish-warning'))) {
      input.checked = true;
      return Promise.resolve();
    }
    const stepId = parseInt(input.dataset.stepid, 10);
    return this.finish(stepId).then(() => {
      // keep the Todolist panel and the steps of the page in sync
      const todoBox = document.getElementById(`todo_step_${stepId}`);
      if (todoBox instanceof HTMLInputElement) {
        todoBox.checked = input.checked;
      }
      if (document.querySelector(`#stepsDiv .stepbox[data-stepid="${stepId}"]`)) {
        return reloadElements(['stepsDiv']);
      }
    }).catch(() => {
      // revert the visual state if the request failed
      input.checked = !input.checked;
    });
  }

  notif(id: number): Promise<Response> {
    return this.genericPatch(id, Action.Notif);
  }

  notifDestroy(id: number): Promise<Response> {
    return this.genericPatch(id, Action.NotifDestroy);
  }

  genericPatch(id: number, action: Action): Promise<Response> {
    return ApiC.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, {action});
  }

  destroy(id: number): Promise<Response> {
    return ApiC.delete(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`);
  }
}
