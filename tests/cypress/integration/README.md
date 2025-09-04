# Cypress End-to-End Tests

This folder contains all Cypress end-to-end (E2E) tests for eLabFTW. You will find here good practices we use on the project.

---

## `data-cy` Attributes

To make our tests **reliable and easy to maintain**, we use custom `data-cy` attributes in our Twig templates.
These attributes give Cypress tests a stable way to query elements, without relying on IDs, CSS classes, or DOM structure in general (which are likely to change for styling/UX reasons).

### Example in real-case

Twig template:
```html
<!-- Instead of targeting .btn or #submit -->
<button class='btn btn-primary' id='submit' data-cy='submit-button'>
  Submit
</button>
```

Cypress related test:
```typescript
// Bad: brittle selector, may break if class or id changes
cy.get('.btn-primary').click();

// Good: stable selector, tied to test intent
cy.get('[data-cy=submit-button]').click();
```
