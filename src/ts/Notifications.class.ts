/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Moustapha Camara - Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import i18next from './i18n';
import { ResponseMsg } from './interfaces';

enum NotificationType {
  Error = 'error',
  Success = 'success',
  Warning = 'warning'
}

const FLASH_PREFIX = 'flash_';

type I18nOptions = Record<string, string | number | boolean>;

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

  // log the error in console and show a translated readable notification.
  public error(msg: string|Error, options?: I18nOptions): void {
    const translated = i18next.t(String(msg), options);
    console.error(translated);
    this.notify(translated, NotificationType.Error);
  }

  public warning(msg: string, options?: I18nOptions): void {
    const translated = i18next.t(msg, options);
    console.warn(translated);
    this.notify(translated, NotificationType.Warning);
  }

  // to handle json responses
  public response(json: ResponseMsg): void {
    // eslint-disable-next-line @typescript-eslint/no-unused-expressions
    (json.res === true
      ? this.success(json.msg)
      : this.error(json.msg));
  }

  /**
   * Consume and display flash notifications stored in sessionStorage.
   * Notifications are shown once and then removed. Intended to be called on page load.
   * example usage:
   * sessionStorage.setItem('flash_ownershipTransfer', i18next.t('ownership-transfer'));
   * Location reloads and consumes the message.
   */
  public flashSuccess(): void {
    Object.keys(sessionStorage).filter(key => key.startsWith(FLASH_PREFIX))
      .forEach(key => {
        const message = sessionStorage.getItem(key);
        if (!message) {
          sessionStorage.removeItem(key);
          return;
        }
        this.notify(message, NotificationType.Success);
        sessionStorage.removeItem(key);
      });
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
    const closeEl = document.createElement('span');
    const closeIcon = document.createElement('i');
    if (type === NotificationType.Success) {
      // success gets removed on animation end
      overlay.addEventListener('animationend', () => {
        overlay.remove();
      });
    } else { // warning and error get a button to close them
      closeEl.classList.add('clickable', 'ml-3', 'float-right');
      closeIcon.classList.add('fas', 'fa-xmark');
      closeEl.append(closeIcon);
      closeEl.addEventListener('click', () => overlay.remove());
    }
    // create overlay content
    const p = document.createElement('p');
    // "status" role: see WCAG2.1 4.1.3
    p.role = 'status';
    p.innerText = message;
    overlay.appendChild(p);
    p.appendChild(closeEl);
    container.appendChild(overlay);
  }
}
