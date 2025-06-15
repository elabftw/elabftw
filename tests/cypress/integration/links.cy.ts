describe('Test links', () => {
  beforeEach(() => {
    cy.login();
  });

  it('experiments can have links to experiments and resources', () => {
    cy.on('window:confirm', cy.stub().returns(true))

    cy.visit('/experiments.php?mode=edit&id=3');
    // link to an experiment
    cy.get('#addLinkExpInput').type('Synthesis{downArrow}{enter}');
    cy.get('#experimentsLinksDiv').should('contain.text', 'Synthesis and Characterization');
    cy.get('[data-action="destroy-link"]').click();
    cy.get('#experimentsLinksDiv').should('not.contain.text', 'Synthesis and Characterization');

    // link to a resource
    cy.get('#addLinkItemsInput').type('Ammonia{downArrow}{enter}');
    cy.get('#itemsLinksDiv').should('contain.text', 'Ammonia - NH3');
    cy.get('[data-action="destroy-link"]').click();
  });

});
