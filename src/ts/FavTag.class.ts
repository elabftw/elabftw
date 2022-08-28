/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model } from './interfaces';
import SidePanel from './SidePanel.class';
import TodoList from './Todolist.class';

export default class FavTag extends SidePanel {

  constructor() {
    super(Model.FavTag);
    this.panelId = 'favtagsPanel';
  }

  // ADD A TAG AS FAVORITE
  create(content: string): Promise<Response> {
    return this.api.post(Model.FavTag, {'tag': content });
  }

  // REMOVE A FAVORITE TAG
  destroy(id: number): Promise<Response> {
    return this.api.delete(`${Model.FavTag}/${id}`);
  }

  toggle(): void {
    // force todolist to close if it's open
    (new TodoList).hide();
    super.toggle();
    // toggle the opener icon (>)
    document.getElementById('favtags-opener').toggleAttribute('hidden');
  }
}
