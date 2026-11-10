describe('Permission user search', () => {
  const modal = '#permModal-canreaddefaultPermissions';
  const input = '#canreaddefaultPermissions-users-select-input';
  const select = '#canreaddefaultPermissions_select_users';
  const toggle = 'label[for="canreaddefaultPermissions_select_users_only_active"]';
  const active = { userid: 90101, fullname: 'Synthetic Active', email: 'active@example.test' };
  const selected = { userid: 90102, fullname: 'Synthetic Selected', email: 'selected@example.test' };
  const archived = { userid: 90103, fullname: 'Synthetic Archived', email: 'archived@example.test' };

  beforeEach(() => {
    cy.login();
  });

  function openPermissions() {
    cy.visit('/ucp.php?tab=1');
    cy.get('[data-target="permModal-canreaddefaultPermissions"]').click();
    cy.get(modal).should('be.visible');
  }

  it('filters suggestions without removing selected users and reloads the same query', () => {
    cy.intercept('GET', '**/api/v2/users/?*', req => {
      const onlyActive = new URL(req.url).searchParams.get('onlyActive') === '1';
      req.alias = onlyActive ? 'activeUsers' : 'allUsers';
      req.reply(onlyActive ? [active] : [active, selected, archived]);
    });
    openPermissions();
    cy.get(input).type('Synthetic');
    cy.wait('@allUsers');
    cy.get(`${modal} .option[data-value="user:90102"]`).click();
    cy.get(input).type('Synthetic');
    cy.get(toggle).click();
    cy.wait('@activeUsers');
    cy.get(select).invoke('val').should('include', 'user:90102');
    cy.get(`${modal} .item[data-value="user:90102"]`).should('exist');
    cy.get(`${modal} .option[data-value="user:90103"]`).should('not.exist');
    cy.get(toggle).click();
    cy.wait('@allUsers');
    cy.get(input).focus();
    cy.get(`${modal} .option[data-value="user:90103"]`).should('exist');
    cy.get(select).invoke('val').should('include', 'user:90102');
    // Do not save these synthetic selections or change any account permissions.
  });

  it('ignores a response from before the filter changed', () => {
    let unfilteredRequestStarted = false;
    cy.intercept('GET', '**/api/v2/users/?*', req => {
      const onlyActive = new URL(req.url).searchParams.get('onlyActive') === '1';
      if (onlyActive) {
        req.alias = 'activeUsers';
        req.reply([active]);
      } else {
        unfilteredRequestStarted = true;
        req.alias = 'oldUsers';
        req.reply({ delay: 1000, body: [active, archived] });
      }
    });
    openPermissions();
    cy.get(input).type('Synthetic');
    cy.wrap(null).should(() => expect(unfilteredRequestStarted).to.equal(true));
    cy.get(toggle).click();
    cy.wait('@activeUsers');
    cy.wait('@oldUsers');
    cy.get(input).focus();
    cy.get(`${modal} .option[data-value="user:90101"]`).should('exist');
    cy.get(`${modal} .option[data-value="user:90103"]`).should('not.exist');
  });
});
