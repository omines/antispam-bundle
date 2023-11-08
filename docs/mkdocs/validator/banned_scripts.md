# Banned Scripts

Validates that a given string does not contain characters of the given script or scripts. 

A [Script](https://en.wikipedia.org/wiki/Script_(Unicode)) is the Unicode term given to what is commonly called a
[writing system](https://en.wikipedia.org/wiki/Writing_system). Well known examples include the Greek, Arabic, Cyrillic
and Han scripts.

The validator can fail on a minimum percentage or character count in the given value. With the default percentage of 0%
the validator will fail if it encounters any character in any of the configured scripts.

Can be applied to [properties or methods](https://symfony.com/doc/current/validation.html#constraint-targets).

## Basic Usage

=== "Attributes"

    ```php
    namespace App\Entity;

    use Omines\AntiSpamBundle\Type\Script; 
    use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
    
    class Message
    {
        #[Antispam\BannedScripts([Script::Cyrillic]
        protected string $content;
    }
    ```

=== "YAML"

    ```yaml
    # config/validator/validation.yaml
    App\Entity\Message:
        properties:
            content:
                - BannedScripts: [Cyrillic]
    ```

=== "PHP"

    ```php
    // src/Entity/Participant.php
    namespace App\Entity;
    
    use Omines\AntiSpamBundle\Type\Script;
    use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
    use Symfony\Component\Validator\Mapping\ClassMetadata;
    
    class Message
    {
    // ...
    
        public static function loadValidatorMetadata(ClassMetadata $metadata): void
        {
            $metadata->addPropertyConstraint('content', new Antispam\BannedScripts(Script::Cyrillic));
        }
    }
    ```

--8<-- "includes/validator-null-warning.md"

## Options

--8<-- "includes/validator-groups-option.md"

### `maxPercentage`

**type**: `int` **default**: `0`

Validation will fail if the given value contains more characters in the configured scripts than configured.

Ignored if set to `0` and `maxCharacters` is also set.

### `maxCharacters`

**type**: `int|null` **default**: `null`

Validation will fail if the given value contains more characters in the configured scripts than configured.

Set to `null` to disable failing on absolute character count.

--8<-- "includes/validator-passive-option.md"

--8<-- "includes/validator-payload-option.md"

### `scripts`

**type**: `array|string|Script`

An array of `Script` values, or strings that can be parsed into a valid `Script` value. Scalars are wrapped in
an array.

For valid values refer to [`Script.php`](https://github.com/omines/antispam-bundle/blob/master/src/Type/Script.php)
in the sources.

--8<-- "includes/validator-stealth-option.md"
