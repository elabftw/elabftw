describe('Search', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Searches an experiment using extended metadata syntax', () => {
    const fieldName = 'Raw data URL';
    // create new experiment
    cy.createEntity().then(() => {
      cy.get('#documentTitle').invoke('text').then((title) => {
        const trimmedTitle = title.trim();
        cy.addTextMetadataField(fieldName);
        // Perform extended search
        cy.visit('experiments.php');
        const query = `extrafield:"${fieldName}":%`;
        cy.get('#extendedArea').should('be.visible').type(`${query}{enter}`);
        cy.url().should('include', 'q=');
        // Assert the experiment is visible
        cy.get('#itemList').should('be.visible').contains(trimmedTitle).should('exist');
      });
    });
  });

  it('Searches an experiment with an unchecked and checked checkbox using extended metadata syntax', () => {
    const fieldName = 'Approved';

    // create experiment with unchecked checkbox
    cy.createEntity().then(() => {
      cy.get('#documentTitle').invoke('text').then((titleUnchecked) => {
        const trimmedTitleUnchecked = titleUnchecked.trim();
        cy.addMetadataField(fieldName, 'checkbox');

        // create second experiment with checked checkbox
        cy.createEntity().then(() => {
          cy.get('#documentTitle').invoke('text').then((titleChecked) => {
            const trimmedTitleChecked = titleChecked.trim();
            cy.addMetadataField(fieldName, 'checkbox');

            // check the checkbox and wait for the update and metadata reload to complete
            cy.intercept('PATCH', '**/api/v2/experiments/*').as('patchExp');
            cy.intercept('GET', '**/api/v2/experiments/*').as('reloadExp');
            cy.get(`input[type="checkbox"][data-field="${fieldName}"]`).click();
            cy.wait('@patchExp');
            cy.wait('@reloadExp');

            // 1. Search for empty/unchecked value
            cy.visit('experiments.php');
            const emptyQuery = `extrafield:"${fieldName}":""`;
            cy.get('#extendedArea').should('be.visible').type(`${emptyQuery}{enter}`);
            cy.url().should('include', 'q=');

            // Unchecked experiment should be visible, checked should not
            cy.get('#itemList').should('be.visible').contains(trimmedTitleUnchecked).should('exist');
            cy.get('#itemList').contains(trimmedTitleChecked).should('not.exist');

            // 2. Search for checked value ("on")
            cy.visit('experiments.php');
            const checkedQuery = `extrafield:"${fieldName}":on`;
            cy.get('#extendedArea').should('be.visible').type(`${checkedQuery}{enter}`);
            cy.url().should('include', 'q=');

            // Checked experiment should be visible, unchecked should not
            cy.get('#itemList').should('be.visible').contains(trimmedTitleChecked).should('exist');
            cy.get('#itemList').contains(trimmedTitleUnchecked).should('not.exist');
          });
        });
      });
    });
  });
});
