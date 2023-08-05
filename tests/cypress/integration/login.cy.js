describe('Logging in', () => {
  it('sets auth cookie when logging in via form submission', function () {
    const email = 'toto@yopmail.com';
    const password = 'totototo';

    cy.visit('/login.php');

    cy.get('input[id=email]').type(email);

    // {enter} causes the form to submit
    cy.get('input[name=password]').type(`${password}{enter}`);
    cy.getCookie('token').should('exist').then(cookie => {
      expect(cookie.value).to.match(/^[0-9a-fA-F]{64}$/)
    })
  });
});
