# About
[![Latest Stable Version](https://poser.pugx.org/omines/antispam-bundle/version)](https://packagist.org/packages/omines/antispam-bundle)
[![Total Downloads](https://poser.pugx.org/omines/antispam-bundle/downloads)](https://packagist.org/packages/omines/antispam-bundle)
[![Latest Unstable Version](https://poser.pugx.org/omines/antispam-bundle/v/unstable)](//packagist.org/packages/omines/antispam-bundle)
[![License](https://poser.pugx.org/omines/antispam-bundle/license)](https://packagist.org/packages/omines/antispam-bundle)

You have found the Swiss Army Knife of battling form spam in your Symfony application!

This bundle provides a ton of different mechanisms for detecting and stopping spammers,
scammers and abusers using your forms for their nefarious purposes, and brings them
all together in an easy to configure profile system.

This bundle is compatible with PHP 8.1+ and Symfony 6.3 or later.

## Features

This bundle provides you with a ton of methods to easily combat spam through tested and
proven methods:

- **[Honeypot](form/honeypot.md)**: Insert a hidden field in your forms that lures spambots into filling it in.
- **[Timer](form/submit_timer.md)**: Reject forms that have been submitted unfeasibly fast or unrealistically slow.
- **Max URLs**: Reject text fields that contain more hyperlinks than plausible.
- **Banned markup**: Reject text fields containing HTML or UBB tags.
- **Banned phrases**: Reject text fields containing signature phrases targeting your site.
- **Banned scripts**: Reject text fields that contain too many characters in scripts not
    expected by the site owners, like Cyrillic (Russian), Chinese or Arabic.

It wraps all these methods in an easy to use and easy to apply profile system, allowing
you to pick and match per form what methods are required.

### Global features

All components support *stealth mode*, which hides verbose errors showing the rejection
reasons, and instead replaces them with a generic catch-all error at the form level.

All components can run in *passive mode*, in which they do not actually block submission
but otherwise do all logging and escalation as if they are. This enables you to evaluate
impact before releasing invasive actions.

All validators are implemented as regular Symfony constraints with attributes, meaning
you can also apply them to your Doctrine entities, API classes and everything.

## Installation

Install the bundle:
```shell
composer require omines/antispam-bundle
```

Symfony Flex will enable the bundle and provide a basic configuration file with samples
at `config/packages/antispam.yaml`. With the default config no invasive actions are enabled.

!!! tip
    Head over to the [Quickstart](quickstart.md) to have your spam protection up and running
    within 5 minutes!

## Frequently Asked Questions

### Why is there no way to enable a profile globally for all forms?

Because it's very dangerous and you're not likely to *really* want this.

Plugging spam protection into all forms of your application is very well possible,
but we cannot distinguish between forms in your contact form, CMS, customer portals
and login forms. Accidentally enabling antispam methods that block HTML, foreign
characters or slow form entry could be destructive to the user experience of your
CMS, in which all those things are likely normal. So no, we do not provide an option
that  allows you to shoot your own foot in a way that will at some point in the future
cause tons of unforeseen drama.

### Why not a stable version number?

As a matter of principle we [eat our own dog food](https://en.wikipedia.org/wiki/Eating_your_own_dog_food),
so we use this bundle internally on multiple projects. When putting it out there however it
comes with the territory that feedback points out unforeseen issues. So we keep the major
version at 0 until we feel sufficiently confident that the core API, DX and mechanisms
are stable.
