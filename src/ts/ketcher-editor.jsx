//import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client'
//import { Ketcher, ketcherProvider } from 'ketcher-core';
import KetcherEditor from './ketcher';

/*
const RootComponent = () => {
  return <KetcherEditor />;
};
*/

document.addEventListener('DOMContentLoaded', () => {
if (document.getElementById('ketcher-root')) {

  const root = createRoot(document.getElementById('ketcher-root'));
  // Note: <StrictMode> makes everything fail for now, so it has been removed
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
}
});
/*
document.getElementById('container').addEventListener('click', event => {
  const el = event.target;
  if (el.matches('[data-action="search-from-editor"]')) {
    console.log('clicked');
    const ketcher = ketcherProvider.getKetcher();
    //const smiles = async () => {await ketcher.getSmiles()};
    ketcher.getSmiles().then(s => console.log(s));
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
/*
  }
});

*/
