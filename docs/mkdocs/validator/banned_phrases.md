# Banned Phrases

Validates that a given string does not contain blacklisted phrases.

Can be applied to [properties or methods](https://symfony.com/doc/current/validation.html#constraint-targets).

## Basic Usage

=== "Attributes"

    ```php
    namespace App\Entity;
    
    use Omines\AntiSpamBundle\Validator\Constraints as Antispam;
    
    class Message
    {
        #[Antispam\BannedPhrases(['viagra', 'cialis'])
        protected string $content;
    }
    ```

=== "YAML"

    ```yaml
    # config/validator/validation.yaml
    App\Entity\Message:
        properties:
            content:
                - BannedPhrases:
                    - viagra
                    - cialis
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
            $metadata->addPropertyConstraint('content', new Antispam\BannedPhrases(['viagra', 'cialis']));
        }
    }
    ```

## Options

--8<-- "includes/validator-groups-option.md"

--8<-- "includes/validator-passive-option.md"

--8<-- "includes/validator-payload-option.md"

--8<-- "includes/validator-stealth-option.md"
