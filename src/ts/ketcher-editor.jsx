/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2024 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
//import { StrictMode, useEffect } from 'react';
import { createRoot } from 'react-dom/client'
import { Api } from './Apiv2.class'
//import { Ketcher, ketcherProvider } from 'ketcher-core';
import KetcherEditor from './ketcher';


/*
const RootComponent = () => {
  useEffect(() => {
        const checkForToolbar = setInterval(() => {
      const toolbar = document.querySelector('[data-testid="top-toolbar"]');
      if (toolbar) {
        console.log('Toolbar found:', toolbar);
        // Create a new button element
        const customButton = document.createElement('button');
        customButton.innerHTML = 'Custom';
        customButton.className = 'custom-button'; // Add a class for styling

        // Define the action when the button is clicked
        customButton.addEventListener('click', () => {
            console.log('Custom button clicked!');
            // Add any custom logic you want to trigger on click
        });

        // Append the custom button to the toolbar
        toolbar.appendChild(customButton);
  const ketcher = ketcherProvider.getKetcher();
  window.ketcher = ketcherProvider.getKetcher();
        clearInterval(checkForToolbar);
        // Your logic here
      }
    }, 100); // Check every 100ms

    return () => clearInterval(checkForToolbar); // Cleanup on unmount
  }, []);

  return <KetcherEditor />;
};
*/

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('ketcher-root')) {
    const root = createRoot(document.getElementById('ketcher-root'));
    // Note: use <StrictMode> in dev to spot errors
    root.render(
      <KetcherEditor />
    );
    /*
    root.render(
    <StrictMode>
      <KetcherEditor />
    </StrictMode>
    );
    */
    document.getElementById('ketcher-actions').addEventListener('click', async (event) => {
      const el = event.target;
      if (el.matches('[data-action="search-from-editor"]')) {
        //const smiles = async () => {await ketcher.getSmiles()};
        window.ketcher.getSmiles().then(s => {
          const ApiC = new Api();
          const json = ApiC.getJson(`compounds?search_fp_smi=${encodeURIComponent(s)}`);
          console.log(json);
        });
      } else if (el.matches('[data-action="create-item-from-editor"]')) {
        const inchi = await ketcher.getInchi();
        console.log(inchi);
        console.log('clicked create item');
        const ApiC = new Api();
        ApiC.post('/items/', {template: 1, body: inchi});
      }
    });
  }
});

    /*
    (async () => {
      try {
        const [inchi, smiles, mol] = await Promise.all([ketcher.getSmiles(), ketcher.getInchi(), ketcher.getMolfile()]);
        console.log('Smiles: ', smiles);
        console.log('Mol: ', mol);
        console.log('InChI: ', inchi);
      } catch (error) {
        console.error('Error:', error);
      }
    })();
    */
/*
  }
});

}
*/
