### `stealth`

**type**: `bool` **default**: `null`, defaulting to [bundle configuration](../configuration.md)

With stealth mode disabled the validator will generate a verbose error, similar to Symfony built-in constraints,
explaining for precisely which reasons what rule was validated.

If stealth mode is enabled instead the validator only shows a generic error message, stating that form submission
failed and the user should contact the website administrator for further assistance.

With [default bundle configuration](../configuration.md/#default-config) stealth mode is **disabled** by default when used standalone,
and **enabled** by default when applied as part of an anti-spam profile.
