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

  // content can be a template id (for experiments), an itemtype id (for items) or a template title
  create(content: string, tags: Array<string>): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      // this id is the experiment template or item type id
      content: content,
      entity: {
        type: this.model,
        id: null,
      },
      extraParams: {
        tags: tags,
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
      notif: true,
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
      notif: true,
    };
    return this.sender.send(payload);
  }

  pin(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Pin,
      model: this.model,
      entity: {
        type: this.model,
        id: id,
      },
      notif: true,
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
