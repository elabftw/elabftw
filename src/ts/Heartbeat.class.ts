/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

import $ from 'jquery';
import { ApiC } from './api';
import { on } from './handlers';
import { Model } from './interfaces';
import { clearLocalStorage } from './localStorage';
import i18next from './i18n';

interface SessionStatus {
  expires_at: number | null;
}

export default class Heartbeat {
  private static readonly HEART_RATE = 300000;

  private static readonly WARNING_DURATION = 60000;

  private warningTimer: number | undefined;

  private countdownTimer: number | undefined;

  constructor() {
    const sessionExpiresAtMeta =
      document.querySelector<HTMLMetaElement>('meta[name="session-expires-at"]');

    if (sessionExpiresAtMeta) {
      const expiresAt = Number(sessionExpiresAtMeta.content) * 1000;

      if (Number.isFinite(expiresAt)) {
        this.scheduleSessionWarning(expiresAt);
      }
    }

    window.setInterval(() => {
      void this.checkSession();
    }, Heartbeat.HEART_RATE);

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        void this.checkSession();
      }
    });

    on('extend-session', el => {
      void this.extendSession(el as HTMLButtonElement);
    });
  }

  private async checkSession(): Promise<void> {
    try {
      const response = await fetch('app/controllers/HeartBeat.php', {
        credentials: 'same-origin',
        cache: 'no-store',
      });

      if (response.status === 401) {
        this.handleExpiredSession();
        return;
      }

      if (!response.ok) {
        throw new Error(`Heartbeat failed with ${response.status}`);
      }

      const status = await response.json() as SessionStatus;

      if (status.expires_at === null) {
        this.clearTimers();
        $('#sessionExpirationModal').modal('hide');
        return;
      }

      const expiresAt = status.expires_at * 1000;
      const warningAt = expiresAt - Heartbeat.WARNING_DURATION;

      if (Date.now() < warningAt) {
        this.scheduleSessionWarning(expiresAt);
        return;
      }

      this.showSessionExpirationModal(expiresAt);
    } catch (error) {
      console.error('Heartbeat failed:', error);
    }
  }

  private scheduleSessionWarning(expiresAt: number): void {
    this.clearTimers();
    $('#sessionExpirationModal').modal('hide');

    const delay = Math.max(
      0,
      expiresAt - Date.now() - Heartbeat.WARNING_DURATION,
    );

    this.warningTimer = window.setTimeout(() => {
      void this.checkSession();
    }, delay);
  }

  private async showSessionExpirationModal(expiresAt: number): Promise<void> {
    if (this.warningTimer !== undefined) {
      window.clearTimeout(this.warningTimer);
      this.warningTimer = undefined;
    }

    if (this.countdownTimer !== undefined) {
      window.clearInterval(this.countdownTimer);
    }

    const countdown = document.getElementById(
      'sessionExpirationCountdown',
    );

    const updateCountdown = (): void => {
      const secondsRemaining = Math.max(
        0,
        Math.ceil((expiresAt - Date.now()) / 1000),
      );

      if (countdown) {
        countdown.textContent = String(secondsRemaining);
      }

      if (secondsRemaining === 0) {
        if (this.countdownTimer !== undefined) {
          window.clearInterval(this.countdownTimer);
          this.countdownTimer = undefined;
        }

        // The session may have been extended by another request or tab.
        void this.checkSession();
      }
    };

    updateCountdown();

    this.countdownTimer = window.setInterval(
      updateCountdown,
      1000,
    );

    $('#sessionExpirationModal').modal('show');

    let permission = Notification.permission;
    if (Notification.permission === 'default') {
      permission = await Notification.requestPermission();
    }
    if (
      document.hidden
      && 'Notification' in window
      && permission === 'granted'
    ) {
      const notification = new Notification(i18next.t('session-expiring-title'), {
        body: i18next.t('session-expiring'),
        tag: 'elabftw-session-expiration',
      });

      notification.onclick = () => {
        window.focus();
        notification.close();
      };
    }
  }

  private async extendSession(button: HTMLButtonElement): Promise<void> {
    button.disabled = true;

    try {
      // Any normal authenticated request extends session_expires_at
      // through App::boot().
      await ApiC.get(`${Model.User}/me`);

      // Retrieve the new authoritative expiration timestamp.
      await this.checkSession();
    } catch (error) {
      console.error('Failed to extend session:', error);
      // Fall back to re-checking session state so an already-expired
      // session is caught and the user is redirected.
      void this.checkSession();
    } finally {
      button.disabled = false;
    }
  }

  private clearTimers(): void {
    if (this.warningTimer !== undefined) {
      window.clearTimeout(this.warningTimer);
      this.warningTimer = undefined;
    }

    if (this.countdownTimer !== undefined) {
      window.clearInterval(this.countdownTimer);
      this.countdownTimer = undefined;
    }
  }

  private handleExpiredSession(): void {
    this.clearTimers();
    clearLocalStorage();
    window.location.replace('login.php');
  }
}
