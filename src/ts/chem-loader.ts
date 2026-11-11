/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { on } from './handlers';

/**
 * The chemical editor of the edit page is in a tab that most users never open: Ketcher, the reactions and the compounds search
 * are only loaded when it is shown, or when an attachment is loaded in it
 */
let chemistry: Promise<typeof import('./chem-editor')> | null = null;

export function loadChemistry(): Promise<typeof import('./chem-editor')> {
  return chemistry ??= import(
    /* webpackChunkName: 'chem-editor' */
    './chem-editor'
  );
}

const isChemistryShown = (): boolean => document.getElementById('chemTab')?.getAttribute('aria-selected') === 'true'
  && !document.getElementById('entityBodyDiv')?.classList.contains('entity-body-pane-inactive');

export function initChemistryLoader(): void {
  // see tabbed-sections.ts: also dispatched when the section is expanded on this tab, or the remembered tab is restored
  document.addEventListener('tab-shown', (event: CustomEvent<string>) => {
    if (event.detail === 'chem') {
      void loadChemistry();
    }
  });
  // the remembered tab can have been restored before this listener
  if (isChemistryShown()) {
    void loadChemistry();
  }
  // from the attachments, which are outside of the tab
  on('chem-load-file', async (el: HTMLElement) => {
    const { loadAttachmentFromList } = await loadChemistry();
    await loadAttachmentFromList(el.dataset.uploadid, el.dataset.name);
  });
}
