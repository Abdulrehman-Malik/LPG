# Agent Reference

- **issue_cylinder.md** is the authoritative implementation reference for Security Deposit / Issue Cylinder and Cylinder Return / Refund Deposit workflows.
- Read `issue_cylinder.md` before changing POS custody, security-deposit, cylinder-return, return-gas, or related stock/ledger behavior.
- Preserve atomic posting, physical cylinder identity, customer custody history, separate deposit balance, and server-side validation.
- Update relevant documentation/test cases when changing these workflows.
- Project owner requires implementation directly on `main`.
