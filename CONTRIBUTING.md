# Contributing

## Workflow (mandatory)
1. Never commit or push directly to `main`.
2. Create a feature branch first: `git switch -c feature/<short-name>` (also `fix/`, `chore/`, `docs/`).
3. Commit and push the branch: `git push -u origin <branch>`.
4. Open a pull request into `main`.
5. Merge only after both CI checks pass: **Lint and static checks** and **Database and PHPUnit**.

## One-time setup after cloning
Enable the shared git hooks, which block direct pushes to `main`:

    git config core.hooksPath .githooks

## Notes
- Never commit `.env` files or credentials; CI fails if one is tracked.
- GitHub branch protection is unavailable on the current plan, so this workflow is enforced by the local hook and review discipline.
