describe('Metadata XML', () => {
  // no need to login first
  it('Show metadata error', () => {
    // will reply with status 400
    cy.visit('/metadata.php', { failOnStatusCode: false });
    cy.get('body').should('contain', 'No Service Provider configured. Aborting.');
  });
});
