# Features

## Profiles

TBD.

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

## Rate Limiting

Profiles can limit how often a form may be submitted by the same client, using limiters from
[`symfony/rate-limiter`](https://symfony.com/doc/current/rate_limiter.html). Define the limiters in the framework
configuration and reference them by name from a profile:

```yaml
framework:
    rate_limiter:
        contact_minute:
            policy: fixed_window
            limit: 1
            interval: '1 minute'
        contact_hour:
            policy: sliding_window
            limit: 5
            interval: '1 hour'

antispam:
    profiles:
        contact:
            rate_limiter: [contact_minute, contact_hour]
```

A single limiter can be given as a string. When multiple limiters are listed, all of them must accept the submission.

Clients are identified by their IP address. Only submissions which are otherwise valid use up a token, so visitors
correcting mistakes are not throttled. The limit is applied to the root form only, and is ignored on embedded forms.
Rejections follow the profile's [stealth](#stealth-mode) and [passive](#passive-mode) settings like any other check.

Install the component with `composer require symfony/rate-limiter` to use this feature.

!!! warning "Behind a reverse proxy"
    The client IP is read through Symfony's `Request::getClientIp()`. When your application runs behind a load balancer
    or reverse proxy you must configure [`framework.trusted_proxies`](https://symfony.com/doc/current/deployment/proxies.html),
    otherwise every visitor shares the IP address of the proxy and a single submission throttles all of them.

    Addresses are used as-is, so IPv6 clients can switch addresses within their prefix to avoid the limit. Treat rate
    limiting as one layer next to the other measures, not as a guarantee.

!!! note "Tokens are used before your controller runs"
    The token is consumed as soon as the form has been validated, before your own code handles the submission. When your
    controller rejects a valid submission afterwards, for example because of a duplicate email address, the token is
    already used and an immediate retry may be throttled.

    Quota is tracked per client IP and per limiter, not per form. Profiles or forms sharing a limiter also share its quota,
    so define separate limiters when forms should be limited independently.

## Quarantine

Quarantine was removed from the base bundle and is left up to the implementor via events.

## Forms

TBD.

## Validators

TBD.
