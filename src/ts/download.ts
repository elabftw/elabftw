/** Download response helpers. License: AGPL-3.0-or-later. */

// An invalid or missing header must never prevent downloading the response body.
export function downloadFilename(disposition: string | null, fallback: string): string {
  if (!disposition) return fallback;
  const extended = disposition.match(/(?:^|;)\s*filename\*\s*=\s*UTF-8'[^']*'([^;]*)/i);
  let filename: string | undefined;
  if (extended) {
    try {
      filename = decodeURIComponent(extended[1].trim());
    } catch {
      // Fall back to the ordinary filename when the extended value is malformed.
    }
  }
  if (!filename) {
    const ordinary = disposition.match(/(?:^|;)\s*filename\s*=\s*(?:"((?:[^"\\]|\\.)*)"|([^;]*))/i);
    filename = ordinary?.[1]?.replace(/\\(.)/g, '$1') ?? ordinary?.[2]?.trim();
  }
  if (!filename) return fallback;
  // Treat filenames as basenames, even when sent by an incorrectly configured server.
  filename = filename.split(/[/\\]/).pop()?.replace(/\p{Cc}/gu, '').trim();
  return filename && filename !== '.' && filename !== '..' ? filename : fallback;
}

export function downloadBlob(blob: Blob, filename: string): void {
  const url = window.URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  try {
    anchor.href = url;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
  } finally {
    anchor.remove();
    // Give the browser a task to consume the URL before releasing the object.
    window.setTimeout(() => window.URL.revokeObjectURL(url), 0);
  }
}
