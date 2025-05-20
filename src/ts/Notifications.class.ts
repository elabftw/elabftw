/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from 'i18next';
import { NotificationType, MessageInput } from './interfaces';

class Notification {
  private readonly message: string;
  private readonly type: NotificationType;

  constructor(input: string | MessageInput, type: NotificationType) {
    this.message = this.resolveMessage(input);
    this.type = type;
    this.show();
  }

  private resolveMessage(input: string | MessageInput): string {
    if (typeof input === 'string') {
      return i18next.t(input);
    }
    // in case of i18n interpolation, e.g. 'multi-changes-confirm', { num: checked.length }
    return String(i18next.t(input.key, input.options));
  }

  public show(): void {
    // remove existing
    if (document.getElementById('overlay')) {
      document.getElementById('overlay').remove();
    }
    const p = document.createElement('p');
    // "status" role: see WCAG2.1 4.1.3
    p.role = 'status';
    p.innerText = this.message;

    const overlay = document.createElement('div');
    overlay.id = 'overlay';
    overlay.classList.add('overlay', `overlay-${this.type}`);
    // show the overlay
    overlay.appendChild(p);
    document.body.appendChild(overlay);
    // error message takes longer to disappear
    const fadeOutDelay = this.type === 'ok' ? 2733 : 4871;
    setTimeout(() => {
      $('#overlay').fadeOut(763, function() {
        $(this).remove();
      });
    }, fadeOutDelay);
  }
}

export class ErrorNotification extends Notification {
  constructor(input: string | MessageInput) {
    super(input, NotificationType.KO);
  }
}

export class SuccessNotification extends Notification {
  constructor(input: string | MessageInput) {
    super(input, NotificationType.OK);
  }
}
