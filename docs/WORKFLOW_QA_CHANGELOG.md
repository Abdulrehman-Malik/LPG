# Workflow QA Change Log

## 2026-10-04 — Dedicated QA branch

Branch: `qa/workflow-cleanup`

### Latest failure reviewed
- Runtime QA run: `37197973474`
- Browser job: `111423662863`
- Database bootstrap, PHP setup, web startup, and HTTP smoke tests passed.
- Browser QA failed with `chrome-error://chromewebdata/`.
- Chromium installation succeeded.
- Server log showed PHP remained healthy and accepted connections.

### Changes made
1. Added this dedicated branch so workflow/runtime fixes remain isolated from `main`.
2. Added a minimal Chromium-to-localhost probe using Python's static HTTP server on port 8090.
3. Changed the PHP development server bind address from `127.0.0.1` to `0.0.0.0` for runner/browser compatibility.
4. Changed browser target from `127.0.0.1` to `localhost`.
5. Explicitly disabled Chromium proxy resolution and removed proxy environment variables.
6. Added browser screenshots and probe logs to the workflow artifact.
7. Updated `actions/checkout` from v4 to v5 to remove the GitHub Actions Node.js 20 warning.
8. Added the new branch to the workflow push trigger.

### Application-code policy
No application PHP, JavaScript, CSS, database schema, controller, model, view, or business-logic files were changed. All changes are QA/workflow configuration only.

### Next verification
Run the complete Runtime QA workflow on this branch. The loopback probe will distinguish a Chromium/runner networking problem from a PHP/CodeIgniter browser-request problem.

## Follow-up: Probe setup correction

The first isolated probe attempt exposed a QA-script issue: the probe ran in a separate workflow step before the temporary Playwright directory was created. The probe step now installs Playwright/Chromium itself before testing loopback, making the diagnostic self-contained.
