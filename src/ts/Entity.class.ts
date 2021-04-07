/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, EntityType, Action, Target, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';


export default class Entity {
  model: EntityType;
  sender: Ajax;

  constructor(model: EntityType) {
    this.model = model;
    this.sender = new Ajax();
  }

  create(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      id: id,
      entity: {
        type: this.model,
        id: null,
      },
    };
    return this.sender.send(payload);
  }

  update(id: number, target: Target, content: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      entity: {
        type: this.model,
        id: id,
      },
      content: content,
      target: target,
    };
    return this.sender.send(payload);
  }

  lock(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Lock,
      model: this.model,
      entity: {
        type: this.model,
        id: id,
      },
    };
    return this.sender.send(payload);
  }

  duplicate(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Duplicate,
      model: this.model,
      entity: {
        type: this.model,
        id: id,
      },
    };
    return this.sender.send(payload);
  }

  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      entity: {
        type: this.model,
        id: id,
      },
    };
    return this.sender.send(payload);
  }
}
