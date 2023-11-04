!!! warning "Null values are always valid"
    As with most of Symfony's own constraints, `null` is considered a valid value. This is to allow the use of optional
    values. If the value is mandatory, a common solution is to combine this constraint with [NotBlank](https://symfony.com/doc/current/reference/constraints/NotBlank.html).
