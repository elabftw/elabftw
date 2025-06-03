export const interceptEntityApi = (endpoint: string) => {
  cy.intercept('GET', `/api/v2/${endpoint}/**`).as('apiGET');
  cy.intercept('POST', `/api/v2/${endpoint}/**`).as('apiPOST');
  cy.intercept('PATCH', `/api/v2/${endpoint}/**`).as('apiPATCH');
  cy.intercept('DELETE', `/api/v2/${endpoint}/**`).as('apiDELETE');
};
