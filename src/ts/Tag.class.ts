/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Entity, Payload, Method, Model, Target, Action, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';

export default class Tag {
  entity: Entity;
  model: Model;
  sender: Ajax;

  constructor(entity: Entity) {
    this.entity = entity;
    this.model = Model.Tag,
    this.sender = new Ajax();
  }

  // REFERENCE A TAG
  create(content: string, itemId: number = null): Promise<ResponseMsg> {
    if (itemId) {
      this.entity.id = itemId;
    }
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      entity: this.entity,
      content: content,
    };
    return this.sender.send(payload);
  }

  update(content: string, id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: Model.Tag,
      entity: this.entity,
      content: content,
      id: id,
    };
    return this.sender.send(payload);
  }

  // REMOVE THE TAG FROM AN ENTITY
  unreference(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      entity: this.entity,
      target: Target.Unreference,
      id: id,
    };
    return this.sender.send(payload);
  }

  // DEDUPLICATE
  deduplicate(): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Deduplicate,
      model: this.model,
      entity: this.entity,
    };
    return this.sender.send(payload);
  }

  // REMOVE A TAG COMPLETELY (from admin panel/tag manager)
  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      entity: this.entity,
      id: id,
    };
    return this.sender.send(payload);
  }
}
