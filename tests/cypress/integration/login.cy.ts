describe('Login page', () => {
  beforeEach(() => {
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('sets auth cookie when logging in via form submission', function() {
    const email = 'toto@yopmail.com';
    const password = 'totototo';

    cy.visit('/login.php');

    cy.get('input[id=email]').type(email);

    // {enter} causes the form to submit
    cy.get('input[name=password]').type(`${password}{enter}`);
    // auth cookies should be present
    cy.getCookie('token').should('exist').then(cookie => {
      expect(cookie.value).to.match(/^[0-9a-fA-F]{64}$/);
    });
    cy.getCookie('token_team').should('exist').then(cookie => {
      expect(cookie.value).to.eq('1');
    });
    cy.getCookie('devicetoken').should('exist');
    // UI should reflect this user being logged in
    cy.get('h6.dropdown-header').should('contain', 'Toto');
  });

  function fillEmailAddress(email) {
    cy.visit('/login.php');
    cy.intercept('/app/controllers/ResetPasswordController.php').as('ResetPasswordController');
    cy.intercept('/login.php').as('login');
    cy.get('[data-target="resetModal"]').click();
    cy.get('[placeholder="Enter your email address"]').should('be.visible').wait(500).type(email);
    cy.wait(['@ResetPasswordController', '@login']);
  }

  const answer = 'If the account exists, an email has been sent.';

  it('resets password of non-existing user', () => {
    fillEmailAddress('does.not@exist.com{enter}');
    cy.get('div.alert.alert-success').should('contain', answer);
  });

  it('resets password of existing user', () => {
    fillEmailAddress('toto@yopmail.com{enter}');
    cy.get('div.alert.alert-success').should('contain', answer);
  });
});
