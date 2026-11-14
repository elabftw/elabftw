describe('Custom fields as columns in table mode', () => {
  beforeEach(() => {
    cy.login();
    cy.request('PATCH', '/api/v2/users/me', { display_mode: 'tb' });
  });

  afterEach(() => {
    cy.request('PATCH', '/api/v2/users/me', { display_mode: 'it' });
  });

  it('adds a column, sorts mixed values consistently and renders multiple links', () => {
    const prefix = `CypressColumns${Date.now()}`;
    const key = `${prefix}Value`;
    const urlKey = `${prefix}Urls`;
    const colId = `extra_field:${key}`;
    // numbers first, then text: the order must not depend on the order of creation
    ['2x', '1e2', '3'].forEach((value, index) => {
      cy.request('POST', '/api/v2/experiments', { title: `${prefix} ${index}` }).then(response => {
        cy.extractIdFromLocation(response).then(id => {
          const extraFields: Record<string, object> = { [key]: { type: 'text', value } };
          if (index === 0) {
            extraFields[urlKey] = { type: 'url', allow_multi_values: true, value: ['https://example.org/a', 'https://example.org/b'] };
          }
          cy.request('PATCH', `/api/v2/experiments/${id}`, { metadata: JSON.stringify({ extra_fields: extraFields }) });
        });
      });
    });

    cy.visit(`/experiments.php?q=${prefix}`);
    cy.get('.entities-table-extra-fields > button').click();
    // wait for the keys to be loaded before picking the first match with enter
    cy.get('.entities-table-extra-fields-list').should('contain', urlKey);
    cy.get('.entities-table-extra-fields-menu input[type="search"]').type(`${urlKey}{enter}`);
    cy.get('.entities-table-extra-fields-menu input[type="search"]').type(`${key}{enter}`);
    // both are added, in this order
    cy.get('.entities-table-extra-fields-menu ul').last().find('li').should('have.length', 2)
      .then(items => expect([...items].map(item => item.innerText.trim())).to.deep.equal([urlKey, key]));
    cy.get('body').type('{esc}');

    // the new columns are appended after the regular ones, out of view: the grid only renders the visible columns
    cy.get('.ag-body-horizontal-scroll-viewport').scrollTo('right', { ensureScrollable: false });
    cy.get(`.ag-header-cell[col-id="${colId}"]`).should('exist').click();
    cy.get(`.ag-row[row-index="0"] .ag-cell[col-id="${colId}"]`).should('have.text', '3');
    cy.get(`.ag-row[row-index="1"] .ag-cell[col-id="${colId}"]`).should('have.text', '1e2');
    cy.get(`.ag-row[row-index="2"] .ag-cell[col-id="${colId}"]`).should('have.text', '2x');

    // each url is its own link
    cy.get(`.ag-cell[col-id="extra_field:${urlKey}"] a`).should('have.length', 2)
      .first().should('have.attr', 'href', 'https://example.org/a');
  });
});
