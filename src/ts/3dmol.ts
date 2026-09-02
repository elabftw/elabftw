/**
 * @author Nicolas CARPi @ Deltablot
 * @copyright 2025 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

export async function displayMoleculeViewer(): Promise<void> {
  const elements = document.querySelectorAll<HTMLElement>(
    '.viewer_3Dmoljs:not([data-initialized])',
  );

  if (elements.length === 0) {
    return;
  }

  const $3Dmol = await get3dmol();

  await Promise.all(Array.from(elements).map(async element => {
    element.dataset.initialized = '1';

    const response = await fetch(element.dataset.href);
    const molecule = await response.text();

    const viewer = $3Dmol.createViewer(element, {
      backgroundColor: 'white',
    });

    $3Dmol.viewers[element.id] = viewer;

    const extension = new URL(
      element.dataset.href,
      window.location.href,
    ).searchParams.get('name')?.split('.').pop();

    viewer.addModel(molecule, extension);

    viewer.setStyle(
      {},
      extension === 'pdb'
        ? { cartoon: { color: 'spectrum' } }
        : { stick: {} },
    );

    viewer.zoomTo();
    viewer.render();
  }));
}

export function get3dmol()
{
  return import('3dmol');
}
