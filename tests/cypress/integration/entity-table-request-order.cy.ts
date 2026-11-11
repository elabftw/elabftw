describe('Entity table request ordering', () => {
  const row = (id: number, title: string) => ({ id, title, tags_decoded: [] });
  let oldRequestStarted: boolean;

  beforeEach(() => {
    cy.login();
    cy.request('PATCH', '/api/v2/users/me', { display_mode: 'tb' });
    oldRequestStarted = false;
  });

  afterEach(() => {
    cy.request('PATCH', '/api/v2/users/me', { display_mode: 'it' });
  });

  function changeFilter(query: string) {
    cy.window().then(win => {
      win.dispatchEvent(new win.CustomEvent('entity-filters-changed', { detail: `q=${query}` }));
    });
  }

  function interceptResults(latestFails = false) {
    cy.intercept({ method: 'GET', pathname: '/api/v2/experiments' }, req => {
      if (req.query.q === 'older') {
        oldRequestStarted = true;
        req.alias = 'olderResults';
        req.reply({ delay: 1000, body: [row(90101, 'Synthetic older result')] });
      } else if (req.query.q === 'latest') {
        req.alias = 'latestResults';
        req.reply(latestFails
          ? { statusCode: 500, body: { message: 'Synthetic failed search' } }
          : { body: [row(90102, 'Synthetic latest result')] });
      } else {
        req.alias = 'initialResults';
        req.reply([row(90100, 'Synthetic initial result')]);
      }
    });
    cy.visit('/experiments.php');
    cy.wait('@initialResults');
    cy.get('#entityList .ag-cell[col-id="title"]').should('contain.text', 'Synthetic initial result');
  }

  it('keeps the newest filter results when an older response arrives last', () => {
    interceptResults();
    changeFilter('older');
    cy.wrap(null).should(() => expect(oldRequestStarted).to.equal(true));
    changeFilter('latest');
    cy.wait('@latestResults');
    cy.get('#entityList .ag-cell[col-id="title"]').should('contain.text', 'Synthetic latest result');
    cy.wait('@olderResults');
    cy.get('#entityList .ag-cell[col-id="title"]').should('contain.text', 'Synthetic latest result');
    cy.get('#entityList').should('not.contain.text', 'Synthetic older result');
  });

  it('does not replace the last successful rows with stale data if the newest request fails', () => {
    interceptResults(true);
    changeFilter('older');
    cy.wrap(null).should(() => expect(oldRequestStarted).to.equal(true));
    changeFilter('latest');
    cy.wait('@latestResults').its('response.statusCode').should('eq', 500);
    cy.wait('@olderResults');
    cy.get('#entityList .ag-cell[col-id="title"]').should('contain.text', 'Synthetic initial result');
    cy.get('#entityList').should('not.contain.text', 'Synthetic older result');
  });
});
