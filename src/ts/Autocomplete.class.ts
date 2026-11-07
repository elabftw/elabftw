/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

export interface AutocompleteItem {
  label: string;
  // Defaults to label. Existing callers keep their "id - title" values.
  value?: string;
  disabled?: boolean;
}

export type AutocompleteResult = string | AutocompleteItem;

const segmenter = new Intl.Segmenter(undefined, { granularity: 'grapheme' });
export const countGraphemes = (text: string): number => Array.from(segmenter.segment(text)).length;

export interface AutocompleteOptions {
  source: (term: string) => AutocompleteResult[] | Promise<AutocompleteResult[]>;
  appendTo?: HTMLElement | null;
  minLength?: number;
  delay?: number;
  // Changing a filter also invalidates any pending search and visible results.
  dependencies?: HTMLElement[];
  onSelect?: (item: AutocompleteItem, input: HTMLInputElement) => void;
}

/**
 * An editable combobox with a listbox popup. Labels are always plain text.
 * Use attach() in initialization helpers: calling it again reuses the instance.
 * Selection emits native input/change events, then calls onSelect if provided.
 */
export default class Autocomplete implements EventListenerObject {
  private static instances = new Map<HTMLInputElement, Autocomplete>();
  private static observer: MutationObserver | undefined;
  private static nextId = 0;

  static get(input: HTMLInputElement): Autocomplete | undefined {
    return this.instances.get(input);
  }

  static attach(input: HTMLInputElement, options: AutocompleteOptions): Autocomplete {
    return this.get(input) ?? new Autocomplete(input, options);
  }

  private readonly document: Document;
  private readonly view: Window & typeof globalThis;
  private readonly menu: HTMLUListElement;
  private readonly listeners: AbortController;
  private readonly originalAttributes = new Map<string, string | null>();
  private popupListeners: AbortController | undefined;
  private timer: number | undefined;
  private requestId = 0;
  private items: AutocompleteItem[] = [];
  private activeIndex = -1;
  private composing = false;
  private selecting = false;
  private committedValue: string | undefined;
  private destroyed = false;

  constructor(private readonly input: HTMLInputElement, private readonly options: AutocompleteOptions) {
    Autocomplete.get(input)?.destroy();
    this.document = input.ownerDocument;
    if (!this.document.defaultView) throw new Error('Autocomplete input must belong to a browser document.');
    this.view = this.document.defaultView;
    this.listeners = new this.view.AbortController();
    this.menu = this.document.createElement('ul');
    this.menu.id = `elab-autocomplete-${++Autocomplete.nextId}`;
    this.menu.className = 'elab-autocomplete';
    this.menu.setAttribute('role', 'listbox');
    this.menu.hidden = true;

    const attributes = {
      autocomplete: 'off',
      role: 'combobox',
      'aria-autocomplete': 'list',
      'aria-haspopup': 'listbox',
      'aria-controls': this.menu.id,
      'aria-expanded': 'false',
      'aria-busy': 'false',
      'aria-activedescendant': null,
    };
    for (const [name, value] of Object.entries(attributes)) {
      this.originalAttributes.set(name, input.getAttribute(name));
      if (value === null) input.removeAttribute(name);
      else input.setAttribute(name, value);
    }

    const { signal } = this.listeners;
    for (const type of ['input', 'keydown', 'blur', 'compositionstart', 'compositionend']) {
      input.addEventListener(type, this, { signal });
    }
    input.addEventListener('change', this, { capture: true, signal });
    for (const type of ['pointerdown', 'pointermove', 'click']) {
      this.menu.addEventListener(type, this, { signal });
    }
    for (const dependency of options.dependencies ?? []) {
      dependency.addEventListener('change', () => this.close(), { signal });
    }
    // aria-controls must refer to an existing element even before the first search.
    this.getPopupParent().append(this.menu);

    Autocomplete.instances.set(input, this);
    // Fragment reloads remove inputs without calling destroy(). Clean up their
    // popups and listeners instead of retaining detached elements indefinitely.
    if (!Autocomplete.observer) {
      Autocomplete.observer = new this.view.MutationObserver(() => {
        for (const instance of Autocomplete.instances.values()) {
          if (!instance.input.isConnected) instance.destroy();
        }
      });
      Autocomplete.observer.observe(this.document.body, { childList: true, subtree: true });
    }
  }

  handleEvent(event: Event): void {
    switch (event.type) {
    case 'input':
      if (!this.selecting) {
        this.committedValue = undefined;
        if (!this.composing) this.scheduleSearch();
      }
      break;
    case 'change':
      // Selection already emitted change. Ignore duplicate blur notifications,
      // including synthetic events, while letting select() emit its own change.
      if (!this.selecting && this.committedValue === this.input.value) event.stopImmediatePropagation();
      break;
    case 'compositionstart':
      this.composing = true;
      this.close();
      break;
    case 'compositionend':
      this.composing = false;
      this.scheduleSearch();
      break;
    case 'keydown':
      this.handleKeydown(event as KeyboardEvent);
      break;
    case 'blur':
      this.committedValue = undefined;
      this.close();
      break;
    case 'pointerdown':
      // Keep focus on the input so its blur handlers cannot save the old value.
      if ((event as PointerEvent).button === 0) event.preventDefault();
      break;
    case 'pointermove':
    case 'click': {
      const option = (event.target as HTMLElement).closest<HTMLElement>('[data-index]');
      if (!option || !this.menu.contains(option)) break;
      const index = Number(option.dataset.index);
      if (event.type === 'click') this.select(index);
      else this.setActive(index);
      break;
    }
    }
  }

  private get canSearch(): boolean {
    return !this.destroyed && !this.composing && !this.input.disabled && !this.input.readOnly
      && this.input.isConnected && this.document.activeElement === this.input;
  }

  private scheduleSearch(): void {
    this.close();
    if (!this.canSearch) return;
    this.timer = this.view.setTimeout(() => {
      this.timer = undefined;
      void this.search();
    }, this.options.delay ?? 300);
  }

  async search(direction: -1 | 0 | 1 = 0): Promise<void> {
    this.close();
    const term = this.input.value;
    if (!this.canSearch || countGraphemes(term) < (this.options.minLength ?? 1)) return;

    const requestId = this.requestId;
    this.input.setAttribute('aria-busy', 'true');
    try {
      const results = await this.options.source(term);
      if (requestId !== this.requestId) return;
      this.input.setAttribute('aria-busy', 'false');
      if (!this.canSearch || this.input.value !== term || results.length === 0) return;
      this.items = results.map(result => typeof result === 'string' ? { label: result } : result);
      this.render();
      // Apply arrow navigation only to the search that produced these results.
      if (direction) this.moveActive(direction);
    } catch {
      // API sources already report errors through ApiC. Consume the rejection
      // here and only close the popup if this was still the current search.
      if (requestId === this.requestId) this.close();
    }
  }

  private render(): void {
    const fragment = this.document.createDocumentFragment();
    this.items.forEach((item, index) => {
      const option = this.document.createElement('li');
      option.id = `${this.menu.id}-${index}`;
      option.className = 'elab-autocomplete-option';
      option.dataset.index = String(index);
      option.setAttribute('role', 'option');
      option.setAttribute('aria-selected', 'false');
      if (item.disabled) option.setAttribute('aria-disabled', 'true');
      option.textContent = item.label;
      fragment.append(option);
    });
    this.menu.replaceChildren(fragment);
    // Keeping the popup inside a modal also keeps it inside its focus boundary.
    this.getPopupParent().append(this.menu);
    this.menu.hidden = false;
    this.input.setAttribute('aria-expanded', 'true');
    this.position();
    if (this.menu.hidden) return;

    this.popupListeners = new this.view.AbortController();
    const { signal } = this.popupListeners;
    this.document.addEventListener('pointerdown', event => {
      if (event.target !== this.input && !this.menu.contains(event.target as Node)) this.close();
    }, { signal });
    this.document.addEventListener('scroll', () => this.position(), { capture: true, signal });
    this.view.addEventListener('resize', () => this.position(), { signal });
  }

  private getPopupParent(): HTMLElement {
    return this.options.appendTo ?? this.input.closest<HTMLElement>('.modal') ?? this.document.body;
  }

  private position(): void {
    const rect = this.input.getBoundingClientRect();
    if (!rect.width || !rect.height) {
      this.close();
      return;
    }
    this.menu.style.width = `${rect.width}px`;
    const below = this.view.innerHeight - rect.bottom;
    const above = rect.top;
    const flip = below < Math.min(this.menu.scrollHeight, 240) && above > below;
    this.menu.style.maxHeight = `${Math.max(0, Math.min(240, (flip ? above : below) - 8))}px`;

    const parent = this.menu.offsetParent as HTMLElement | null;
    let left = rect.left + this.view.scrollX;
    let top = (flip ? rect.top - this.menu.offsetHeight : rect.bottom) + this.view.scrollY;
    if (parent && (parent !== this.document.body || this.view.getComputedStyle(parent).position !== 'static')) {
      const parentRect = parent.getBoundingClientRect();
      left += parent.scrollLeft - parentRect.left - parent.clientLeft - this.view.scrollX;
      top += parent.scrollTop - parentRect.top - parent.clientTop - this.view.scrollY;
    }
    this.menu.style.left = `${left}px`;
    this.menu.style.top = `${top}px`;
  }

  private handleKeydown(event: KeyboardEvent): void {
    if (this.composing || event.isComposing) return;
    switch (event.key) {
    case 'ArrowDown':
    case 'ArrowUp': {
      event.preventDefault();
      const direction = event.key === 'ArrowDown' ? 1 : -1;
      if (this.menu.hidden) void this.search(direction);
      else this.moveActive(direction);
      break;
    }
    case 'Enter':
      if (!this.menu.hidden && this.activeIndex !== -1) {
        event.preventDefault();
        this.select(this.activeIndex);
      }
      break;
    case 'Tab':
      if (!this.menu.hidden && this.activeIndex !== -1) this.select(this.activeIndex);
      this.close();
      break;
    case 'Escape':
      if (!this.menu.hidden) {
        event.preventDefault();
        // Escape closes the suggestions before dismissing a containing modal.
        event.stopPropagation();
      }
      this.close();
      break;
    }
  }

  private moveActive(direction: number): void {
    if (this.menu.hidden) return;
    let index = this.activeIndex;
    do {
      index += direction;
      if (index < -1) index = this.items.length - 1;
      if (index >= this.items.length) index = -1;
    } while (index !== -1 && this.items[index].disabled);
    this.setActive(index);
    if (index !== -1) this.menu.children[index].scrollIntoView({ block: 'nearest' });
  }

  private setActive(index: number): void {
    if (index !== -1 && this.items[index]?.disabled) return;
    if (this.activeIndex !== -1) this.menu.children[this.activeIndex]?.setAttribute('aria-selected', 'false');
    this.activeIndex = index;
    if (index === -1) this.input.removeAttribute('aria-activedescendant');
    else {
      const option = this.menu.children[index];
      option.setAttribute('aria-selected', 'true');
      this.input.setAttribute('aria-activedescendant', option.id);
    }
  }

  private select(index: number): void {
    const item = this.items[index];
    if (!item || item.disabled || this.input.disabled || this.input.readOnly) return;
    this.close();
    this.selecting = true;
    try {
      this.input.value = item.value ?? item.label;
      this.committedValue = this.input.value;
      this.input.dispatchEvent(new this.view.Event('input', { bubbles: true }));
      this.input.dispatchEvent(new this.view.Event('change', { bubbles: true }));
    } finally {
      this.selecting = false;
    }
    this.options.onSelect?.(item, this.input);
  }

  close(): void {
    if (this.destroyed) return;
    this.view.clearTimeout(this.timer);
    this.timer = undefined;
    ++this.requestId;
    this.popupListeners?.abort();
    this.popupListeners = undefined;
    this.menu.hidden = true;
    this.menu.replaceChildren();
    this.items = [];
    this.activeIndex = -1;
    this.input.setAttribute('aria-expanded', 'false');
    this.input.setAttribute('aria-busy', 'false');
    this.input.removeAttribute('aria-activedescendant');
  }

  destroy(): void {
    if (this.destroyed) return;
    this.close();
    this.destroyed = true;
    this.listeners.abort();
    this.menu.remove();
    for (const [name, value] of this.originalAttributes) {
      if (value === null) this.input.removeAttribute(name);
      else this.input.setAttribute(name, value);
    }
    Autocomplete.instances.delete(this.input);
    if (Autocomplete.instances.size === 0) {
      Autocomplete.observer?.disconnect();
      Autocomplete.observer = undefined;
    }
  }
}
