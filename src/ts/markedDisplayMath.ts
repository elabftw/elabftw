/**
 * @author Nicolas CARPi / Deltablot
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import type { MarkedExtension, Tokens } from 'marked';

interface DisplayMathToken extends Tokens.Generic {
  type: 'displayMath';
  text: string;
}

const openingLine = /^ {0,3}(\$\$|\\\[)[ \t]*(?:\r?\n|$)/;
const dollarClosingLine = /^ {0,3}\$\$[ \t]*$/;
const bracketClosingLine = /^ {0,3}\\\][ \t]*$/;

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

export const displayMathExtension: MarkedExtension = {
  extensions: [{
    name: 'displayMath',
    level: 'block',
    start(src): number | undefined {
      return /^ {0,3}(?:\$\$|\\\[)[ \t]*(?=\r?$)/m.exec(src)?.index;
    },
    tokenizer(src): DisplayMathToken | undefined {
      const opening = openingLine.exec(src);
      if (!opening) {
        return undefined;
      }
      const closingLine = opening[1] === '$$' ? dollarClosingLine : bracketClosingLine;
      let lineStart = opening[0].length;
      while (lineStart < src.length) {
        const nextLineBreak = src.indexOf('\n', lineStart);
        const lineEnd = nextLineBreak === -1 ? src.length : nextLineBreak;
        const line = src.slice(lineStart, lineEnd).replace(/\r$/, '');
        if (closingLine.test(line)) {
          const rawEnd = nextLineBreak === -1 ? lineEnd : nextLineBreak + 1;
          const raw = src.slice(0, rawEnd);
          return { type: 'displayMath', raw, text: raw.replace(/\r?\n$/, '') };
        }
        if (nextLineBreak === -1) {
          break;
        }
        lineStart = nextLineBreak + 1;
      }
      return { type: 'displayMath', raw: src, text: src.replace(/\r?\n$/, '') };
    },
    renderer(token): string {
      return `<div>${escapeHtml((token as DisplayMathToken).text)}</div>\n`;
    },
  }],
};
