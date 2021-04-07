/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Entity, Action, EntityType, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';
import { getTinymceBaseConfig } from './tinymce';
import tinymce from 'tinymce/tinymce';


export default class ItemType {
  entity: Entity;
  model: EntityType;
  sender: Ajax;

  constructor() {
    this.model = EntityType.ItemType,
    this.sender = new Ajax();
  }

  create(content: string, color: string, bookable: number, body: string, canread: string, canwrite: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      entity: {
        type: EntityType.ItemType,
        id: null,
      },
      content: content,
      extraParams: {
        color: color,
        bookable: bookable,
        body: body,
        canread: canread,
        canwrite: canwrite,
      },
    };
    return this.sender.send(payload);
  }

  showEditor(id): void {
    $('#itemsTypesTemplate_' + id).addClass('mceditable');
    tinymce.init(getTinymceBaseConfig('items_types'));
    $('#itemsTypesEditor_' + id).toggle();
  }

  update(id: number, content: string, color: string, bookable: number, body: string, canread: string, canwrite: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Update,
      model: this.model,
      entity: {
        type: EntityType.ItemType,
        id: id,
      },
      content: content,
      extraParams: {
        color: color,
        bookable: bookable,
        body: body,
        canread: canread,
        canwrite: canwrite,
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
        type: EntityType.ItemType,
        id: id,
      },
    };
    return this.sender.send(payload);
  }
}
