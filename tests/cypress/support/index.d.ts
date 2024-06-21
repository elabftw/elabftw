/// <reference types="cypress" />

// custom command declaration
declare namespace Cypress {
  interface Chainable {
    /**
     * Command to log in to the app as Toto Le sysadmin by default but can be someone else
     * @return csrf-token
     */
    login(email?: string, password?: string): Cypress.Chainable;
    /**
     * Sends necessary headers to get codecoverage via codeceptions c3.php.
     */
    enableCodeCoverage(testFile: string): void;
  }
}
