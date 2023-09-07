describe('admin page', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('has valid html', () => {
    cy.visit('/admin.php?');
    cy.get('h1#pageTitle').should('have.text', 'Admin panel');
    cy.htmlvalidate();

    for (let i = 1; i <= 7; i++) {
      cy.visit(`/admin.php?tab=${i}`);
      cy.get(`[data-tabtarget="${i}"]`).should('have.class', 'selected');
    }
  });
});
