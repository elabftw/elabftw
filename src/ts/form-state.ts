/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { errorMessage } from './misc';

/** Display an error inside a form, where it stays next to the values that caused it, instead of a transient toast. */
export function showFormError(element: HTMLElement, error: unknown): void {
  element.textContent = errorMessage(error);
  element.hidden = false;
}

/**
 * Serialize the requests of a form: its controls, and the buttons outside of it that start an operation,
 * stay disabled until the request ends, so a second click cannot send it twice.
 */
export class BusyState {
  private running = false;

  constructor(private readonly buttons: HTMLButtonElement[] = []) {}

  get busy(): boolean {
    return this.running;
  }

  /**
   * Run task with the form disabled. Resolves to true only if the task completed: it neither threw
   * (onError then gets the error) nor returned false (e.g. the user cancelled a confirmation).
   * The form is enabled again before this resolves, so a modal guarded by `busy` can be closed right after.
   */
  async run(form: HTMLFormElement, task: () => Promise<boolean | void>, onError: (error: unknown) => void): Promise<boolean> {
    if (this.running) {
      return false;
    }
    this.set(form, true);
    try {
      return await task() !== false;
    } catch (error) {
      onError(error);
      return false;
    } finally {
      this.set(form, false);
    }
  }

  private set(form: HTMLFormElement, value: boolean): void {
    this.running = value;
    this.buttons.forEach(button => button.disabled = value);
    form.querySelectorAll<HTMLInputElement | HTMLButtonElement | HTMLSelectElement | HTMLTextAreaElement>('input, button, select, textarea')
      .forEach(control => control.disabled = value);
  }
}
