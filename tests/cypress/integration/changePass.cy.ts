describe('Change Password', () => {
  beforeEach(() => {
    cy.login();
  });

  it('Change Password', () => {
    // will be status code 403
    cy.visit('/change-pass.php', { failOnStatusCode: false });
    cy.htmlvalidate();
    cy.contains('Bad parameters in url.').should('be.visible');

    // tampered key, status 400
    cy.visit('/change-pass.php?key=def50200e0233e366243196f01e43f1ee3ba7e71389e7a1abb4bee25eb9a19a12f6f03a8fd237d3a268343fa59fccc35cfc576a03c843eee8a17a6540e9ccf2b294c48127b050b17a75a96391c1a0a6b56d7515dfa5ce03cfdb8d2e872086c4a689efb802429abfb3681594aaee78d09c88e38367e5ecc8c3e1974a0fb824c5e8212c453d7a70bd9ab059195b57cc8', { failOnStatusCode: false });
    cy.get('div.alert.alert-danger').should('contain', 'Error decrypting key. Make sure the URL is correct and complete!');
  });
});
