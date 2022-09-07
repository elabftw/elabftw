/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Entity from './Entity.class';
import { EntityType } from './interfaces';

export default class Templates extends Entity {

  constructor() {
    super(EntityType.Template);
  }

  create(title: string): Promise<Response> {
    const params = {'title': title};
    return this.api.post(`${this.model}`, params);
  }
}
