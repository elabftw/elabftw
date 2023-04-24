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

  static init(input: HTMLInputElement): void {
    new ResizeObserver(SearchSyntaxHighlighting.syncSize).observe(input);

    SearchSyntaxHighlighting.update(input.value);

    // display element before syncScroll otherwise there is an offset
    document.getElementById('search-highlighting').classList.remove('d-none');
    SearchSyntaxHighlighting.syncScroll(input);

    // trigger syncSize via ResizeObserver
    input.style.height = String(input.offsetHeight) + 'px';

    input.addEventListener('input', (ev: InputEvent): void => {
      const input = ev.target as HTMLInputElement;
      SearchSyntaxHighlighting.update(input.value);
      SearchSyntaxHighlighting.syncScroll(input);
    });

    input.addEventListener('scroll', (ev: Event): void => {
      SearchSyntaxHighlighting.syncScroll(ev.target as HTMLInputElement);
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

  static syncScroll(input: HTMLInputElement): void {
    const pre = document.getElementById('search-highlighting');

    pre.scrollTop = input.scrollTop;
  }

  static syncSize(entries: ResizeObserverEntry[]): void {
    const input = entries[0].target as HTMLInputElement;
    const pre = document.getElementById('search-highlighting');

    pre.style.height = String(input.style.height);
  }
}
