/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { EntityType, Action, Target } from './interfaces';
import { Api } from './Apiv2.class';

export default class Entity {
  model: EntityType;
  api: Api;

  constructor(model: EntityType) {
    this.model = model;
    this.api = new Api();
  }

  // content can be a template id (for experiments), an itemtype id (for items) or a template title
  create(content: string, tags: Array<string>): Promise<Response> {
    const params = {'category_id': content, 'tags': tags};
    return this.api.post(`${this.model}`, params);
  }

  read(id: number): Record<string, any> {
    return this.api.getJson(`${this.model}/${id}`);
  }

  update(id: number, target: Target, content: string): Promise<Response> {
    const params = {};
    params[target] = content;
    return this.api.patch(`${this.model}/${id}`, params);
  }

  lock(id: number): Promise<Response> {
    return this.api.patch(`${this.model}/${id}`, {'action': Action.Lock});
  }

  pin(id: number): Promise<Response> {
    return this.api.patch(`${this.model}/${id}`, {'action': Action.Pin});
  }

  duplicate(id: number): Promise<Response> {
    return this.api.post(`${this.model}/${id}`, {'action': Action.Duplicate});
  }

  timestamp(id: number): Promise<Response> {
    return this.api.patch(`${this.model}/${id}`, {'action': Action.Timestamp});
  }


  destroy(id: number): Promise<Response> {
    return this.api.delete(`${this.model}/${id}`);
  }
}
