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
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();

    // Scheduler with selected item
    cy.visit('/scheduler.php?item=1');
    cy.get('[data-action="remove-param-reload"][data-target="item"]').should('exist');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();

    // Members
    cy.visit('/team.php?tab=1');
    cy.get('[data-tabtarget="1"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Members');
    cy.get('#loading-spinner').should('not.exist');
    cy.get('div[data-tabcontent="1"]').htmlvalidate();

    // Send email
    cy.visit('/team.php?tab=2');
    cy.get('[data-tabtarget="2"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Send an email to users');
    cy.get('#loading-spinner').should('not.exist');
    cy.get('div[data-tabcontent="2"]').htmlvalidate();
  });
});
