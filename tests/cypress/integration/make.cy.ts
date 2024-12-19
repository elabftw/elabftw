describe('Make', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath.join(' '));
  });

  it('csv', () => {
    cy.request('/make.php?format=csv&type=experiments&id=1+2').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('text/csv; charset=UTF-8');
    });
  });

  it('eln', () => {
    cy.request('/make.php?format=eln&type=experiments&id=1+2').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/zip');
    });
  });


  it('single entry PDF', () => {
    cy.request('/make.php?format=pdf&type=experiments&id=1').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/pdf');
    });
  });

  it('multientry PDF', () => {
    cy.request('/make.php?format=pdf&type=experiments&id=1+2').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/pdf');
    });
  });

  it('qrPDF', () => {
    cy.request('/make.php?format=qrpdf&type=experiments&id=1+2').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/pdf');
    });
  });

  it('JSON', () => {
    cy.request('/make.php?format=json&type=experiments&id=1+2').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/json');
    });
  });

  it('qrpng', () => {
    cy.request('/make.php?format=qrpng&type=experiments&id=1').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('image/png');
    });
    cy.visit('/make.php?format=qrpng&type=experiments&id=1+2');
    cy.get('div.alert.alert-danger').should('contain', 'QR PNG format is only suitable for one ID.');
  });

  it('report', () => {
    cy.request('/make.php?format=report').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('zip with PDFA', () => {
    cy.request('/make.php?format=zipa&type=experiments&id=1').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('application/zip');
    });
  });

  it('scheduler report', () => {
    cy.request('/make.php?format=schedulerReport').then(resp => {
      expect(resp.status).to.eq(200);
      expect(resp.headers['content-type']).to.eq('text/csv; charset=UTF-8');
    });
  });
});
