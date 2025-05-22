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
 * Overlays come in different types. See methods: success(), error(), etc.
 * Examples:
 * - 'add-quantity' => 'Add quantity'
 * - 'increment-something', 5 => 'Add 5 units'
 * - 'Random sentence' => 'Random sentence' (if not found in i18n catalog)
 */
export class Notification {
  // default value: 'Saved'
  public success(msg: string = 'saved', options?: I18nOptions): void {
    const translated = i18next.t(msg, options);
    this.notify(translated, NotificationType.Success);
  }

  // display the notification & log in the console for debugging.
  public error(msg: string, options?: I18nOptions): void {
    const translated = i18next.t(msg, options);
    console.error(translated);
    this.notify(translated, NotificationType.Error);
  }

  // TODO-notifications: see where Warnings could be used instead of Errors. If not relevant, remove.
  public warning(msg: string, options?: I18nOptions): void {
    const translated = i18next.t(msg, options);
    console.warn(translated);
    this.notify(translated, NotificationType.Warning);
  }

  // to handle json responses
  public response(json: ResponseMsg): void {
    if (json.res === true) {
      this.success(json.msg);
    } else {
      this.error(json.msg);
    }
  }

  private notify(message: string, type: NotificationType): void {
    // add a container to hold all overlays, allow stacking
    let container = document.getElementById('overlay-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'overlay-container';
      document.body.appendChild(container);
    }

    // create overlay
    const overlay = document.createElement('div');
    overlay.classList.add('overlay', `overlay-${type}`);
    // create overlay content
    const p = document.createElement('p');
    // "status" role: see WCAG2.1 4.1.3
    p.role = 'status';
    p.innerText = message;
    // show the overlay
    overlay.appendChild(p);
    container.appendChild(overlay);

    // overlay animation: fades in and out. Remove element when ended
    overlay.addEventListener('animationend', () => {
      overlay.remove();
    });
  }
}
