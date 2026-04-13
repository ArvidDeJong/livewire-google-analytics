---
description: "Use when writing or updating Pest feature tests for Livewire analytics tracking in this package. Covers event dispatch assertions, fixture style, and minimal regression-safe scope."
name: "Livewire Analytics Test Conventions"
applyTo: "tests/Feature/**/*.php"
---

# Livewire Analytics Test Conventions

- Keep tests in Pest style and aligned with existing patterns in [tests/Feature/TracksAnalyticsTest.php](../../tests/Feature/TracksAnalyticsTest.php).
- Prefer behavior assertions with `Livewire::test(...)->call(...)` and `->assertDispatched('ga:event', ...)`.
- For small fixtures, inline Livewire test components are acceptable when they keep tests focused and readable.
- When behavior changes, update or add tests in `tests/Feature/` in the same change.
- Preserve backward compatibility unless a breaking change is explicitly requested.

## Scope Guardrails

- Do not add network-bound or GA external integration tests.
- Keep assertions centered on package behavior (event name and params), not implementation internals.
- Avoid broad refactors in tests that are unrelated to the changed behavior.

## References

- Testing docs: [docs/05-testing.md](../../docs/05-testing.md)
- Troubleshooting: [docs/06-troubleshooting.md](../../docs/06-troubleshooting.md)
- Contribution workflow: [CONTRIBUTING.md](../../CONTRIBUTING.md)
