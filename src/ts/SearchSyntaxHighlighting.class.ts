/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @author Marcel Bolten <github@marcelbolten.de>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import Prism from 'prismjs';
import './prism-elabftwquery';

export class SearchSyntaxHighlighting {

  static init(textarea: HTMLTextAreaElement): void {
    new ResizeObserver(SearchSyntaxHighlighting.syncSize).observe(textarea);

    SearchSyntaxHighlighting.update(textarea.value);

    // display element before syncScroll otherwise there is an offset
    document.getElementById('search-highlighting').classList.remove('d-none');
    SearchSyntaxHighlighting.syncScroll(textarea);

    // trigger syncSize via ResizeObserver
    textarea.style.height = String(textarea.offsetHeight) + 'px';

    textarea.addEventListener('input', (ev: InputEvent): void => {
      const textarea = ev.target as HTMLTextAreaElement;
      SearchSyntaxHighlighting.update(textarea.value);
      SearchSyntaxHighlighting.syncScroll(textarea);
    });

    textarea.addEventListener('scroll', (ev: Event): void => {
      SearchSyntaxHighlighting.syncScroll(ev.target as HTMLTextAreaElement);
    });

  }

  static update(text: string): void {
    const code = document.querySelector('#search-highlighting code');

    // Handle final newlines
    if (text.slice(-1) === '\n') {
      text += ' ';
    }

    code.textContent = text;
    code.innerHTML = Prism.highlight(
      code.textContent,
      Prism.languages.elabftwquery,
      'elabftwquery',
    );
  }

  static syncScroll(textarea: HTMLTextAreaElement): void {
    const pre = document.getElementById('search-highlighting');

    pre.scrollTop = textarea.scrollTop;
  }

  static syncSize(entries: ResizeObserverEntry[]): void {
    const textarea = entries[0].target as HTMLTextAreaElement;
    const pre = document.getElementById('search-highlighting');

    pre.style.height = String(textarea.style.height);
  }
}
