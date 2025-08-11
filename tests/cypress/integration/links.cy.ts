// WARNING flaky test
describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('experiments can have links to experiments and resources', () => {
    cy.on('window:confirm', cy.stub().returns(true));

    cy.getExperimentId().then(expid => {
      cy.visit(`/experiments.php?mode=edit&id=${expid}`);
    });
    // link to an experiment
    const expTitle = 'Link target';
    // create an experiment first
    cy.request({ method: 'POST', url: '/api/v2/experiments', body: {title: expTitle} });
    cy.get('#addLinkExpInput').type(expTitle);
    cy.get('.ui-menu-item-wrapper').first().click();
    cy.get('button[aria-label="Add link to an experiment"]').click();
    cy.get('#experimentsLinksDiv').should('contain.text', expTitle);
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
    cy.get('#experimentsLinksDiv').should('not.contain.text', expTitle);

    // link to a resource
    const itemTitle = 'Light sheet 1';
    // create a resource first
    cy.request({ method: 'POST', url: '/api/v2/items', body: {title: itemTitle} });
    cy.get('#addLinkItemsInput').type(itemTitle);
    cy.get('.ui-menu-item-wrapper').contains(itemTitle).click();
    cy.get('button[aria-label="Add item link"]').click();
    cy.get('#itemsLinksDiv').should('contain.text', itemTitle);
    cy.intercept('DELETE', '/api/v2/**').as('delete');
    cy.get('[data-action="destroy-link"]').first().click();
    cy.wait('@delete').its('response.statusCode').should('eq', 204);
  });
});
