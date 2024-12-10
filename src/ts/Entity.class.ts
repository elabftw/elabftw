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
    const params = {'category_id': parseInt(content, 10), 'tags': tags};
    return this.api.post(`${this.model}`, params);
  }

  getPage(): string {
    switch (this.model) {
    case EntityType.Experiment:
      return 'experiments';
    case EntityType.Item:
      return 'database';
    case EntityType.ItemType:
      return 'admin.php';
    case EntityType.Template:
      return 'ucp.php';
    }
  }

  read(id: number) {
    return this.api.getJson(`${this.model}/${id}`);
  }

  patchAction(id: number, action: Action): Promise<Response> {
    return this.api.patch(`${this.model}/${id}`, {action});
  }

  update(id: number, target: Target, content: string): Promise<Response> {
    const params = {};
    params[target] = content;
    return this.api.patch(`${this.model}/${id}`, params);
  }

  duplicate(id: number, copyFiles: boolean): Promise<Response> {
    return this.api.post(`${this.model}/${id}`, {'action': Action.Duplicate, 'copyFiles': copyFiles});
  }

  destroy(id: number): Promise<Response> {
    return this.api.delete(`${this.model}/${id}`);
  }
}
