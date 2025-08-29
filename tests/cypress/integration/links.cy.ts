describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('can link other experiments and resources to an experiment', () => {
    cy.getExperimentId().then(expid => {
      cy.visit(`/experiments.php?mode=edit&id=${expid}`);
    });
    // create the new experiment to link
    const expTitle = 'Linked Experiment';
    cy.request({ method: 'POST', url: '/api/v2/experiments', body: {title: expTitle} });
    // add link to the new experiment
    cy.get('#addLinkExpInput').type(expTitle);
    cy.get('.ui-menu-item-wrapper').first().click();
    cy.get('button[aria-label="Add link to an experiment"]').click();
    cy.get('#experimentsLinksDiv').should('contain.text', expTitle);
    // remove link to the new experiment
    cy.get('[data-action="destroy-link"]').first().click();
    cy.get('#experimentsLinksDiv').should('not.contain.text', expTitle);

    // link a new resource
    const itemTitle = 'Linked Resource';
    cy.request({ method: 'POST', url: '/api/v2/items', body: {title: itemTitle} });
    cy.get('#addLinkItemsInput').type(itemTitle);
    cy.get('.ui-menu-item-wrapper').contains(itemTitle).click();
    cy.get('button[aria-label="Add link to a resource"]').click();
    cy.get('#itemsLinksDiv').should('contain.text', itemTitle);
    cy.get('#linksDiv [data-action="destroy-link"][data-endpoint="items_links"]').first().click();
    cy.get('#itemsLinksDiv').should('not.contain.text', itemTitle);
  });

  // TODO: add compound, delete, check logs for all three link additions
});
