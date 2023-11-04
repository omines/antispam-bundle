### `payload`

**type**: `mixed` **default**: `null`

This option can be used to attach arbitrary domain-specific data to a constraint. The configured payload is not used by
the Validator component, but its processing is completely up to you.

For example, you may want to use [several error levels](https://symfony.com/doc/current/validation/severity.html) to
present failed constraints differently in the front-end depending on the severity of the error.
