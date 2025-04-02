describe('Import links', () => {
  beforeEach(() => {
    cy.login().as('csrfToken');
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  const postRequest = (endpoint: string, body: object): Cypress.Chainable => {
    return cy.get('@csrfToken').then(token => {
      return cy.request({
        url: `/api/v2/${endpoint}`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': token,
        },
        body: JSON.stringify(body),
        failOnStatusCode: false,
        followRedirect: false,
      });
    });
  };

  it('can import from experiments and resources', () => {
    // create 4 exp via API
    ['A', 'B', 'C', 'D'].forEach(title => {
      postRequest('experiments', {
        title: `Links test-${title}`,
      }).then(res => expect(res.status).to.equal(201));
    });

    // create 4 res via API
    ['a', 'b', 'c', 'd'].forEach(title => {
      postRequest('items', {
        title: `Links test-${title}`,
      }).then(res => expect(res.status).to.equal(201));
    });

    // link exp A to exp B and res b
    cy.visit('/experiments.php');
    cy.get('#itemList').contains('Links test-A').click();
    cy.intercept('GET', /\/api\/v2\/experiments\/\?.+$/).as('getExpQueryApi');
    cy.get('#topToolbar').get('[title="Edit"]').click();

    let target = 'Links test-B';
    cy.intercept('POST', '/api/v2/experiments/*/experiments_links/*').as('postExpLinkExpApi');
    cy.get('#addLinkExpInput').type(target, {delay: 0});
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });

    cy.intercept('GET', '/experiments.php?mode=edit*').as('getExpPage');
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkExpApi');
      cy.wait('@getExpPage');
      cy.get('#linksExpDiv').should('contain', target);
    });

    cy.intercept('GET', /api\/v2\/items\/\?.+$/).as('getResQueryApi');
    cy.intercept('POST', '/api/v2/experiments/*/items_links/*').as('postExpLinkResApi');
    cy.get('#addLinkItemsInput').type(target, {delay: 0});
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkResApi');
      cy.wait('@getExpPage');
      cy.get('#linksDiv').should('contain', target);
    });

    // link res a to exp D and res d
    target = 'Links test-D';
    cy.visit('/database.php');
    cy.get('#itemList').contains('Links test-a').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    cy.intercept('GET', '/database.php?mode=edit*').as('getResPage');
    cy.intercept('POST', '/api/v2/items/*/experiments_links/*').as('postResLinkExpApi');
    cy.get('#addLinkExpInput').type(target, {delay: 0});
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkExpApi');
      cy.wait('@getResPage');
      cy.get('#linksExpDiv').should('contain', target);
    });

    target = 'Links test-d';
    cy.intercept('POST', '/api/v2/items/*/items_links/*').as('postResLinkResApi');
    cy.get('#addLinkItemsInput').type(target, {delay: 0});
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkResApi');
      cy.wait('@getResPage');
      cy.get('#linksDiv').should('contain', target);
    });

    // link exp C to exp A and res a
    cy.visit('/experiments.php');
    cy.get('#itemList').contains('Links test-C').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    target = 'Links test-A';
    cy.get('#addLinkExpInput').type(target);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkExpApi');
      cy.wait('@getExpPage');
      cy.get('#linksExpDiv').should('contain', target);
    });

    target = 'Links test-a';
    cy.get('#addLinkItemsInput').type(target);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postExpLinkResApi');
      cy.wait('@getExpPage');
      cy.get('#linksDiv').should('contain', target);
    });

    // import links from exp A and res a
    cy.get('#linksExpDiv [data-action="import-links"]').click();
    cy.get('#linksExpDiv').should('contain', 'Links test-B');
    cy.get('#linksDiv').should('contain', 'Links test-b');

    cy.get('#linksDiv [data-action="import-links"]').click();
    cy.get('#linksExpDiv').should('contain', 'Links test-D');
    cy.get('#linksDiv').should('contain', 'Links test-d');

    // link res c to exp A and res a
    cy.visit('/database.php');
    cy.get('#itemList').contains('Links test-c').click();
    cy.get('#topToolbar').get('[title="Edit"]').click();

    target = 'Links test-A';
    cy.get('#addLinkExpInput').type(target);
    cy.wait('@getExpQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkExpInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksExpDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkExpApi');
      cy.wait('@getResPage');
      cy.get('#linksExpDiv').should('contain', target);
    });

    target = 'Links test-a';
    cy.get('#addLinkItemsInput').type(target);
    cy.wait('@getResQueryApi').then(() => {
      cy.get('.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front').contains(`- ${target}`).should('be.visible');
      cy.get('#addLinkItemsInput').type('{downArrow}', {delay: 500});
    });
    cy.get('#linksDiv').contains('Add').click().then(() => {
      cy.wait('@postResLinkResApi');
      cy.wait('@getResPage');
      cy.get('#linksDiv').should('contain', target);
    });

    // import links from exp A and res a
    cy.get('#linksExpDiv [data-action="import-links"]').click();
    cy.get('#linksExpDiv').should('contain', 'Links test-B');
    cy.get('#linksDiv').should('contain', 'Links test-b');

    cy.get('#linksDiv [data-action="import-links"]').click();
    cy.get('#linksExpDiv').should('contain', 'Links test-D');
    cy.get('#linksDiv').should('contain', 'Links test-d');
  });
});
