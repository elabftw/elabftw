/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
export default class ScrollButtons {
  private readonly container: HTMLDivElement;
  private readonly backToTopButton: HTMLButtonElement;

  public constructor() {
    this.container = document.createElement('div');
    this.container.id = 'scrollButtons';
    this.container.classList.add(
      'floating-middle-right',
      'd-none',
      'd-lg-flex',
      'flex-column',
    );

    this.backToTopButton = this.createButton('fa-arrow-up', 'Back to top');
    this.backToTopButton.id = 'backToTopButton';

    this.backToTopButton.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: 'smooth',
      });
    });
    this.container.prepend(this.backToTopButton);
  }

  public init(): void {
    const root = document.getElementById('container');
    if (!root) {
      return;
    }

    this.registerSectionButtons();
    root.append(this.container);
  }

  private registerSectionButtons(): void {
    document.querySelectorAll<HTMLElement>('[data-scroll-btn]').forEach(target => {
      const iconClass = target.dataset.scrollBtn?.trim();
      if (!iconClass) {
        return;
      }

      const extraOffset = Number.parseInt(target.dataset.scrollBtnY ?? '0', 10);
      if (Number.isFinite(extraOffset) && extraOffset !== 0) {
        target.style.setProperty('--scroll-btn-y', `${extraOffset}px`);
      }

      const label = target.dataset.scrollBtnLabel ?? target.innerText.trim();
      const button = this.createButton(iconClass, label);

      button.addEventListener('click', () => {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });
      });

      this.container.append(button);
    });
  }

  private createButton(iconClass: string, label: string): HTMLButtonElement {
    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add('btn', 'btn-secondary', 'scroll-button');
    button.setAttribute('aria-label', label);

    const text = document.createElement('span');
    text.classList.add('scroll-button-label');
    text.textContent = label;

    const icon = document.createElement('i');
    icon.classList.add('fas', 'fa-fw', iconClass);
    icon.setAttribute('aria-hidden', 'true');

    // text first so the button expands towards the left
    button.replaceChildren(text, icon);

    return button;
  }
}
