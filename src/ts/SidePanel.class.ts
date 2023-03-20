/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { Model } from './interfaces';
import { Api } from './Apiv2.class';

export default class SidePanel {
  panelId: string;
  model: Model;
  api: Api;

  constructor(model: Model) {
    this.model = model;
    this.api = new Api();
  }

  hide(): void {
    // make container great again
    $('#container').css('width', '100%').css('margin-left', 'auto');
    // hide panel
    document.getElementById(this.panelId).toggleAttribute('hidden', true);
    // store the current state
    localStorage.setItem(`is${this.model}Open`, '0');
    const opener = document.getElementById(`${this.panelId}Opener`);
    opener.classList.add('bounce-right');
    opener.classList.remove('sidepanel-opened');
    opener.classList.add('sidepanel-closed');
  }

  show(): void {
    $('#container').css('width', '78%').css('margin-left', 'max(22%, 300px)');
    // show panel
    document.getElementById(this.panelId).removeAttribute('hidden');
    // store the current state
    localStorage.setItem(`is${this.model}Open`, '1');
    const opener = document.getElementById(`${this.panelId}Opener`);
    opener.classList.remove('bounce-right');
    opener.classList.add('sidepanel-opened');
    opener.classList.remove('sidepanel-closed');
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
