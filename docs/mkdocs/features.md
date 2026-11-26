# Features

## Profiles

A **profile** is a named set of antispam measures, defined under `antispam.profiles` in your [configuration](configuration.md#profiles). You apply a profile to a form with the `antispam_profile` option, either when creating the form or in the defaults of its type:

```php
$form = $this->createForm(ContactForm::class, options: [
    'antispam_profile' => 'contact',
]);
```

The option also accepts a `Profile` instance, which you can retrieve from the `AntiSpam` service with `getProfile()`. An unknown profile name throws an `InvalidProfileException`.

A profile applies its measures as follows:

- The profile only takes effect on compound forms, and only when the bundle is [enabled](configuration.md#enabled).
- The [honeypot](form/honeypot.md) is added to the form the profile is applied to, the [submit timer](form/submit_timer.md) only when that form is the root form.
- The validators configured in the profile (`banned_markup`, `url_count`, `banned_phrases` and `banned_scripts`) are applied to the direct children of the form that are a `TextType` or one of its subtypes, such as `TextareaType`. Other field types and the fields of embedded forms are not touched, unless the embedded form has the profile applied itself.
- The validators run in a `Sequentially` constraint ordered from cheap to expensive, so only the first violation of a field is reported.
Profiles can also differ in their handling of failures through their [`stealth`](configuration.md#stealth_1) and [`passive`](configuration.md#passive_1) settings.

## Stealth Mode

**Stealth Mode** is used to mask the actual reason a form or field value was rejected.

The way Symfony's form and validator mechanisms work together is counterintuitive for how we expect antispam measures
to work, as their errors are really verbose, informative and user friendly. That means the default validation failures
are, in a way, giving hints to the spammers on how to bypass the antispam measures. That sounds like a bad idea.

By default all profiles have `stealth` **enabled**. This means all errors on fields and forms are replaced by a single
generic error on the form level, by default stating that *'technical issues'* have prevented the form from being
processed. You can disable `stealth` at the profile level to keep explicit errors at the violating fields.

When using the bundle components without the profile system, they follow the global `stealth` setting at the root of
the configuration. It is **disabled** by default.

## Passive Mode

**Passive Mode** makes the bundle components work without actually rejecting input.

When enabled all validators and other components still generate errors as usual, following `stealth` rules where
enabled. At the end of the process all errors are dropped, not hampering successful validation.

All logging is still present, all events are still dispatched. This allows you both to evaluate form
filtering rules, and to implement custom behavior such as *"fake pass"*.

By default `passive` is **disabled** both at the form and component level.

## Quarantine

Quarantine was removed from the base bundle and is left up to the implementor via [events](#events).

## Forms

The bundle provides two form types, which a [profile](#profiles) injects for you but which can also be added to any form manually:

- **[HoneypotType](form/honeypot.md)**: a hidden text field that fails the form when it is filled in.
- **[SubmitTimerType](form/submit_timer.md)**: a hidden signed field that fails the form when it is submitted too fast or too slow.

Both are rendered through the form theme `@AntiSpam/form/widgets.html.twig`, which the bundle registers automatically when TwigBundle is installed. It defines the blocks `antispam_honeypot_row` and `antispam_submit_timer_row`, which render only the widget, without label or errors. To change how the fields are rendered, override these blocks in your own form theme.

## Validators

The bundle provides four validators, implemented as regular Symfony constraints:

- **[BannedMarkup](validator/banned_markup.md)**
- **[BannedPhrases](validator/banned_phrases.md)**
- **[BannedScripts](validator/banned_scripts.md)**
- **[UrlCount](validator/url_count.md)**

They can be applied to your own classes with attributes, YAML, XML or PHP like any other constraint, and are applied automatically to text fields by a [profile](#profiles). Like Symfony's own constraints they consider `null` valid. All of them support the `passive` and `stealth` options to override the [global setting](configuration.md) for a single constraint.

## Events

The bundle dispatches events that allow you to react to detected spam, for example to log submissions to a database, notify someone or quarantine messages. The event names are defined as constants in `Omines\AntiSpamBundle\AntiSpamEvents`, and the event class names can also be used as event names in listeners and subscribers.

| Constant                              | Event class               | Cancellable | Dispatched                                                          |
|---------------------------------------|---------------------------|-------------|---------------------------------------------------------------------|
| `AntiSpamEvents::FORM_PROCESSED`      | `FormProcessedEvent`      | No          | After every submission of a form that has a profile                 |
| `AntiSpamEvents::FORM_VIOLATION`      | `FormViolationEvent`      | Yes         | After a submission of a form with a profile that violated any rule  |
| `AntiSpamEvents::VALIDATOR_VIOLATION` | `ValidatorViolationEvent` | Yes         | When one of the bundle's validators finds a violation               |

Cancelling a `FormViolationEvent` removes all antispam errors from the form, so the submission is treated as valid. Cancelling a `ValidatorViolationEvent` means the validator does not add a violation. This is exactly how [passive mode](#passive-mode) is implemented, by a subscriber running at priority `-512`. Listeners that need to run before or after passive mode handling should choose their priority accordingly.

`FormProcessedEvent` and `FormViolationEvent` give access to an `AntiSpamFormResult` through `getResult()`, which exposes:

- `getForm()` and `getProfile()`: the submitted form and the profile it used;
- `isSpam()` or `hasAntiSpamErrors()`: whether any antispam rule was violated;
- `getAntiSpamErrors()` and `getFormErrors()`: the errors raised by the bundle, and all other errors of the form.

`ValidatorViolationEvent` exposes the violated constraint with `getConstraint()` and the offending value with `getValue()`.

The following subscriber stores all spam submissions for later review:

```php
use Omines\AntiSpamBundle\Event\FormViolationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuarantineSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [FormViolationEvent::class => 'onFormViolation'];
    }

    public function onFormViolation(FormViolationEvent $event): void
    {
        $result = $event->getResult();

        foreach ($result->getAntiSpamErrors() as $error) {
            // Store the message, $result->getForm()->getData() and the profile name
        }
    }
}
```

## Logging

The bundle logs through the Monolog channel `antispam`. Every form submission that violates a rule is logged at `info` level with the client IP address and request URI, and a timer configured on an embedded form is reported at the same level. Use Monolog's channel configuration to route these messages to a separate handler:

```yaml title="config/packages/monolog.yaml"
monolog:
    handlers:
        antispam:
            type: stream
            path: '%kernel.logs_dir%/antispam.log'
            channels: ['antispam']
```
