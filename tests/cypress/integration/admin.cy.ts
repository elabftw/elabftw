describe('admin page', () => {
  beforeEach(() => {
    cy.login();
  });

  it('has valid html', () => {
    cy.visit('/admin.php?');
    cy.get('h1#pageTitle').should('have.text', 'Admin panel');
    // wait for page and tinymce to load before htmlvalidate
    cy.get('#loading-spinner').should('not.exist');
    cy.get('div.tox-menubar').contains('File').should('exist');
    cy.htmlvalidate();

    for (let i = 1; i <= 6; i++) {
      cy.visit(`/admin.php?tab=${i}`);
      cy.get(`[data-tabtarget="${i}"]`).should('have.class', 'selected');
      cy.get('#loading-spinner').should('not.exist');
    }
  });
});
