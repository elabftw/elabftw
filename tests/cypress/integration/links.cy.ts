describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('experiments can have links to experiments and resources', () => {
    cy.on('window:confirm', cy.stub().returns(true));

    cy.visit('/experiments.php?mode=edit&id=10');
    // link to an experiment
    cy.get('#addLinkExpInput').type('Testing');
    cy.get('.ui-menu-item-wrapper').first().click();
    cy.get('button[aria-label="Add experiment link"]').click();
    cy.get('#experimentsLinksDiv').should('contain.text', 'Testing');
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
    cy.get('#experimentsLinksDiv').should('not.contain.text', 'Testing the eLabFTW');

    // link to a resource
    const itemTitle = 'Light sheet 1';
    cy.get('#addLinkItemsInput').type(itemTitle);
    cy.get('.ui-menu-item-wrapper').contains(itemTitle).click();
    cy.get('button[aria-label="Add item link"]').click();
    cy.get('#itemsLinksDiv').should('contain.text', itemTitle);
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
  });
});
