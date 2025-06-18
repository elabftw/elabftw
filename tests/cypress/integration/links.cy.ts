describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('experiments can have links to experiments and resources', () => {
    cy.on('window:confirm', cy.stub().returns(true));

    cy.visit('/experiments.php?mode=edit&id=33');
    // link to an experiment
    cy.get('#addLinkExpInput').type('Synthesis');
    cy.get('.ui-menu-item-wrapper').first().click();
    cy.get('#addLinkExpInput').type('{enter}');
    cy.get('#experimentsLinksDiv').should('contain.text', 'Synthesis');
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
    //cy.get('#experimentsLinksDiv').should('not.contain.text', 'Synthesis and Characterization');

    // link to a resource
    /*
    cy.get('#addLinkItemsInput').type('Ammonia');
    cy.get('.ui-menu-item-wrapper').click();
    cy.get('#addLinkExpInput').type('{enter}');
    cy.get('#itemsLinksDiv').should('contain.text', 'Ammonia - NH3');
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
   */
  });
});
