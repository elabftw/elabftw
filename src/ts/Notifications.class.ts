/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from 'i18next';
import { TranslatedInput, NotificationType } from './interfaces';

class Notification {
  protected readonly message: string;
  protected readonly type: NotificationType;

  constructor(input: string | TranslatedInput, type: NotificationType) {
    this.message = this.resolveMessage(input);
    this.type = type;
    this.show();
  }

  /**
   * Returns an i18n translated string, both single and interpolated.
   * Examples:
   * - 'add-quantity' => 'Add quantity'
   * - { key: 'add-quantity', options: { qty: 5 } } => 'Add 5 of this'
   * - 'Random sentence' => 'Random sentence' (if not found in i18n catalog)
   */
  private resolveMessage(input: string | TranslatedInput): string {
    if (typeof input === 'string') {
      return i18next.t(input);
    }
    return String(i18next.t(input.key, input.options));
  }

  protected show(): void {
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
    const fadeOutDelay = this.type === NotificationType.Success ? 2733 : 4871;
    setTimeout(() => {
      $('#overlay').fadeOut(763, function() {
        $(this).remove();
      });
    }, fadeOutDelay);
  }
}

class ErrorNotification extends Notification {
  constructor(input: string | TranslatedInput) {
    super(input, NotificationType.Error);
  }
}

class InfoNotification extends Notification {
  constructor(input: string | TranslatedInput) {
    super(input, NotificationType.Info);
  }
}

class SuccessNotification extends Notification {
  constructor(input: string | TranslatedInput) {
    super(input, NotificationType.Success);
  }
}

class WarningNotification extends Notification {
  constructor(input: string | TranslatedInput) {
    super(input, NotificationType.Warning);
  }
}

class DebugNotification extends Notification {
  constructor(input: string | TranslatedInput) {
    super(input, NotificationType.Debug);
  }
}

export {
  ErrorNotification,
  InfoNotification,
  SuccessNotification,
  WarningNotification,
  DebugNotification,
};
