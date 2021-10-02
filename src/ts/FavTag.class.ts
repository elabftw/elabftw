/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Action, ResponseMsg } from './interfaces';
import { Ajax } from './Ajax.class';

export default class FavTag {
  model: Model;
  sender: Ajax;

  constructor() {
    this.model = Model.FavTag,
    this.sender = new Ajax();
  }

  // ADD A TAG AS FAVORITE
  create(content: string): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Create,
      model: this.model,
      content: content,
      id: null,
    };
    return this.sender.send(payload);
  }

  // TOGGLE FAVTAGS PANEL VISIBILITY
  toggle(): void {
    if ($('#favtags-panel').is(':visible')) {
      $('#container').css('width', '100%').css('margin-right', 'auto');
      localStorage.setItem('isFavtagsOpen', '0');
    } else {
      $('#container').css('width', '70%').css('margin-right', '0');
      localStorage.setItem('isFavtagsOpen', '1');
    }
    $('#favtags-panel').toggle();
  }

  // REMOVE A FAVORITE TAG
  destroy(id: number): Promise<ResponseMsg> {
    const payload: Payload = {
      method: Method.POST,
      action: Action.Destroy,
      model: this.model,
      id: id,
    };
    return this.sender.send(payload);
  }
}
