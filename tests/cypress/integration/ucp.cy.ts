describe('UCP', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Show UCP page', () => {
    cy.visit('/ucp.php');
    cy.get('h1#pageTitle').should('have.text', 'Settings');
    cy.get('#loading-spinner').should('not.exist');
    cy.htmlvalidate();

    for (let i = 1; i <= 4; i++) {
      cy.visit(`/ucp.php?tab=${i}`);
      cy.get(`[data-tabtarget="${i}"]`).should('have.class', 'selected');
      cy.get('#loading-spinner').should('not.exist');
    }
  });
});
