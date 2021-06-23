/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, EntityType, Action, Target, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';
import tinymce from 'tinymce/tinymce';
import { saveAs } from 'file-saver/dist/FileSaver.js';


export default class Entity {
  model: EntityType;
  sender: Ajax;

  constructor(model: EntityType) {
    this.model = model;
    this.sender = new Ajax();
  }

  // content can be a template id (for experiments), an itemtype id (for items) or a template title
  create(content: string): Promise<ResponseMsg> {
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

  saveToFile(id, name): void {
    // we have the name of the template used for filename
    // and we have the id of the editor to get the content from
    // we don't use activeEditor because it requires a click inside the editing area
    const content = tinymce.get('e' + id).getContent();
    const blob = new Blob([content], {type: 'text/plain;charset=utf-8'});
    saveAs(blob, name + '.elabftw.tpl');
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
