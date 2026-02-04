describe('User changes theme', () => {
  beforeEach(() => {
    cy.login();
  });

  it('defaults to light mode', () => {
    cy.clearCookies();
    cy.get('html').should('not.have.class', 'dark-mode');
  });

  it('user changes theme to dark mode', () => {
    cy.clearCookies();
    cy.login('titi@yopmail.com');
    cy.visit('/dashboard.php');
    cy.get('#navbarDropdown').click();
    cy.get('[data-action="toggle-dark-mode"]').click();
    cy.get('.overlay').should('contain', 'Saved');
    cy.get('html').should('have.class', 'dark-mode');
    // it keeps dark mode after logout thanks to cookies
    cy.get('#navbarDropdown').should('exist').click();
    cy.get('[data-action="logout"]').click();
    cy.location('pathname').should('include', '/login.php');
    cy.get('html').should('have.class', 'dark-mode');
  });
});
