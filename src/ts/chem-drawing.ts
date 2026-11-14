/**
 * @copyright 2026 Nicolas CARPi
 * @license AGPL-3.0
 * @package elabftw
 */
import { KetcherAsyncEvents } from 'ketcher-core';
import type { Ketcher } from 'ketcher-core';

/** Replace the drawing only after confirmation, restoring it if import fails. */
export async function loadChemDrawing(ketcher: Ketcher, content: string, messages: { confirm: string; error: string }): Promise<boolean> {
  // KET preserves molecules, macromolecules and drawing objects.
  const previous = await ketcher.getKet();
  if (JSON.parse(previous).root.nodes.length && !confirm(messages.confirm)) return false;
  // setMolecule swallows import errors; observe its failure event instead.
  let failed = false;
  const onFailure = () => failed = true;
  ketcher.eventBus.once(KetcherAsyncEvents.FAILURE, onFailure);
  try {
    await ketcher.setMolecule(content);
  } finally {
    ketcher.eventBus.off(KetcherAsyncEvents.FAILURE, onFailure);
  }
  if (failed) {
    // In macromolecule mode Ketcher clears the canvas before parsing.
    await ketcher.setMolecule(previous);
    throw new Error(messages.error);
  }
  return true;
}
