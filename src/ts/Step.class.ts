/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model, Target, Entity } from './interfaces';
import { Api } from './Apiv2.class';

export default class Step {
  entity: Entity;
  model: Model;
  api: Api;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = Model.Step,
    this.api = new Api();
  }

  create(content: string): Promise<Response> {
    return this.api.post(`${this.entity.type}/${this.entity.id}/${this.model}`, {'body': content});
  }

  update(id: number, content: string|null, target = Target.Body): Promise<Response> {
    const params = {};
    params[target] = content;
    return this.api.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, params);
  }

  finish(id: number): Promise<Response> {
    return this.api.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, {'action': 'finish'});
  }

  notif(id: number): Promise<Response> {
    return this.api.patch(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`, {'action': 'notif'});
  }

  destroy(id: number): Promise<Response> {
    return this.api.delete(`${this.entity.type}/${this.entity.id}/${this.model}/${id}`);
  }
}
