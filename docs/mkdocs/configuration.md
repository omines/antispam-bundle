# Configuration

The bundle configuration is for the most part self-documented, and the annotated default configuration can be viewed
from the Symfony console with:
```shell
bin/console config:dump-reference antispam
```

Note that you can also view the resolved configuration during development:
```shell
bin/console debug:config antispam
```

## Default config

```yaml
# Default configuration for extension with alias: "antispam"
antispam:

  # Global default for whether included components issue verbose or stealthy error messages
  stealth:              false # (1)!

  # A named list of different profiles used throughout your application
  profiles:

    # Prototype: Name the profile
    name:

      # Defines whether measures in this profile issue stealthy error messages
      stealth:              true # (1)!

      # Passive mode will not make any of the included checks actually fail validation, they will still be logged
      passive:              false

      # Defines whether to disallow content resembling markup languages like HTML and BBCode
      banned_markup:
        html:                 true
        bbcode:               true

      # Simple array of phrases which are rejected when encountered in a submitted text field
      banned_phrases:       []

      # Banned script types, like Cyrillic or Arabic (see docs for commonly used ISO 15924 names)
      banned_scripts:
        scripts:              []
        max_characters:       null
        max_percentage:       0

      # Inject an invisible honeypot field in forms, baiting spambots to fill it in
      honeypot:

        # Base name of the injected field
        field:                ~ # Required

      # Maximum number of URLs permitted in text fields
      max_urls:             ~

      # Verify that time between retrieval and submission of a form is within human boundaries
      timer:

        # Base name of the injected field
        field:                __antispam_time
        min:                  3
        max:                  3600
```

1. The global and profile defaults for `stealth` are different on purpose. The global setting is applied to validators
   and form types used separately, and will therefore default to acting like an actual validator, displaying the precise
   error in the right place. Within a profile they become part of a larger antispam measure, and are therefore stealthed,
   merging them together as a generic rejection message.

