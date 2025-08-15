describe('Register new user', () => {
  it('fills form and submits', () => {
    cy.visit('/register.php');
    cy.htmlvalidate();
    cy.get('#team-ts-control').click().get('#team-ts-dropdown').contains('Alpha').click();
    const randomChars = Math.random().toString(36).slice(2,8);
    cy.get('#email').type(`newCypressUser-${randomChars}@yopmail.com`).blur();
    cy.get('#password').type('cypress1cypress').blur();
    cy.get('#firstname').type('newCypress').blur();
    cy.get('#lastname').type('User').blur();
    cy.get('form').submit();

    cy.get('div.alert.alert-success').should('contain', 'Registration successful :)');
  });

  it('detects attempt of a bot to register', () => {
    cy.visit('/register.php');
    cy.get('input[name="bot"]').type('I am a bot', {force: true});
    cy.get('#team-ts-control').click().get('#team-ts-dropdown').contains('Alpha').click();
    cy.get('#email').type('newCypressUser@yopmail.com').blur();
    cy.get('#password').type('cypress1cypress').blur();
    cy.get('#firstname').type('newCypress').blur();
    cy.get('#lastname').type('User').blur();
    cy.get('form').submit();

    cy.get('div.alert.alert-danger').should('contain', 'Sorry, you are not allowed to perform that action.');
  });
});
