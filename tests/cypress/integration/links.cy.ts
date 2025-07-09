describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('experiments can have links to experiments and resources', () => {
    cy.on('window:confirm', cy.stub().returns(true));
    // test is random on CI: either passes Chrome and not Edge, either stops at Chrome for a timeout, either passes without any changes... See if full removal or rework
    /*
    cy.visit('/experiments.php?mode=edit&id=33');
    // the first get takes time on Edge e2e tests - CircleCI
    cy.get('#addLinkExpInput', { timeout: 20000 }).should('be.visible');
    cy.get('.ui-menu-item-wrapper').first().click();
    cy.get('button[aria-label="Add experiment link"]').click();
    cy.get('#experimentsLinksDiv').should('contain.text', 'Synthesis');
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
    cy.get('#experimentsLinksDiv').should('not.contain.text', 'Synthesis and Characterization');

    // link to a resource
    cy.get('#addLinkItemsInput').type('Ammonia');
    cy.get('.ui-menu-item-wrapper').contains('Ammonia').click();
    cy.get('button[aria-label="Add item link"]').click();
    cy.get('#itemsLinksDiv').should('contain.text', 'Ammonia - NH3');
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
     */
  });
});
