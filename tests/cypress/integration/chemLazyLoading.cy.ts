import type { Ketcher } from 'ketcher-core';

describe('Chemistry on the edit page', () => {
  beforeEach(() => {
    cy.login();
  });

  it('only loads the chemical editor and the compounds when they are used', () => {
    cy.intercept('GET', '/api/v2/compounds*').as('compounds');
    cy.request('POST', '/api/v2/experiments', { title: 'Chemistry loading test' })
      .then(response => cy.extractIdFromLocation(response))
      .then(id => cy.visit(`/experiments.php?mode=edit&id=${id}`));
    cy.get('[data-cy="body-tab-main"]').should('have.attr', 'aria-selected', 'true');
    // the editor is not loaded with the text tab, so its buttons stay disabled
    cy.get('[data-cy="chem-create-reaction"]').should('be.disabled');

    cy.get('[data-cy="body-tab-chem"]').click();
    cy.get('[data-cy="chem-create-reaction"]', { timeout: 60000 }).should('be.enabled');
    // loaded, the editor still does not read the compounds
    cy.get('@compounds.all').should('have.length', 0);

    // a structure search does, with the structure
    cy.window().its('ketcher').then((ketcher: Ketcher) => ketcher.setMolecule('c1ccccc1'));
    cy.get('[data-action="search-from-editor"]').click();
    cy.wait('@compounds').its('request.url').should('include', 'search_fp_smi=');
    cy.get('#searchFpResultsDiv').should('be.visible');
  });
});
