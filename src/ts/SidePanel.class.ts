/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import $ from 'jquery';
import { Model } from './interfaces';

export default class SidePanel {
  panelId: string;
  model: Model;

  constructor(model: Model) {
    this.model = model;
  }

  hide(): void {
    // make container great again
    $('#container').css('width', '100%').css('margin-left', 'auto');
    // hide panel
    document.getElementById(this.panelId).toggleAttribute('hidden', true);
    // store the current state
    localStorage.removeItem('opened-sidepanel');
    const opener = document.getElementById(`${this.panelId}Opener`);
    opener.classList.add('bounce-right');
    opener.classList.remove('bounce-left');
    opener.classList.remove('sidepanel-opened');
    opener.classList.add('sidepanel-closed');
  }

  show(): void {
    $('#container').css('width', '76%').css('margin-left', 'max(24%, 330px)');
    // show panel
    document.getElementById(this.panelId).removeAttribute('hidden');
    // store the current state
    localStorage.setItem('opened-sidepanel', this.model);
    const opener = document.getElementById(`${this.panelId}Opener`);
    opener.classList.remove('bounce-right');
    opener.classList.add('bounce-left');
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
