/// <reference types="cypress" />

// custom command declaration
declare namespace Cypress {
  interface Chainable {
    /**
     * Command to log in to the app as Toto Le sysadmin by default but can be someone else
     * The session is cached with cy.session(), so only the first call per user and per spec actually logs in
     * @return csrf-token
     */
    login(email?: string, password?: string): Cypress.Chainable;
  }
}
