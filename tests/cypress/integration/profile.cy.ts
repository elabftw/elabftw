describe('Profile', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Shows profile page', () => {
    cy.visit('/profile.php');
    cy.htmlvalidate();
    cy.get('h1#pageTitle').should('have.text', 'Profile');
  });

  it('Applies attachments filters', () => {
    cy.visit('/profile.php?tab=2');
    cy.get('select[name="state"]').select('3');
    cy.location('search').should('include', 'state=3');
    cy.get('select[name="sort"]').select('asc');
    cy.location('search').should('include', 'sort=asc');
    cy.get('select[name="state"]').select('');
    cy.location('search').should('not.include', 'state=');
  });
});
