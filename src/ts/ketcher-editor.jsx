// import { StrictMode } from 'react';
 import { useEffect } from 'react';
import { createRoot } from 'react-dom/client'
import KetcherEditor from './ketcher';
//import { StandaloneStructServiceProvider, FormatterFactory } from 'ketcher-standalone';
import { Ketcher, ketcherProvider } from 'ketcher-core';
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

if (document.getElementById('ketcher-root')) {
  const root = createRoot(document.getElementById('ketcher-root'));
  // Note: <StrictMode> makes everything fail for now, so it has been removed
  root.render(
      <RootComponent />
  );
  /*
  ReactDOM.render(
    <React.StrictMode>
      <KetcherEditor />
    </React.StrictMode>,
    document.getElementById('ketcher-root')
  );
  */
}
document.getElementById('container').addEventListener('click', event => {
  const el = event.target;
  /*
const structServiceProvider = new StandaloneStructServiceProvider();
const formatterFactory = new FormatterFactory(structServiceProvider);
*/
  if (el.matches('[data-action="search-from-editor"]')) {
    console.log('clicked');
    const ketcher = ketcherProvider.getKetcher();
    //const smiles = async () => {await ketcher.getSmiles()};
    ketcher.getInchi().then(s => console.log(s));
    //console.log(smiles());
    /*
    const getInchi = async () => {
      return ketcher.getInchi();
    };
    const getSmiles = async() => {
      return ketcher.getMolfile();
    }
    */
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
  }
});

