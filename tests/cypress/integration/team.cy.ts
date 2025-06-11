describe('Team', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Show team page', () => {
    // Members
    cy.visit('/team.php');
    cy.get('h1#pageTitle').should('have.text', 'Team');
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

    // Procurement Requests
    cy.visit('/team.php?tab=3');
    cy.get('[data-tabtarget="3"]').should('have.class', 'selected');
    cy.get('h3.section-title').should('contain.text', 'Procurement requests');
    cy.get('#loading-spinner').should('not.exist');
    cy.get('div[data-tabcontent="3"]').htmlvalidate();
  });
});
