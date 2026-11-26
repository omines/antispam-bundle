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

### `honeypot`

Injects a [Honeypot](form/honeypot.md) field in every form using the profile. Omit the key to not inject one. A string is accepted as shorthand for `field`:

```yaml
antispam:
    profiles:
        default:
            honeypot: email_address
```

#### `field`

**type**: `string` **required**

Base name of the injected field. If the form already has a field with that name a number is appended until the name is unique, so `email_address` becomes `email_address1`.

#### `attributes`

**type**: `array<string, string>` **default**: `{ style: 'display:none' }`

HTML attributes rendered on the honeypot field. Keys and values must be strings. Setting this option replaces the default, so include `style: 'display:none'` yourself if you still want the field hidden inline, or hide it with a CSS class instead:

```yaml
antispam:
    profiles:
        default:
            honeypot:
                field: email_address
                attributes:
                    class: visually-hidden
                    autocomplete: 'off'
                    tabindex: '-1'
```

### `timer`

Injects a [Submit Timer](form/submit_timer.md) field in every form using the profile. Omit the key to not inject one. The timer is only added to the root form, and ignored for embedded forms.

#### `field`

**type**: `string` **default**: `_validation`

Base name of the injected field. Made unique in the same way as the honeypot field name.

#### `min`

**type**: `float` **default**: `3`

Minimum number of seconds between rendering and submitting the form, must be `0` or more. Forms submitted faster are rejected.

#### `max`

**type**: `float` **default**: `3600`

Maximum number of seconds between rendering and submitting the form, must be `60` or more. Forms submitted later are rejected.

### `banned_markup`

Applies the [BannedMarkup](validator/banned_markup.md) validator to all text fields of forms using the profile. Omit the key to not validate markup. `banned_markup: true` enables it with the defaults below.

#### `html`

**type**: `boolean` **default**: `true`

Reject values containing tags resembling HTML.

#### `bbcode`

**type**: `boolean` **default**: `true`

Reject values containing tags resembling BBCode.

### `banned_phrases`

Applies the [BannedPhrases](validator/banned_phrases.md) validator to all text fields of forms using the profile. Omit the key to not validate phrases. A plain list is accepted as shorthand for `phrases`:

```yaml
antispam:
    profiles:
        default:
            banned_phrases: ['viagra', 'cialis']
```

#### `phrases`

**type**: `array<string>` **required**, at least one element

Phrases that are rejected when found in a value. Matching is case-insensitive and also finds the phrase inside longer words.

### `banned_scripts`

Applies the [BannedScripts](validator/banned_scripts.md) validator to all text fields of forms using the profile. Omit the key to not validate scripts. A plain list is accepted as shorthand for `scripts`:

```yaml
antispam:
    profiles:
        default:
            banned_scripts: ['cyrillic', 'arabic']
```

#### `scripts`

**type**: `array<string>` **required**, at least one element

ISO 15924 script names, matched case-insensitively. For valid values refer to [`Script.php`](https://github.com/omines/antispam-bundle/blob/master/src/Type/Script.php).

#### `max_percentage`

**type**: `float` **default**: `0`, between `0` and `100`

Maximum percentage of characters in the banned scripts allowed in a value. With `0` any such character fails validation, unless `max_characters` is set.

#### `max_characters`

**type**: `int|null` **default**: `null`

Maximum number of characters in the banned scripts allowed in a value. When combined with `max_percentage`, a value fails as soon as either limit is exceeded.

### `url_count`

Applies the [UrlCount](validator/url_count.md) validator to all text fields of forms using the profile. Omit the key to not validate URL counts. An integer is accepted as shorthand for `max`:

```yaml
antispam:
    profiles:
        default:
            url_count: 2
```

#### `max`

**type**: `int|null` **default**: `null`

Maximum number of URLs accepted per text field.

#### `max_identical`

**type**: `int|null` **default**: `null`

Maximum number of times the same URL is accepted per text field.

## Default config

```yaml
--8<-- "includes/default-config.yaml"
```
