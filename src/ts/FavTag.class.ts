/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Payload, Method, Model, Action, ResponseMsg } from './interfaces';
import SidePanel from './SidePanel.class';
import TodoList from './Todolist.class';

export default class FavTag extends SidePanel {

  constructor() {
    super(Model.FavTag);
    this.panelId = 'favtagsPanel';
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

  toggle(): void {
    // force todolist to close if it's open
    (new TodoList).hide();
    super.toggle();
    // toggle the opener icon (>)
    document.getElementById('favtags-opener').toggleAttribute('hidden');
  }
}
