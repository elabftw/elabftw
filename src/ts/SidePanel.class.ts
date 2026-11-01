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

  // Keep legacy floating panels intact; navbar panels only toggle inline.
  private setOpen(open: boolean): void {
    const panel = document.getElementById(this.panelId);
    if (!panel) return;
    const inNavbar = !!panel.closest('.navbar-side-bottom');
    if (!inNavbar) {
      $('#container').css('width', open ? '76%' : '100%')
        .css('margin-left', open ? 'max(24%, 330px)' : 'auto');
    }
    panel.toggleAttribute('hidden', !open);
    if (open) {
      localStorage.setItem('opened-sidepanel', this.model);
    } else {
      localStorage.removeItem('opened-sidepanel');
    }
    const opener = document.getElementById(`${this.panelId}Opener`);
    if (!inNavbar) {
      opener?.classList.toggle('btn-neutral', !open);
      opener?.classList.toggle('btn-secondary', open);
    }
    opener?.setAttribute('aria-expanded', String(open));
  }

  hide(): void { this.setOpen(false); }

  show(): void { this.setOpen(true); }

  toggle(): void {
    this.setOpen(document.getElementById(this.panelId)?.hasAttribute('hidden') ?? false);
  }
}
