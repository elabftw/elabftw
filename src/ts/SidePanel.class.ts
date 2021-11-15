/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model } from './interfaces';
import { Ajax } from './Ajax.class';

export default class SidePanel {
  panelId: string;
  model: Model;
  sender: Ajax;

  constructor(model: Model) {
    this.model = model,
    this.sender = new Ajax();
  }

  hide(): void {
    // make container great again
    $('#container').css('width', '100%').css('margin-left', 'auto');
    // hide panel
    document.getElementById(this.panelId).toggleAttribute('hidden', true);
    // store the current state
    localStorage.setItem(`is${this.model}Open`, '0');
  }

  show(): void {
    $('#container').css('width', '78%').css('margin-left', 'max(22%, 300px)');
    // show panel
    document.getElementById(this.panelId).removeAttribute('hidden');
    // store the current state
    localStorage.setItem(`is${this.model}Open`, '1');
  }

  // TOGGLE PANEL VISIBILITY
  toggle(): void {
    if (document.getElementById(this.panelId).hasAttribute('hidden')) {
      this.show();
    } else {
      this.hide();
    }
  }
}
