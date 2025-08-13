/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
export type Handler = (el: HTMLElement, e: Event) => void;

const handlers = new Map<string, Set<Handler>>();

export function on(action: string, fn: Handler) {
  let set = handlers.get(action);
  if (!set) handlers.set(action, (set = new Set()));
  if (action === 'toggle-modal') {
    console.debug(`adding function for action: ${action}`);
  }
  set.add(fn);
}
export function off(action: string, fn: Handler) {
  handlers.get(action)?.delete(fn);
}
export function get(action: string) {
  console.debug(`getting function for action: ${action}`);
  return handlers.get(action);
}

export default handlers;
