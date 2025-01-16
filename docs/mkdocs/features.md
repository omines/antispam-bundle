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

## Quarantine

Quarantine was removed from the base bundle and is left up to the implementor via events.

## Forms

TBD.

## Validators

TBD.
