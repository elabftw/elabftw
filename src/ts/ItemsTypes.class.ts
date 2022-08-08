/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Entity from './Entity.class';
import { EntityType } from './interfaces';

export default class ItemsTypes extends Entity {

  constructor() {
    super(EntityType.ItemType);
  }

  // todo make a parent for templates and items types
  create(title: string): Promise<Response> {
    const params = {'title': title};
    return this.api.post(`${this.model}`, params);
  }
}
