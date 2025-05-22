/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from 'i18next';
import { I18nOptions, NotificationType, ResponseMsg } from './interfaces';

/**
 * Returns an i18n translated string, both single and interpolated.
 * Overlays come in different types : Success, Error, Warning
 * Examples:
 * - 'add-quantity' => 'Add quantity'
 * - 'increment-something', 5 => 'Add 5 units'
 * - 'Random sentence' => 'Random sentence' (if not found in i18n catalog)
 */
class Notification {
  protected readonly message: string;
  protected readonly type: NotificationType;

  constructor(msg: string, type: NotificationType, options?: I18nOptions) {
    this.message = i18next.t(msg, options);
    this.type = type;
    this.show();
  }

  protected show(): void {
    // add a container to hold all overlays, allow stacking
    let container = document.getElementById('overlay-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'overlay-container';
      document.body.appendChild(container);
    }

    // create overlay
    const overlay = document.createElement('div');
    overlay.classList.add('overlay', `overlay-${this.type}`);

    // create overlay content
    const p = document.createElement('p');
    // "status" role: see WCAG2.1 4.1.3
    p.role = 'status';
    p.innerText = this.message;
    // show the overlay
    overlay.appendChild(p);
    container.appendChild(overlay);

    // overlay animation: fades in and out. Remove element when ended
    overlay.addEventListener('animationend', () => {
      overlay.remove();
    });
  }
}

// display the notification AND log in the console for debugging.
class ErrorNotification extends Notification {
  constructor(msg: string, options?: I18nOptions) {
    super(msg, NotificationType.Error, options);
    console.error(i18next.t(msg));
  }
}

class SuccessNotification extends Notification {
  constructor(msg: string, options?: I18nOptions) {
    super(msg, NotificationType.Success, options);
  }
}

// to handle json responses with true or false
class ResponseNotification {
  constructor(json: ResponseMsg) {
    if (json.res === true) {
      new SuccessNotification(json.msg);
    } else {
      new ErrorNotification(json.msg);
    }
  }
}

// TODO-notifications: see where Warnings could be used instead of Errors. If not relevant, remove.
class WarningNotification extends Notification {
  constructor(msg: string, options?: I18nOptions) {
    super(msg, NotificationType.Warning, options);
    console.warn(i18next.t(msg));
  }
}

export {
  ErrorNotification,
  SuccessNotification,
  ResponseNotification,
  WarningNotification,
};
