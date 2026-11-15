/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2026 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
import { on } from './handlers';
import { errorMessage } from './misc';
import { notify } from './notify';

/**
 * The chemical editor of the edit page is in a tab that most users never open: Ketcher, the reactions and the compounds search
 * are only loaded when it is shown, or when an attachment is loaded in it
 */
let chemistry: Promise<typeof import('./chem-editor')> | null = null;

// Leaving the page while Ketcher downloads (several MB) aborts the download, and Firefox then fails the import: that is not an error
let leavingPage = false;

export function loadChemistry(): Promise<typeof import('./chem-editor')> {
  return chemistry ??= import(
    /* webpackChunkName: 'chem-editor' */
    './chem-editor'
  ).catch((error: unknown) => {
    // a failed download is not kept: the next time the tab is shown loads it again
    chemistry = null;
    throw error;
  });
}

// every caller handles the failure, or it becomes an unhandled rejection
function reportLoadError(error: unknown): void {
  if (!leavingPage) {
    notify.error(errorMessage(error));
  }
}

const isChemistryShown = (): boolean => document.getElementById('chemTab')?.getAttribute('aria-selected') === 'true'
  && !document.getElementById('entityBodyDiv')?.classList.contains('entity-body-pane-inactive');

export function initChemistryLoader(): void {
  window.addEventListener('beforeunload', () => { leavingPage = true; });
  // back on the page from the back-forward cache, or the user chose to stay
  window.addEventListener('pageshow', () => { leavingPage = false; });
  window.addEventListener('focus', () => { leavingPage = false; });
  // see tabbed-sections.ts: also dispatched when the section is expanded on this tab, or the remembered tab is restored
  document.addEventListener('tab-shown', (event: CustomEvent<string>) => {
    if (event.detail === 'chem') {
      loadChemistry().catch(reportLoadError);
    }
  });
  // the remembered tab can have been restored before this listener
  if (isChemistryShown()) {
    loadChemistry().catch(reportLoadError);
  }
  // from the attachments, which are outside of the tab
  on('chem-load-file', async (el: HTMLElement) => {
    try {
      const { loadAttachmentFromList } = await loadChemistry();
      await loadAttachmentFromList(el.dataset.uploadid, el.dataset.name);
    } catch (error) {
      reportLoadError(error);
    }
  });
}
