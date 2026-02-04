describe('User changes theme', () => {
  beforeEach(() => {
    cy.login();
  });

  it('defaults to light mode', () => {
    cy.clearCookies();
    cy.get('html').should('not.have.class', 'dark-mode');
  });
});
