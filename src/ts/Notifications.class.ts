/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara <mouss@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from 'i18next';
import { NotificationType, I18nOptions } from './interfaces';

class Notification {
  protected readonly message: string;
  protected readonly type: NotificationType;

  constructor(key: string, type: NotificationType, options?: I18nOptions) {
    this.message = i18next.t(key, options);
    this.type = type;
    this.show();
  }

  /**
   * Returns an i18n translated string, both single and interpolated.
   * Examples:
   * - 'add-quantity' => 'Add quantity'
   * - 'increment-something', 5 => 'Add 5 units'
   * - 'Random sentence' => 'Random sentence' (if not found in i18n catalog)
   */

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
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Error, options);
  }
}

class SuccessNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Success, options);
  }
}

class WarningNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Warning, options);
  }
}

class InfoNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Info, options);
  }
}

class DebugNotification extends Notification {
  constructor(key: string, options?: I18nOptions) {
    super(key, NotificationType.Debug, options);
  }
}

export {
  ErrorNotification,
  InfoNotification,
  SuccessNotification,
  WarningNotification,
  DebugNotification,
};
