describe('Sysconfig', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Show sysconfig page', () => {
    cy.visit('/sysconfig.php');
    cy.get('h1#pageTitle').should('have.text', 'Instance settings');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();

    for (let i = 1; i <= 13; i++) {
      cy.visit(`/sysconfig.php?tab=${i}`);
      cy.get(`[data-tabtarget="${i}"]`).should('have.class', 'selected');
      cy.get('#loading-spinner').should('not.exist');
      cy.get(`div[data-tabcontent="${i}"]`).htmlvalidate();
    }

    // Search user
    cy.visit('/sysconfig.php?tab=3&q=toto');
    cy.get('#editUsersBox').should('contain', 'Le sysadmin');
  });
});
