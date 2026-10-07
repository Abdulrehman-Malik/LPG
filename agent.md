# Agent Reference

- **issue_cylinder.md** is the authoritative implementation reference for Security Deposit / Issue Cylinder and Cylinder Return / Refund Deposit workflows.
- Read `issue_cylinder.md` before changing POS custody, security-deposit, cylinder-return, return-gas, or related stock/ledger behavior.
- Preserve atomic posting, physical cylinder identity, customer custody history, separate deposit balance, and server-side validation.
- Update relevant documentation/test cases when changing these workflows.
- Project owner requires implementation directly on `main`.

## Transaction-Type Isolation Rule — MUST FOLLOW

- Each POS transaction type has its **own UI layout, rendering logic, validation flow, and business-flow boundary**.
- A change requested for one transaction type MUST NOT alter, break, hide, resize, or change the business behavior of another transaction type.
- Do not share transaction-specific DOM/CSS selectors, calculations, validation, or payment behavior unless the shared component is explicitly designed to be type-neutral.
- When modifying one transaction type, regression-test **all four POS transaction types**: Gas Sale / Refill, Cylinder Sale, Security Deposit / Issue Cylinder, and Cylinder Return / Refund Deposit.
- Keep transaction-specific containers and mode classes isolated so changes remain scoped to the selected transaction type.
- Any intentional cross-type change must be explicitly documented in `issue_cylinder.md` and regression-tested.

## POS Layout Rule

- Preserve a responsive, non-overflowing layout for every transaction type.
- Never introduce a fixed table width, hidden column, or global CSS rule that reserves space for fields not displayed for the current transaction type.

## Runtime QA Rule — MUST FOLLOW

- Whenever a fix, feature, layout change, JavaScript change, backend change, or other modification is made to any screen, automatically identify **all impacted screens and workflows** and execute the **LPG Runtime QA** workflow for those screens.
- The user does **not** need to explicitly ask for QA. Running the appropriate runtime QA is mandatory after every screen-level change.
- Runtime QA must exercise the actual affected screen through the browser, including relevant controls, navigation, interactions, validation, and impacted business flows—not only static syntax or code checks.
- If Runtime QA finds any failure, JavaScript error, UI issue, broken interaction, regression, or unexpected behavior, fix it and run the impacted Runtime QA again.
- Continue the fix → runtime QA → fix cycle until the impacted screen/workflow works correctly and the relevant Runtime QA passes.
- For changes affecting multiple screens, run QA coverage for **each impacted screen**, plus regression coverage for directly related workflows.
- Do not report a screen-level fix as complete until the corresponding impacted-screen Runtime QA has passed.
- Never rely on a previous QA result from an older commit when the current change could affect the tested screen; QA must validate the current commit.
