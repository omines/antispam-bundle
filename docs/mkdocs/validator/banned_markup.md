# Banned Markup

Validates that a given string does not contain a configured type of markup. 

It can recognize [HTML](https://en.wikipedia.org/wiki/HTML) and [BBCode](https://en.wikipedia.org/wiki/BBCode).

Can be applied to [properties or methods](https://symfony.com/doc/current/validation.html#constraint-targets).

??? tip "How strict is the detection of the markup types?"
    Markup detection is loose on purpose, and will also flag "lame attempts" that are not valid, while at the same time
    trying to keep the chance of false positives as low as possible. Spambots are not known to strictly adhere to
    internet standards so being really strict would only reduce effectiveness.

## Basic Usage

=== "Attributes"

    ```php
    namespace App\Entity;
    
    use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
    
    class Message
    {
        #[Antispam\BannedMarkup]
        protected string $content;
    }
    ```

=== "YAML"

    ```yaml
    # config/validator/validation.yaml
    App\Entity\Message:
        properties:
            content:
                - BannedMarkup:
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
            $metadata->addPropertyConstraint('content', new Antispam\BannedMarkup());
        }
    }
    ```

--8<-- "includes/validator-null-warning.md"

## Options

### `bbcode`

**type**: `boolean` **default**: `true`

If set to `true` validation will fail if the given value contains tags resembling BBCode.

--8<-- "includes/validator-groups-option.md"

### `html`

**type**: `boolean` **default**: `true`

If set to `true` validation will fail if the given value contains tags resembling HTML.

--8<-- "includes/validator-passive-option.md"

--8<-- "includes/validator-payload-option.md"

--8<-- "includes/validator-stealth-option.md"
