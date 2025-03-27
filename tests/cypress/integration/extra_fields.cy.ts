describe('Extra Fields', () => {
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
      });
    });
  };

  it('Create experiemnt with valid extra fileds', () => {
    const title = 'cypress: valid extra fields test';
    postRequest('experiments', {
      title,
      metadata: {
        non_extra_filed: {
          array: [
            'some',
            'data',
            true,
            false,
            1.2,
            null,
          ],
          object: {
            foo: 'bar',
            fizz: 'buzz',
          },
        },
        extra_fields: {
          'Field name': {
            type: 'text',
            value: 'With a value',
            required: true,
            description: 'An extra field of type text',
          },
        },
      },
    }).then(resp => {
      expect(resp.status).to.eq(201);
      cy.visit('/experiments.php');
      cy.get('#itemList').contains(title).click();
      cy.get('#extraFieldsDiv')
        .should('be.visible')
        .should('contain', 'Field name')
        .should('contain', 'With a value')
        .should('contain', 'An extra field of type text')
        .should('contain', 'non_extra_filed')
        .should('contain', 'array')
        .should('contain', 'some')
        .should('contain', 'data')
        .should('contain', '1.2')
        .should('contain', 'true')
        .should('contain', 'false')
        .should('contain', 'NULL')
        .should('contain', 'object')
        .should('contain', 'foo')
        .should('contain', 'bar')
        .should('contain', 'fizz')
        .should('contain', 'buzz');
    });
  });

  it('Create resource with invalid extra fileds', () => {
    postRequest('items', {
      metadata: {
        extra_fields: {
          'Field name': 'With a value',
        },
      },
    }).then(resp => {
      expect(resp.status).to.eq(400);
      expect(resp.body.description).to.contain('Extra field "Field name" does not comply with the expected format.');
    });

    postRequest('items', {
      metadata: {
        extra_fields: {
          'Field name': {
            type: 'text',
          },
        },
      },
    }).then(resp => {
      expect(resp.status).to.eq(400);
      expect(resp.body.description).to.contain('Extra field "Field name" does not have the required property "value".');
    });
  });

  it('Create template with metadata without extra fileds', () => {
    const title = 'cypress: metadata without extra fields test';
    const metadata = [
      'random',
      'data',
      'can be numbers',
      123,
      'or booleans',
      true,
      'or null',
      null,
      'or arrays',
      'some',
      'data',
      false,
    ];
    postRequest('experiments_templates', {
      title,
      metadata: {
        some: {
          [`${metadata[0]}`]: metadata[1],
          [`${metadata[2]}`]: metadata[3],
          [`${metadata[4]}`]: metadata[5],
          [`${metadata[6]}`]: metadata[7],
          [`${metadata[8]}`]: [
            metadata[9],
            metadata[10],
            metadata[11],
          ],
        },
      },
    }).then(resp => {
      cy.log(resp.body.description);
      cy.log(resp.body.message);
      expect(resp.status).to.eq(201);
      cy.visit('/templates.php');
      cy.get('#itemList').contains(title).click();
      cy.get('#extraFieldsDiv')
        .should('be.visible')
        .should('contain', metadata[0])
        .should('contain', metadata[1])
        .should('contain', metadata[2])
        .should('contain', metadata[3])
        .should('contain', metadata[4])
        .should('contain', metadata[5].toString())
        .should('contain', metadata[6])
        .should('contain', 'NULL')
        .should('contain', metadata[8])
        .should('contain', metadata[9])
        .should('contain', metadata[10])
        .should('contain', metadata[11].toString());
    });
  });
});
