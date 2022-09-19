/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
export class Ajax {
  postForm(controller: string, params: Record<string, string|Blob>): Promise<Response> {
    const formData = new FormData();
    formData.append('csrf', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    for (const [key, value] of Object.entries(params)) {
      formData.append(key, value);
    }
    return fetch(controller, {
      method: 'POST',
      body: formData,
    });
    // don't response.json() here as we don't always get json back
  }
}
