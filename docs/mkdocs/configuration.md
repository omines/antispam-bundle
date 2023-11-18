# Configuration

When installing the bundle, the Symfony Flex recipe will add a default config file to use as a starting point at
`config/packages/antispam.yaml`. The file is yours to change according to your needs.

The bundle configuration is for the most part self-documented, and the annotated [default configuration](#default-config)
can be viewed from the Symfony console with:
```shell
bin/console config:dump-reference antispam
```

Note that you can also view the resolved configuration during development:
```shell
bin/console debug:config antispam
```
The fields below the `antispam` bundle root key are detailed below.

## `enabled`

**type**: `boolean` **default**: `true`

Allows you to enable or disable the entire bundle. Its main purpose is to be able to [disable the bundle during
functional testing](advanced.md#testing-your-application), where it may interfere with, or complicate, your test cases.

## `passive`

**type**: `boolean` **default**: `false`

Default [passive setting](features.md#passive-mode) for *all* validators and form types of the bundle.

## `quarantine`

When forms have been validated, they can be put in quarantine for analysis and logging purposes. Quarantine also allows
you to review false positives and, if `only_spam` is disabled, false negatives.

## `stealth`

**type**: `boolean` **default**: `false`

Default [stealth setting](features.md#stealth-mode) for *all* validators and form types of the bundle.

??? tip "On default global and profile `stealth` settings"
    The global and profile defaults for `stealth` are different on purpose. The global setting is applied to  validators
    and form types used separately, and will therefore default to acting like an actual validator, displaying the precise
    error in the right place. Within a profile they become part of a larger antispam measure, and are therefore stealthed,
    merging them together as a generic rejection message.

## `profiles`

Keyed map of [profiles](features.md#profiles) to be used throughout your application. Use profiles to cater for different use cases, as you
may want to apply different antispam measures to a contact form than a registration form, which may both differ from
a comment section form.

Each profile supports the following options:

### `stealth`

**type**: `boolean` **default**: `true`

By default [stealth mode](features.md#stealth-mode) is enabled for all forms. Change this property to disable it
specifically per profile.

### `passive`

**type**: `boolean` **default**: `null`

You can enable or disable [passive mode](features.md#passive-mode) explicitly per form profile, or leave it at `null`
to follow the global setting.

## Default config

```yaml
--8<-- "includes/default-config.yaml"
```
