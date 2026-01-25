# Git Guidelines

## Branch Naming

Branches must follow this format:
```
YYYYMMDD-summary-words
```

- Date in YYYYMMDD format (e.g., 20260114 for 14 January 2026)
- Summary is 1-4 words, lowercase, hyphen-separated
- Examples: `20260114-smoke-tests`, `20260115-add-channel-filter`

### Commit Messages

- Never reference Claude, AI assistants, or any AI tools in commit messages
- Commit messages should be concise and no more than 72 characters on a single line.

### Pull Requests

PRs should include:
- **Title**: Concise summary of changes (max 50 characters) prefixed with the same "YYYYMMDD - " from the branch name
- **Summary**: Brief description of changes (bullet points)
- **Test plan**: How to verify the changes work
- **Screenshots**: If UI changes are involved

## Protected branches

You must never commit directly to the `main` branch. 

Development must always be committed to a new branch of work that is not `main`.
