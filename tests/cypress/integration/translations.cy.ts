const language = String(Cypress.env('translationLanguage') ?? 'fr_FR');
const pages = Cypress.env('webPhpPages') as string[];

// Cypress cannot cy.visit() non-HTML responses. They still need to be
// requested so every top-level web/*.php entry point is covered.
const requestOnlyPages = new Set([
  'healthcheck.php',
  'metadata.php',
]);

describe(`Translation pages (${language})`, () => {
  let originalUserLanguage: string | undefined;
  let originalInstanceLanguage: string | undefined;

  before(() => {
    expect(pages, 'discovered web/*.php pages')
      .to.be.an('array')
      .and.not.be.empty;

    // Twig is rendered server-side, so loading the static assets adds no
    // coverage here and makes visiting all pages considerably slower.
    cy.intercept(
      'GET',
      /\.(?:css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot)(?:\?.*)?$/,
      { statusCode: 204 },
    );

    cy.login();

    cy.request('/api/v2/users/me').then(response => {
      originalUserLanguage = response.body.lang;
    });
    cy.request('/api/v2/config').then(response => {
      originalInstanceLanguage = response.body.lang;
    });

    cy.request({
      method: 'PATCH',
      url: '/api/v2/users/me',
      body: { lang: language },
    }).then(response => {
      expect(response.status).to.eq(200);
      expect(response.body.lang).to.eq(language);
    });

    cy.request({
      method: 'PATCH',
      url: '/api/v2/config',
      body: { lang: language },
    }).then(response => {
      expect(response.status).to.eq(200);
      expect(response.body.lang).to.eq(language);
    });
  });

  after(() => {
    if (originalUserLanguage !== undefined) {
      cy.request({
        method: 'PATCH',
        url: '/api/v2/users/me',
        body: { lang: originalUserLanguage },
      });
    }

    if (originalInstanceLanguage !== undefined) {
      cy.request({
        method: 'PATCH',
        url: '/api/v2/config',
        body: { lang: originalInstanceLanguage },
      });
    }
  });

  it('renders every PHP entry point with content', () => {
    for (const page of pages) {
      cy.visit(`/${page}`, { failOnStatusCode: false });
      cy.document().then(document => {
        const content = document.body?.innerText.trim()
          || document.body?.innerHTML.trim()
          || '';

        expect(content.length, `${page} body content`)
          .to.be.greaterThan(0);
      });
    }
  });
