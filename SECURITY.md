# Security policy

This package passes values from your Livewire components to JavaScript in the visitor's browser. A
way to make an event name or a parameter run as script counts as a security issue. That includes the
events the view writes into its script after a redirect: they are JSON with every tag character and
quote escaped, and a way around that escaping is a vulnerability.

## What the package stores in the browser

One `sessionStorage` key, `livewire-google-analytics.sent`: a JSON list of at most 100 random ids of
events that were carried over a redirect. No event name, no parameter and nothing about the visitor
is stored there, and the package sets no cookie. A way to make event data end up in browser storage
counts as a security issue.

## Supported versions

Only the latest minor release of 1.x receives security fixes. Upgrade before reporting.

## Reporting a vulnerability

Please do **not** open a public issue. Report it privately instead:

- via [GitHub private vulnerability reporting](https://github.com/ArvidDeJong/livewire-google-analytics/security/advisories/new), or
- by email to info@arvid.nl.

Include the package version, the Livewire and Laravel versions and the steps that show the problem.

You will get a reply within a week. Once a fix is released, the advisory is published and you are
credited, unless you prefer not to be.

## Out of scope

- Any script on the page can dispatch a `ga:event` browser event, and the listener forwards it. Such
  a script can also call `gtag()` directly, so the listener gives it nothing new. Events in Google
  Analytics are sent from the browser and can always be forged by a visitor.
- A host application that makes a tracking method public, or wraps one in a public Livewire method
  with free arguments. The trait methods are protected for that reason.
- What you put in the parameters. Keeping personal data out of Google Analytics is up to the host
  application.
- Google's own tag, `gtag.js`, consent handling and the measurement id. The package does not load or
  configure any of them.
