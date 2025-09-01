describe('Login page', () => {
  beforeEach(() => {
  });

  it('sets auth cookie when logging in via form submission', () => {
    const email = 'toto@yopmail.com';
    const password = 'totototototo';

    cy.visit('/login.php');

    cy.htmlvalidate();

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

  function fillEmailAddress(email: string): void {
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

  it ('logs in as anonymous user', () => {
    cy.visit('/login.php');
    cy.get('#anon-login').should('exist');
    cy.get('#anon_login_select').select(0);
    cy.get('#anon-login button[type="submit"]').click();
    cy.location('pathname').should('eq', '/experiments.php');
    cy.htmlvalidate();
    cy.request('/app/logout.php');
  });
});
