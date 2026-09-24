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
    let closeTimer: number | undefined;
    const cancelClose = (): void => {
      window.clearTimeout(closeTimer);
      closeTimer = undefined;
    };

    wrapper.addEventListener('pointerenter', () => {
      cancelClose();
      refresh();
      setOpen(true);
    });
    wrapper.addEventListener('pointerleave', () => {
      if (!wrapper.contains(document.activeElement)) {
        cancelClose();
        closeTimer = window.setTimeout(() => setOpen(false), 250);
      }
    });
    trigger.addEventListener('focus', () => {
      cancelClose();
      refresh();
      setOpen(true);
    });
    wrapper.addEventListener('focusout', event => {
      const nextTarget = event.relatedTarget;
      if (!(nextTarget instanceof Node) || !wrapper.contains(nextTarget)) {
        cancelClose();
        setOpen(false);
      }
    });

    wrapper.replaceChildren(menu, trigger);
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
    const extraOffset = Number.parseInt(target.dataset.scrollBtnY ?? '0', 10);
    const scrollOffset = Number.isFinite(extraOffset) ? extraOffset : 0;

    list.append(this.createTextNavigationItem(
      target.dataset.scrollBtnTopLabel ?? '',
      'fa-arrow-up',
      () => this.scrollElementIntoView(topTarget, scrollOffset),
    ));

    const headings = Array.from(headingsRoot.querySelectorAll<HTMLElement>('h1, h2, h3'));
    let currentList = list;
    let currentLevel = 0;
    let lastItem: HTMLLIElement | null = null;
    const listStack: Array<{level: number; list: HTMLUListElement}> = [];

    headings.forEach(heading => {
      const label = heading.textContent?.trim() ?? '';
      if (!label) {
        return;
      }
      const level = Number.parseInt(heading.tagName.slice(1), 10);
      if (listStack.length === 0) {
        listStack.push({ level, list });
      } else if (level > currentLevel && lastItem) {
        const sublist = document.createElement('ul');
        sublist.classList.add('scroll-text-navigation-list', 'scroll-text-navigation-sublist');
        lastItem.append(sublist);
        listStack.push({ level, list: sublist });
      } else if (level < currentLevel) {
        while (listStack.length > 1 && level < listStack[listStack.length - 1].level) {
          listStack.pop();
        }
      }

      currentList = listStack[listStack.length - 1].list;
      lastItem = this.createTextNavigationItem(
        label,
        null,
        () => this.scrollElementIntoView(heading, scrollOffset),
      );
      currentList.append(lastItem);
      currentLevel = level;
    });

    list.append(this.createTextNavigationItem(
      target.dataset.scrollBtnBottomLabel ?? '',
      'fa-arrow-down',
      () => target.scrollIntoView({ behavior: 'smooth', block: 'start' }),
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

  private scrollElementIntoView(target: HTMLElement, extraOffset: number): void {
    const frame = target.ownerDocument.defaultView?.frameElement;
    const editorWindow = target.ownerDocument.defaultView;
    let targetTop = target.getBoundingClientRect().top + window.scrollY;
    if (frame instanceof HTMLIFrameElement) {
      targetTop = frame.getBoundingClientRect().top
        + window.scrollY
        + target.getBoundingClientRect().top
        + (editorWindow?.scrollY ?? 0);
    }
    const styles = getComputedStyle(document.documentElement);
    const navbarHeight = Number.parseFloat(styles.getPropertyValue('--navbar-height')) || 0;
    const toolbarHeight = Number.parseFloat(styles.getPropertyValue('--toolbar-height')) || 0;
    const rootFontSize = Number.parseFloat(styles.fontSize) || 16;

    window.scrollTo({
      top: targetTop - navbarHeight - toolbarHeight - rootFontSize - extraOffset,
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
