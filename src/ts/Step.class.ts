/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Target, Entity, Action, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';

export default class Step {
  entity: Entity;
  model: Model;
  sender: Ajax;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = Model.Step,
    this.sender = new Ajax();
  }

  create(content: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      entity: this.entity,
      content: content,
    };
    return this.sender.send(payload);
  }

  update(id: number, content: string|null, target = Target.Body): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      target: target,
      entity: this.entity,
      content: content,
      id : id,
      notif: true,
    };
    return this.sender.send(payload);
  }

  finish(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      target: Target.Finished,
      entity: this.entity,
      id : id,
    };
    return this.sender.send(payload);
  }

  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      entity: this.entity,
      id : id,
    };
    return this.sender.send(payload);
  }
}
