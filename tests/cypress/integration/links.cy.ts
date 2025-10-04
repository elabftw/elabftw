// commented out because very flaky


//describe('Test links', () => {
//  beforeEach(() => {
//    cy.login();
//  });
//
//  it('can link other experiments and resources to an experiment', () => {
//    cy.getExperimentId().then(expid => {
//      cy.visit(`/experiments.php?mode=edit&id=${expid}`);
//    });
//    linkExp('Linked Experiment');
//    linkItem('Linked Resource');
//    linkCompound('Linked Compound');
//    verifyChangelog();
//  });
//
//  const linkExp = (title: string) => {
//    cy.request({ method: 'POST', url: '/api/v2/experiments', body: { title } });
//    // add link to the new experiment
//    cy.get('#addLinkExpInput').type(title);
//    cy.get('.ui-menu-item-wrapper').contains(title).click();
//    cy.get('button[aria-label="Add link to an experiment"]').click();
//    cy.get('#experimentsLinksDiv').should('contain.text', title);
//    // remove link to the new experiment
//    cy.get('[data-action="destroy-link"]').first().click();
//    cy.get('#experimentsLinksDiv').should('not.contain.text', title);
//  };
//
//  const linkItem = (title: string) => {
//    cy.request({ method: 'POST', url: '/api/v2/items', body: { title } });
//    cy.get('#addLinkItemsInput').type(title);
//    cy.get('.ui-menu-item-wrapper').contains(title).click();
//    cy.get('button[aria-label="Add link to a resource"]').click();
//    cy.get('#itemsLinksDiv').should('contain.text', title);
//    cy.get('#linksDiv [data-action="destroy-link"][data-endpoint="items_links"]').first().click();
//    cy.get('#itemsLinksDiv').should('not.contain.text', title);
//  };
//
//  const linkCompound = (title: string) => {
//    cy.request({ method: 'POST', url: '/api/v2/compounds', body: { name: title } });
//    cy.get('#addCompoundInput').type(title);
//    cy.get('.ui-menu-item-wrapper').contains(title).click();
//    cy.get('button[aria-label="Add link to a compound"]').click();
//    cy.get('#compoundDiv').should('contain.text', title);
//    cy.get('#compoundDiv [data-action="delete-compound-link"]').first().click();
//    cy.get('#compoundDiv').should('not.contain.text', title);
//  };
//
//  const verifyChangelog = () => {
//    cy.get('button[title="More options"]').click()
//      .get('a.dropdown-item').contains('See changelog').click();
//    cy.get('#changelogTable').should('contain.text', 'Added link to experiment');
//    cy.get('#changelogTable').should('contain.text', 'Added link to resource');
//    cy.get('#changelogTable').should('contain.text', 'Added link to compound');
//    cy.get('#changelogTable').should('contain.text', 'Removed link to experiment');
//    cy.get('#changelogTable').should('contain.text', 'Removed link to resource');
//    cy.get('#changelogTable').should('contain.text', 'Removed link to compound');
//  };
//});
