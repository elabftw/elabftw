/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Ajax } from './Ajax.class';
import { Payload, Method, Model, Action, Type, ResponseMsg } from './interfaces';
import tinymce from 'tinymce/tinymce';
import { saveAs } from 'file-saver/dist/FileSaver.js';

export default class Template {
  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.Template;
    this.sender = new Ajax();
  }

  create(title: string, body = ''): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      entity: {
        type: Type.ExperimentTemplate,
        id: null,
      },
      content: title,
      extraParams: {
        body: body,
      },
    };
    return this.sender.send(payload);
  }

  lock(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Lock,
      model: this.model,
      entity: {
        type: Type.ExperimentTemplate,
        id: id,
      },
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

  duplicate(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Duplicate,
      model: this.model,
      entity: {
        type: Type.ExperimentTemplate,
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
        type: Type.ExperimentTemplate,
        id: id,
      },
    };
    return this.sender.send(payload);
  }
}
