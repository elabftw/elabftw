describe('Entry image layout', () => {
  it('preserves image proportions in narrow entry bodies and table cells', () => {
    // Exercise the real application stylesheet without creating persistent records.
    cy.visit('/login.php');
    cy.document().then(doc => {
      const fixture = doc.createElement('div');
      fixture.id = 'body_view';
      fixture.style.width = '160px';
      const source = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="navy"/></svg>',
      );
      fixture.innerHTML = `<img data-cy="plain-image" src="${source}" width="400" height="200" alt="Test rectangle">
        <table style="width:100px;table-layout:fixed"><tbody><tr><td style="height:20px">
          <img data-cy="table-image" src="${source}" width="400" height="200" alt="Test rectangle">
        </td></tr></tbody></table>`;
      doc.body.appendChild(fixture);
    });
    for (const selector of ['plain-image', 'table-image']) {
      cy.get<HTMLImageElement>(`[data-cy="${selector}"]`).should(images => {
        const image = images[0];
        expect(image.naturalWidth).to.equal(400);
        const bounds = image.getBoundingClientRect();
        expect(bounds.width).to.be.greaterThan(0).and.at.most(160);
        expect(bounds.width / bounds.height).to.be.closeTo(2, 0.01);
      });
    }
  });
});
