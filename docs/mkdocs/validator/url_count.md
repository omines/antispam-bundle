# URL count

Validates that a given string contains at most a defined number of URLs, or a limited amount of duplicated URLs.

Can be applied to [properties or methods](https://symfony.com/doc/current/validation.html#constraint-targets).

??? tip "How strict is the detection of URLs?"
URL detection is loose on purpose, and will also flag "lame attempts" that are not valid, while at the same time
trying to keep the chance of false positives as low as possible. Spambots are not known to strictly adhere to
internet standards so being really strict would only reduce effectiveness.

## Basic Usage

=== "Attributes"

    ```php
    namespace App\Entity;
    
    use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
    
    class Message
    {
        #[Antispam\UrlCount(max: 3, maxIdentical: 1]
        protected string $content;
    }
    ```

=== "YAML"

    ```yaml
    # config/validator/validation.yaml
    App\Entity\Message:
        properties:
            content:
                - UrlCount:
                    max: 3
                    maxIdentical: 1
    ```

=== "PHP"

    ```php
    // src/Entity/Participant.php
    namespace App\Entity;
    
    use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
    use Symfony\Component\Validator\Mapping\ClassMetadata;
    
    class Message
    {
    // ...
    
        public static function loadValidatorMetadata(ClassMetadata $metadata): void
        {
            $metadata->addPropertyConstraint('content', new Antispam\UrlCount(max: 3, maxIdentical: 1));
        }
    }
    ```

--8<-- "includes/validator-null-warning.md"

## Options

--8<-- "includes/validator-groups-option.md"

### `max`

**type**: `int|null` **default**: `0`

Validation will fail if the given value contains more URLs than configured.

Set to `null` to disable failing on URL count altogether.

### `maxIdentical`

**type**: `int|null` **default**: `null`

Validation will fail if any single URL in the given value occurs more often than configured.

The default `null` setting disables counting URL repetition.

--8<-- "includes/validator-passive-option.md"

--8<-- "includes/validator-payload-option.md"

--8<-- "includes/validator-stealth-option.md"
