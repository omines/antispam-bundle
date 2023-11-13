# HoneypotType Field

The `HoneypotType` field is a text field being rendered invisible in your forms. It will raise a form validation error
if any non-empty value is submitted.

A [Honeypot](https://en.wikipedia.org/wiki/Honeypot_(computing)) is a concept in computing that exposes seemingly valid
application elements, without documenting or showing them to human users. Therefore their usage is a reliable indication
of abuse by scripts, bots or other malicious agents. In spam recognition, they are considered a powerful tool to detect
forms being filled in automatically by automated agents.

!!! tip
    The full list of options defined and inherited by this form type is available running this command in your app:
    ```shell
    # replace 'FooType' by the class name of your form type
    $ php bin/console debug:form FooType
    ```