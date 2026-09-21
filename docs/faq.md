---
title: "FAQ"
nav_order: 9
description: "Short answers about darvis/livewire-google-analytics: what it is, what it does not load, events before consent, redirects, double events, safety and testing."
faq: true
---

# Frequently asked questions

{% for item in site.data.faq %}
## {{ item.q }}

{{ item.a | markdownify }}
{% endfor %}
