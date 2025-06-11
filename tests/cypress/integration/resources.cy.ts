describe('Resources', () => {
  beforeEach(() => {
    cy.login();
  });

  it('See page', () => {
    cy.visit('/database.php');
    cy.get('h1#pageTitle').should('have.text', 'Resources');
  });
});
