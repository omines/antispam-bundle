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

The global and profile defaults for `stealth` are different on purpose. The global setting is applied to validators
and form types used separately, and will therefore default to acting like an actual validator, displaying the precise
error in the right place. Within a profile they become part of a larger antispam measure, and are therefore stealthed,
merging them together as a generic rejection message.

## Default config

```yaml
--8<-- "includes/default-config.yaml"
```
