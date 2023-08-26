describe('Profile', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('SHow profile page', () => {
    cy.visit('/profile.php');
    cy.get('h1#pageTitle').should('have.text', 'Profile');
  });
});
