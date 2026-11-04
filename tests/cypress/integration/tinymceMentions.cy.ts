describe('TinyMCE mentions', () => {
  let experimentId: number | null = null;

  const getEditorBody = () => cy.get<HTMLIFrameElement>('iframe.tox-edit-area__iframe')
    .its('0.contentDocument.body').should('not.be.empty').then(cy.wrap);

  beforeEach(() => {
    cy.login();
    cy.request('POST', '/api/v2/experiments', { title: 'Cypress TinyMCE mentions' }).then(response => {
      cy.extractIdFromLocation(response).then(id => {
        experimentId = id;
        cy.visit(`/experiments.php?mode=edit&id=${id}`);
      });
    });
    cy.get('.tox-tinymce').should('be.visible');
  });

  afterEach(() => {
    if (experimentId !== null) {
      cy.request({ method: 'DELETE', url: `/api/v2/experiments/${experimentId}`, failOnStatusCode: false });
      experimentId = null;
    }
  });

  const title = 'p<iframe srcdoc="<p>Injected</p>"></iframe> & <b>bold</b> &lt;em&gt;literal&lt;/em&gt; "quotes"';
  const category = '<img src="data:,"> & Category';

  [
    { type: 'experiments', page: 'experiments.php', selection: 'click' },
    { type: 'items', page: 'database.php', selection: 'keyboard' },
  ].forEach(({ type, page, selection }) => {
    it(`Displays and inserts ${type} titles and categories as text (${selection})`, () => {
      const item = { id: 987654, custom_id: 42, type, page, title, category_title: category, category_color: '12ab34' };
      for (const endpoint of ['experiments', 'items']) {
        cy.intercept({ method: 'GET', pathname: `/api/v2/${endpoint}`, query: { fastq: 'p' } }, {
          body: endpoint === type ? [item] : [],
        }).as(`mention${endpoint}`);
      }
      cy.intercept('POST', `**/api/v2/experiments/*/${type}_links/${item.id}`, { statusCode: 201 }).as('addLink');

      getEditorBody().type('#p');
      cy.wait(['@mentionexperiments', '@mentionitems']);
      cy.get('.rte-autocomplete:visible li').should('have.length', 1);
      cy.get('.rte-autocomplete li a').should('have.text', `${category} - 42 ${title}`);
      cy.get('.rte-autocomplete').find('iframe, img, b, em, script').should('not.exist');
      cy.get('.rte-autocomplete li a > span').first().should('have.css', 'color', 'rgb(18, 171, 52)');
      cy.get('.rte-autocomplete li strong').first().should('have.text', 'p');

      if (selection === 'click') {
        cy.get('.rte-autocomplete li a').click();
      } else {
        getEditorBody().type('{downarrow}{enter}');
      }
      cy.wait('@addLink');
      cy.get('.rte-autocomplete').should('not.exist');
      getEditorBody().find(`a[href="${page}?mode=view&id=${item.id}"]`)
        .should('have.text', `${category} - ${title}`).children().should('have.length', 0);
      getEditorBody().find('iframe, img, b, em, script').should('not.exist');
    });
  });

  it('Restores typed markup as literal text when mentions are cancelled', () => {
    cy.intercept('GET', '**/api/v2/experiments?*', { body: [] });
    cy.intercept('GET', '**/api/v2/items?*', { body: [] });
    const query = '#p <b>bold</b> &lt;i&gt;';
    getEditorBody().type(query, { parseSpecialCharSequences: false }).type('{esc}');
    getEditorBody().should('contain.text', query).find('b, i').should('not.exist');
    cy.get('.rte-autocomplete').should('not.exist');
  });
});
