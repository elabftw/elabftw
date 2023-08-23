describe('Make', () => {
  beforeEach(() => {
    cy.login();
    cy.enableCodeCoverage(Cypress.currentTest.titlePath[0]);
  });

  it('csv', () => {
    cy.request('/make.php?format=csv&type=experiments&id=1,2,3,4').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('eln', () => {
    cy.request('/make.php?format=eln&type=experiments&id=1,2,3,4').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });


  it('multientry PDF', () => {
    cy.request('/make.php?format=pdf&type=experiments&id=1,2,3,4').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('qrPDF', () => {
    cy.request('/make.php?format=qrpdf&type=experiments&id=1,2,3,4').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('JSON', () => {
    cy.request('/make.php?format=json&type=experiments&id=1,2,3,4').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('qrpng', () => {
    cy.request('/make.php?format=qrpng&type=experiments&id=1').then(resp => {
      expect(resp.status).to.eq(200);
    });
    cy.request('/make.php?format=qrpng&type=experiments&id=1,2').then(resp => {
      expect(resp.status).to.eq(200);
    });

  });

  it('report', () => {
    cy.request('/make.php?format=report').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('zip with PDFA', () => {
    cy.request('/make.php?format=zipa&type=experiments&id=1,2,3,4').then(resp => {
      expect(resp.status).to.eq(200);
    });
  });

  it('scheduler report', () => {
    cy.visit('/make.php?format=schedulerReport');
    cy.get('div.alert.alert-danger').should('contain', 'There are no events to report');
  });
});
