/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model, Target, Entity, Action } from './interfaces';
import { Api } from './Apiv2.class';
import { getEditor } from './Editor.class';

export default class Step {
  entity: Entity;
  model: Model;
  api: Api;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = Model.Step;
    this.api = new Api();
  }

  create(content: string): Promise<Response> {
    return this.api.post(`${this.entity.type}/${this.entity.id}/${this.model}`, {'body': content});
  }

  update(id: number, content: string|null, target = Target.Body): Promise<Response> {
    const params = {};
    params[target] = content;
    // if we edit the body of the step, also change it in the editor body
    if (target === Target.Body) {
      const editor = getEditor();
      // read the old step and replace it in the entity body
      this.api.getJson(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`).then(json => {
        editor.replaceContent(editor.getContent().replace(json.body, content));
      });
    }
    return this.api.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, params);
  }

  finish(id: number): Promise<Response> {
    return this.genericPatch(id, Action.Finish);
  }

  notif(id: number): Promise<Response> {
    return this.genericPatch(id, Action.Notif);
  }

  genericPatch(id: number, action: Action): Promise<Response> {
    return this.api.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, {action});
  }

  destroy(id: number): Promise<Response> {
    return this.api.delete(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`);
  }
}
