describe('Users tab in Admin page', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/admin.php?tab=3&q=toto');
  });

  it('cannot create user with empty fields', () => {
    // create user without filling
    cy.get('#initialCreateUserBtn').should('exist').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Invalid input found! Aborting');
  });

  it('creates user', () => {
    // Team & Permission group are already filled on this form, by default
    cy.get('#firstname').type('theNewToto');
    cy.get('#lastname').type('notSysAdmin');
    const randomChars = Math.random().toString(36).slice(2,8);
    cy.get('#email').type(`totonew-${randomChars}@yopmail.com`);

    // create the user
    cy.get('#initialCreateUserBtn').should('exist').click();
    cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
  });

  /* TODO
  it('deletes user', () => {
    // if there's a second user, target the dropdown and delete target
    cy.get('button[aria-label="More options"]').then(($buttons) => {
      if ($buttons.length > 1) {
        // target the 2nd dropdown to delete target user and not the admin
        cy.wrap($buttons.eq(1)).click();

        // confirm modal for deletion
        cy.on('window:confirm', (text) => {
          expect(text).to.contain('Are you sure you want to remove permanently');
          return true; // clicks 'OK' on the modal
        });

        // click the 'Delete user' from dropdown
        cy.get('[data-action="destroy-user"]:visible').click();
        cy.get('.overlay').first().should('be.visible').should('contain', 'Saved');
      } else {
        cy.log('Second dropdown not found, skipping deletion');
      }
    });
  });
 */
});
