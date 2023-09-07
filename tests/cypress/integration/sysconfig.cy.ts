describe('Sysconfig', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('Show sysconfig page', () => {
    cy.visit('/sysconfig.php');
    cy.get('h1#pageTitle').should('have.text', 'eLabFTW Configuration');
    cy.htmlvalidate();

    for (let i = 1; i <= 12; i++) {
      cy.visit(`/sysconfig.php?tab=${i}`);
      cy.get(`[data-tabtarget="${i}"]`).should('have.class', 'selected');
    }
  });
});
