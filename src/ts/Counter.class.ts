/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * This class is responsible for creating a mutation observer that will count all .countable items of the element that has a data-count-for attribute
 * The count is updated in the element whose id is the value of data-count-for
 * This is used for steps and links for instance
 */
export class Counter {
  private countEl: HTMLElement;
  private container: HTMLElement;

  constructor(container: HTMLElement) {
    this.container = container;
    this.countEl = document.getElementById(this.container.dataset.countFor as string) as HTMLElement;
    if (!this.countEl) {
      console.debug(`count element ${container.dataset.countFor} not found!`);
      return;
    }
    this.update(); // Initial update
    this.observe();
  }

  private update() {
    const count = this.container.getElementsByClassName('countable').length;
    this.countEl.textContent = `${count}`;
  }

  private observe() {
    const observer = new MutationObserver(() => this.update());
    observer.observe(this.container, { childList: true });
  }
}
