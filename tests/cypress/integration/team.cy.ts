describe('Team', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Show team page', () => {
    // Scheduler
    cy.visit('/team.php');
    cy.get('h1#pageTitle').should('have.text', 'Team');
    cy.get('[data-tabtarget="1"]').should('have.class', 'selected');

    // Members
    cy.visit('/team.php?tab=2');
    cy.get('[data-tabtarget="2"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Members');

    // Templates
    cy.visit('/team.php?tab=3');
    cy.get('[data-tabtarget="3"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Experiments Templates');

    // Send email
    cy.visit('/team.php?tab=4');
    cy.get('[data-tabtarget="4"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Send an email to users');
  });
});
