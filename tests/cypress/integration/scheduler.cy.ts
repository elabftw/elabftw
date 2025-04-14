describe('Scheduler', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Displays Scheduler page', () => {
    // Scheduler
    cy.visit('/scheduler.php');
    cy.get('h1#pageTitle').should('have.text', 'Scheduler');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();

    // Scheduler with selected item
    cy.visit('/scheduler.php?item=1');
    cy.get('#schedulerResourceDisplay').should('exist');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();
  });
});
