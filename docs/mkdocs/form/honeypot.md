# HoneypotType Field

The `HoneypotType` field is a text field being rendered invisible in your forms. It will raise a form validation error
if any non-empty value is submitted.

A [honeypot](https://en.wikipedia.org/wiki/Honeypot_(computing)) is a concept in computing that exposes seemingly valid
application elements, without documenting or showing them to human users. Therefore their usage is a reliable indication
of abuse by scripts, bots or other malicious agents. In spam recognition, they are considered a powerful tool to detect
forms being filled in automatically by automated agents.

!!! tip
    The full list of options defined and inherited by this form type is available running this command in your app:
    ```shell
    # replace 'FooType' by the class name of your form type
    $ php bin/console debug:form FooType
    ```

## Basic Usage

The usual way is to enable it for a [profile](../configuration.md#honeypot), which injects the field in every form using the profile:

```yaml title="config/packages/antispam.yaml"
antispam:
    profiles:
        default:
            honeypot: email_address
```

The field gets the configured name, with a number appended if the form already has a field by that name. Name it after something a bot is tempted to fill in, like `email_address`, `website` or `phone`.

The field can also be added to a form manually, without a profile:

```php
use Omines\AntiSpamBundle\Form\Type\HoneypotType;

$builder->add('email_address', HoneypotType::class);
```

The field is not mapped to your data object, and is rendered as a text input hidden with the inline style `display:none`. A submission with a non-empty value is rejected with the error `form.honeypot.not_empty`, which can be [customized](../index.md#can-i-customize-the-error-messages) in the translation domain `antispam`.

!!! warning "Errors are added to the field, which is hidden"
    A rejected submission can only show its error at form level when the honeypot is used through a profile with `stealth` enabled. When using the field manually make sure its errors are displayed somewhere, as the field itself is not visible.

## Options

### `attr`

**type**: `array<string, string>` **default**: `{ style: 'display:none' }`

HTML attributes rendered on the field. Setting this option replaces the default, so include the `style` yourself if you still want the field hidden inline, or hide it with a CSS class instead. When configured through a profile use its [`attributes`](../configuration.md#attributes) option.

### `mapped`

**type**: `boolean` **default**: `false`

The field must not be mapped to a property of your data object, so this defaults to `false`.
