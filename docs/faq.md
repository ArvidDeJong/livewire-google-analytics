---
title: FAQ
nav_order: 8
description: "Short answers about darvis/livewire-google-analytics: what it sends, what it does not load, blocked trackers, double events, safety and testing."
faq: true
---

# Frequently asked questions

{% for item in site.data.faq %}
## {{ item.q }}

{{ item.a | markdownify }}
{% endfor %}
