---
title: Rationale
hide: navigation
---

# Why LighthousePHP?

Every tool is born from a question. **LighthousePHP** was born from this one:

> *Why does building a simple website require so much complexity today?*

## The modern web, the hard way

If you start a new PHP project today, you're expected to install Composer,
pick a framework, learn its conventions, configure a router, choose an ORM,
wire up a template engine... and that's before writing a single line of
business logic.

For many projects — a portfolio, a small business site, a personal app — this
overhead is simply not justified.

## Back to basics (but not backwards)

Before MVC frameworks took over, PHP worked differently: a file was a page,
a page was a file. No magic, no indirection. You knew exactly what was
happening and where.

**LighthousePHP** takes that simplicity seriously — not out of nostalgia, but
because **simplicity is a feature**. A codebase you can read, understand and
debug without a map is a codebase you can maintain.

## One tool, end to end

Fragmented knowledge is one of the biggest hidden costs of modern web
development. You learn React, then you learn how to integrate it with your
backend, then you learn why they don't play well together, then you look for a
workaround...

**LighthousePHP** ships with a single CSS layer (`default.css`) and a single
interaction layer (`default.js`) — both minimal, both documented, both yours
to understand completely.

But if you want, you can opt-out and substitute them with your preferred tools.

## Know your database

The database is one of the most common bottlenecks in web applications — and
also one of the most abstracted away. ORMs are convenient, but they put a
layer of indirection between you and the most performance-critical part of
your stack. When something is slow, you're left debugging generated queries
you didn't write and don't fully control.

**LighthousePHP** encourages you to write SQL directly. Not because it's
trendy, but because understanding even the basics — how a `JOIN` works, when
an index matters, what a query actually costs — gives you the power to
optimize instead of just hoping the ORM makes the right call.

That said, repetitive tasks shouldn't be tedious. **LighthousePHP** provides
a set of helpers to handle the most common database operations without
ceremony, so you stay productive without losing sight of what's happening
underneath.

## Performance as a default, not an afterthought

The name is not accidental. **LighthousePHP** is designed from the ground up
to help you hit 100/100 on Google Lighthouse — not by hiding complexity, but
by making the right choices the default ones.

---

If this philosophy resonates with you, you're in the right place.