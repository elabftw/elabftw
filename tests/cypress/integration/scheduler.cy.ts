describe('Scheduler', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Displays Scheduler page', () => {
    // Scheduler
    cy.visit('/scheduler.php');
    cy.get('h1#pageTitle').should('have.text', 'Scheduler');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();
  });

  it ('Display Scheduler with selected item', () => {
    // Scheduler with selected item
    cy.visit('/scheduler.php?items[]=1');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();
  });
});
