# SubmitTimerType Field

The `SubmitTimerType` field is a hidden field that records when a form was rendered, and rejects the submission if the time between rendering and submitting is outside of human boundaries.

Many spambots submit a form within a second of retrieving it, as they are in a rush to attack millions of other sites. Others store the form and submit it hours, days or weeks later. Both behaviors are rare for real visitors.

The field value is signed with a hash over the timestamp, the client IP address and a secret, so it cannot be forged or altered by a bot. The submission is rejected if:

- the value is missing, cannot be decoded or fails the signature check (`form.timer.corrupted`);
- the client IP address changed between rendering and submitting (`form.timer.mismatch_ip`);
- the form was submitted faster than `min` seconds (`form.timer.too_fast`);
- the form was submitted slower than `max` seconds (`form.timer.too_slow`).

The error messages can be [customized](../index.md#can-i-customize-the-error-messages) in the translation domain `antispam`.

## Basic Usage

The usual way is to enable it for a [profile](../configuration.md#timer), which injects the field in the root form:

```yaml title="config/packages/antispam.yaml"
antispam:
    profiles:
        default:
            timer:
                min: 3
                max: 3600
```

The timer is only added to the root form. For an embedded form using a profile with a `timer` configuration it is ignored, and an informational message is logged to the `antispam` channel.

The field can also be added to a form manually, without a profile:

```php
use Omines\AntiSpamBundle\Form\Type\SubmitTimerType;

$builder->add('_validation', SubmitTimerType::class, [
    'min' => 5,
    'max' => 1800,
]);
```

!!! warning "Errors are added to the field, which is hidden"
    A rejected submission can only show its error at form level when the timer is used through a profile with `stealth` enabled. When using the field manually make sure the errors of the field are displayed somewhere, as the field itself is not visible.

## Options

### `min`

**type**: `int|float` **default**: `3`

Minimum number of seconds that must have passed between rendering and submitting the form.

### `max`

**type**: `int|float` **default**: `3600`

Maximum number of seconds that may have passed between rendering and submitting the form.

### `secret`

**type**: `string` **default**: the `kernel.secret` parameter

Secret used to sign the field value. Changing it invalidates all forms that were rendered before the change.

!!! tip
    The full list of options defined and inherited by this form type is available running this command in your app:
    ```shell
    $ php bin/console debug:form SubmitTimerType
    ```
