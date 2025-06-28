describe('Profile', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Shows profile page', () => {
    cy.visit('/profile.php');
    cy.htmlvalidate();
    cy.get('h1#pageTitle').should('have.text', 'Profile');
  });
});
