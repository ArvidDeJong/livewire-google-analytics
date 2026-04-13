---
description: "Check and align package docs when API, behavior, or support matrix changes."
name: "Docs Sync Check"
argument-hint: "Describe change, e.g. add new tracking helper + Laravel support update"
agent: "agent"
---

Review documentation consistency for darvis/livewire-google-analytics after a code change.

Check these files:

- [README.md](../../README.md)
- [QUICK_START.md](../../QUICK_START.md)
- [docs/02-installation.md](../../docs/02-installation.md)
- [docs/03-basic-usage.md](../../docs/03-basic-usage.md)
- [docs/04-examples.md](../../docs/04-examples.md)
- [docs/05-testing.md](../../docs/05-testing.md)
- [docs/06-troubleshooting.md](../../docs/06-troubleshooting.md)
- [examples/README.md](../../examples/README.md)

Tasks:

- Identify mismatches with current behavior and `composer.json` constraints.
- Propose minimal edits to keep docs accurate and non-duplicative.
- Keep wording concise and beginner-friendly.
- Preserve existing structure and style of each document.

Output:

1. A checklist of mismatches found.
2. Exact patch proposals per file.
3. A final "docs are in sync" confirmation list.
