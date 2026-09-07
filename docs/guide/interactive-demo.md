---
description: See how a Symfony filter form becomes Doctrine conditions, step by step.
---

# Interactive demo

This small, client-side simulation illustrates the path taken by the bundle: define a filter form, submit its rendered HTML fields, then let `FilterBuilderUpdater` add conditions to a Doctrine query builder.

It does not execute PHP or connect to a database. Its purpose is to make the relationship between the form, DQL and result set immediately visible. You can play the walkthrough or change the fields yourself.

<FilterDemo />

The real bundle keeps the same division of responsibilities: Symfony handles form submission and validation; the bundle turns active filter fields into query conditions; Doctrine binds the parameters and runs the query.
