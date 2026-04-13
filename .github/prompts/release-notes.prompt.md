---
description: "Generate release notes and changelog updates for this package using conventional structure and repository docs."
name: "Generate Package Release Notes"
argument-hint: "Version and highlights, e.g. 1.2.0 add purchase tracking helper"
agent: "agent"
---

Create release notes for darvis/livewire-google-analytics based on the provided version and highlights.

Requirements:

- Update `CHANGELOG.md` in existing style with date and sections (`Added`, `Changed`, `Fixed`, `Security`) as relevant.
- Keep entries concise, factual, and user-facing.
- Maintain backward-compatible framing unless explicitly marked breaking.
- Ensure version support statements match `composer.json` constraints.
- If tests/documentation are affected, mention them in release notes.

Before finalizing:

- Verify consistency with [README.md](../../README.md), [docs/03-basic-usage.md](../../docs/03-basic-usage.md), and [CHANGELOG.md](../../CHANGELOG.md).
- Prefer linking to docs topics instead of duplicating long explanations.

Output:

1. Proposed `CHANGELOG.md` diff-ready text.
2. Short release summary (3-5 bullets) suitable for GitHub release notes.
