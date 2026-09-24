/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import i18next from './i18n';

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

    this.backToTopButton = this.createButton('fa-arrow-up', i18next.t('back-to-top'));
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

      if (target.dataset.scrollBtnHeadings && target.dataset.scrollBtnTop) {
        this.container.append(this.createTextNavigation(target, button));
        return;
      }

      button.addEventListener('click', () => {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start',
        });
      });

      this.container.append(button);
    });
  }

  private createTextNavigation(target: HTMLElement, trigger: HTMLButtonElement): HTMLDivElement {
    const wrapper = document.createElement('div');
    wrapper.classList.add('scroll-text-navigation');

    const menu = document.createElement('nav');
    menu.id = 'scrollTextNavigationMenu';
    menu.classList.add('scroll-text-navigation-menu');
    menu.setAttribute('aria-label', target.dataset.scrollBtnLabel ?? '');
    menu.setAttribute('aria-hidden', 'true');

    trigger.classList.add('scroll-text-navigation-trigger');
    trigger.setAttribute('aria-controls', menu.id);
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-haspopup', 'true');

    const setOpen = (open: boolean): void => {
      wrapper.classList.toggle('is-open', open);
      trigger.setAttribute('aria-expanded', String(open));
      menu.setAttribute('aria-hidden', String(!open));
    };
    const refresh = (): void => this.populateTextNavigation(target, menu);

    wrapper.addEventListener('pointerenter', () => {
      refresh();
      setOpen(true);
    });
    wrapper.addEventListener('pointerleave', event => {
      if (event.pointerType === 'touch') {
        return;
      }
      if (!wrapper.contains(document.activeElement)) {
        setOpen(false);
      }
    });
    trigger.addEventListener('focus', () => {
      refresh();
      setOpen(true);
    });
    wrapper.addEventListener('focusout', event => {
      const nextTarget = event.relatedTarget;
      if (!(nextTarget instanceof Node) || !wrapper.contains(nextTarget)) {
        setOpen(false);
      }
    });
    wrapper.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        trigger.focus({ preventScroll: true });
        setOpen(false);
      }
    });
    menu.addEventListener('click', event => {
      const clicked = event.target;
      if (clicked instanceof Element && clicked.closest('.scroll-text-navigation-link')) {
        trigger.focus({ preventScroll: true });
        setOpen(false);
      }
    });
    document.addEventListener('pointerdown', event => {
      const clicked = event.target;
      if (clicked instanceof Node && !wrapper.contains(clicked)) {
        setOpen(false);
      }
    });

    wrapper.replaceChildren(trigger, menu);
    return wrapper;
  }

  private populateTextNavigation(target: HTMLElement, menu: HTMLElement): void {
    const topTarget = document.getElementById(target.dataset.scrollBtnTop ?? '');
    const headingsSource = document.getElementById(target.dataset.scrollBtnHeadings ?? '');
    const headingsRoot = headingsSource instanceof HTMLIFrameElement
      ? headingsSource.contentDocument?.body
      : headingsSource;
    if (!topTarget || !headingsRoot) {
      menu.replaceChildren();
      return;
    }

    const list = document.createElement('ul');
    list.classList.add('scroll-text-navigation-list');

    list.append(this.createTextNavigationItem(
      target.dataset.scrollBtnTopLabel ?? '',
      'fa-arrow-up',
      () => this.scrollElementIntoView(topTarget),
    ));

    const headings = Array.from(headingsRoot.querySelectorAll<HTMLElement>('h1, h2, h3'));
    const parents: Array<{
      level: number;
      item: HTMLLIElement;
      childList?: HTMLUListElement;
    }> = [];

    headings.forEach(heading => {
      const label = heading.textContent?.trim() ?? '';
      if (!label) {
        return;
      }
      const level = Number.parseInt(heading.tagName.slice(1), 10);
      while (parents.length > 0 && parents[parents.length - 1].level >= level) {
        parents.pop();
      }

      let targetList = list;
      const parent = parents[parents.length - 1];
      if (parent) {
        if (!parent.childList) {
          parent.childList = document.createElement('ul');
          parent.childList.classList.add('scroll-text-navigation-list', 'scroll-text-navigation-sublist');
          parent.item.append(parent.childList);
        }
        targetList = parent.childList;
      }

      const item = this.createTextNavigationItem(
        label,
        null,
        () => this.scrollElementIntoView(heading),
      );
      targetList.append(item);
      parents.push({ level, item });
    });

    list.append(this.createTextNavigationItem(
      target.dataset.scrollBtnBottomLabel ?? '',
      'fa-arrow-down',
      () => this.scrollElementIntoView(target),
    ));
    menu.replaceChildren(list);
  }

  private createTextNavigationItem(
    label: string,
    iconClass: string | null,
    onClick: () => void,
  ): HTMLLIElement {
    const item = document.createElement('li');
    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add('scroll-text-navigation-link');
    button.title = label;
    button.addEventListener('click', onClick);

    if (iconClass) {
      const icon = document.createElement('i');
      icon.classList.add('fas', 'fa-fw', iconClass);
      icon.setAttribute('aria-hidden', 'true');
      button.append(icon);
    }

    const text = document.createElement('span');
    text.classList.add('scroll-text-navigation-label');
    text.textContent = label;
    button.append(text);
    item.append(button);
    return item;
  }

  private scrollElementIntoView(target: HTMLElement): void {
    const frame = target.ownerDocument.defaultView?.frameElement;
    const editorWindow = target.ownerDocument.defaultView;
    let targetTop = target.getBoundingClientRect().top + window.scrollY;
    if (frame instanceof HTMLIFrameElement) {
      targetTop = frame.getBoundingClientRect().top
        + window.scrollY
        + target.getBoundingClientRect().top
        + (editorWindow?.scrollY ?? 0);
    }

    const navbarHeight = document.querySelector<HTMLElement>('.sticky-navbar')?.offsetHeight ?? 0;
    const toolbarHeight = document.getElementById('entityToolbar')?.offsetHeight ?? 0;
    const editorHeaderHeight = frame instanceof HTMLIFrameElement
      ? frame.closest('.tox-tinymce')?.querySelector<HTMLElement>('.tox-editor-header')?.offsetHeight ?? 0
      : 0;
    const rootFontSize = Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
    const offset = navbarHeight + toolbarHeight + editorHeaderHeight + rootFontSize;

    window.scrollTo({
      top: targetTop - offset,
      behavior: 'smooth',
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
