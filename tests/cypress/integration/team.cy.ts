describe('Team', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Show team page', () => {
    // Scheduler
    cy.visit('/team.php');
    cy.htmlvalidate();
    cy.get('h1#pageTitle').should('have.text', 'Team');
    cy.get('[data-tabtarget="1"]').should('have.class', 'selected');

    // Scheduler with selected item
    cy.visit('/team.php?tab=1&item=1');
    cy.get('[data-tabtarget="1"]').should('have.class', 'selected');
    cy.get('[data-action="remove-param-reload"][data-target="item"]').should('exist');

    // Members
    cy.visit('/team.php?tab=2');
    cy.get('[data-tabtarget="2"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Members');

    // Templates
    cy.visit('/team.php?tab=3');
    cy.get('[data-tabtarget="3"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Experiments Templates');
    cy.get('#tplTable').should('exist');

    // View individual template
    cy.visit('/team.php?tab=3&mode=view&templateid=1');
    cy.get('#templatesDiv').should('exist');
    
    // Send email
    cy.visit('/team.php?tab=4');
    cy.get('[data-tabtarget="4"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Send an email to users');
  });
});
