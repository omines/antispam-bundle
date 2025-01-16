# Symfony Anti-Spam Bundle

[![Latest Stable Version](https://poser.pugx.org/omines/antispam-bundle/version)](https://packagist.org/packages/omines/antispam-bundle)
[![Latest Unstable Version](https://poser.pugx.org/omines/antispam-bundle/v/unstable)](https://packagist.org/packages/omines/antispam-bundle)
[![Total Downloads](https://poser.pugx.org/omines/antispam-bundle/downloads)](https://packagist.org/packages/omines/antispam-bundle)
[![License](https://poser.pugx.org/omines/antispam-bundle/license)](https://packagist.org/packages/omines/antispam-bundle)

[![automated-testing](https://github.com/omines/antispam-bundle/actions/workflows/ci.yaml/badge.svg?branch=master&event=push)](https://github.com/omines/antispam-bundle/actions/workflows/ci.yaml)
[![codecov](https://codecov.io/gh/omines/antispam-bundle/graph/badge.svg?token=634ZQ8EQ7A)](https://codecov.io/gh/omines/antispam-bundle)
[![phpstan](https://img.shields.io/badge/PHPStan-max-brightgreen)](https://github.com/omines/antispam-bundle/blob/master/phpstan.neon)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fomines%2Fantispam-bundle%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/omines/antispam-bundle/master)

You have found the Swiss Army Knife of battling form spam in your Symfony application!

This bundle provides a ton of different mechanisms for detecting and stopping spammers, scammers and abusers
using your forms for their nefarious purposes, and brings them all together in an easy to configure profile system.

This bundle is compatible with PHP 8.2+ and Symfony 6.4 LTS or 7.1+.

## Documentation

Extensive documentation can be found at https://omines.github.io/antispam-bundle/

## What does it do

This bundle provides you with a ton of methods to easily combat spam through tested and proven methods:

- *Honeypot*: hidden field to bait spammers
- *Timer*: Rejects forms being submitted too fast or too slow
- *Banned scripts*: Reject forms containing characters in specific scripts (ie. Cyrillic or Arabic)
- *Banned markup*: Rejects forms containing (attempts at) HTML or BBCode
- *Banned phrases*: Reject forms containing predefined phrases
- *URL Count*: Reject forms with too many URLs or repeated URLs in the content

All components can either be used standalone or deployed through easily configured antispam profiles.

[Read more in the documentation](https://omines.github.io/antispam-bundle/#features).

## Development

This bundle can be considered stable, and the current API is not likely to change as we prepare for
releasing version 1.0.

## Contributing

Please see [CONTRIBUTING.md](https://github.com/omines/antispam-bundle/blob/master/.github/CONTRIBUTING.md) for details.

## Legal

This software was developed for internal use at [Omines Full Service Internetbureau](https://www.omines.nl/)
in Eindhoven, the Netherlands. It is shared with the general public under the permissive MIT license, without
any guarantee of fitness for any particular purpose. Refer to the included `LICENSE` file for more details.
