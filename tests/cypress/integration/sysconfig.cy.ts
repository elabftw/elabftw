describe('Sysconfig', () => {
  beforeEach(() => {
    cy.login();
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
    }
  });
});
