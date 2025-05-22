/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from 'i18next';
import { I18nOptions, NotificationType } from './interfaces';

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

  constructor(key: string, type: NotificationType, options?: I18nOptions) {
    this.message = i18next.t(key, options);
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

    // error message takes longer to disappear
    const fadeOutDelay = this.type === NotificationType.Success ? 2733 : 4871;
    setTimeout(() => {
      $(overlay).fadeOut(763, function() {
        $(this).remove();
      });
    }, fadeOutDelay);
  }
}

// display the notification AND log in the console for debugging.
class ErrorNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Error, options);
    console.error(i18next.t(key));
  }
}

class SuccessNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Success, options);
  }
}

// to handle json responses with true or false
class ResponseNotification {
  constructor(json: { res: boolean; msg: string }) {
    if (json.res === true) {
      new SuccessNotification(json.msg);
    } else {
      new ErrorNotification(json.msg);
      console.error(json.msg);
    }
  }
}

// TODO-notifications: see where Warnings could be used instead of Errors. If not relevant, remove.
class WarningNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Warning, options);
    console.warn(i18next.t(key));
  }
}

export {
  ErrorNotification,
  SuccessNotification,
  ResponseNotification,
  WarningNotification,
};
