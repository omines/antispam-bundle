# Quickstart

## Basic setup

Install the bundle:
```shell
composer require omines/antispam-bundle
```
Symfony Flex will automatically enable the bundle, and run a recipe creating a basic configuration file in the right
place for your project.

Open it and it will look something like this between the comments:

```yaml title="config/packages/antispam.yaml" linenums="1"
antispam:
    profiles:
        default:
            honeypot: email_address
            timer:
                min: 3
                max: 3600
```
This defines a *profile* called `default`, which defines that any forms having the profile should insert a
[honeypot](https://en.wikipedia.org/wiki/Honeypot_(computing)) field called `email_adress`, and have timer protection
rejecting forms submitted either within 3 seconds, or after more than 1 hour.

??? tip "But my form already has an email_address field!"
    Don't worry about automatically generated field causing name conflicts, the bundle will detect this and create
    a unique name instead by appending a number. In this case the honeypot would become `email_address1` instead
    automatically!

For the form that you want to protect, apply the profile in its options either when creating it:
```php
$form = $this->createForm(MyApplicationForm::class, options: [
    'antispam_profile' => 'default',
]);
```
Or in its type definition's defaults:
```php
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefault('antispam_profile', 'default');
}
```
**THAT'S IT!** You should now already start noticing a severe decrease in spam submissions!

??? warning "Not seeing an error when you manually try to submit a form too fast or too slow?"
    Symfony Forms by default only shows form level errors if you are using `{{ form(form) }}` to render it as a whole.
    If you use `{{ form_start(form) }}` instead you will need to ensure form level errors are also shown, either by
    adding `{{ form_errors(form) }}` or manually rendering them with something like this:

    ```twig
    {% for error in form.vars.errors %}
        <div class="alert alert-danger">{{ error.message }}</div>
    {% endfor %}
    ```

    Note that this is not a shortcoming of this bundle, and is in general a good idea to do. This bundle just exposes
    the issue because it cannot show errors on fields that are by definition hidden.

## Tailoring the weapons

While the default setup will already stop many bots and scripts, there is also a lot of manual spam going on, and the
spammers and scammers do write bots that can bypass these common methods. After running with the defaults for a bit
you will have some idea of the kind of spam still getting through.

Let's investigate some of the more invasive options available. For the following example, we're protecting the contact
form of a local toy store operating in southern France.

First of all, spammers are usually trying to sell you stuff, meaning they probably want to get you to head over to
a forged or real website. So a contact form submission with several links in there is usually quite suspicious. For our
toy store, we do expect someone to sometimes email a link, asking if we also stock the item. More than 1 is unlikely,
more than 2 pretty much implausible.

Spammers also have issues with "lingual targeting". It's really unlikely that a real customer would ever fill in our
contact form in Russian or Hebrew (1). Spambots regularly do, so let's forbid that.
{ .annotate }

1.  The chosen scripts are random for the sake of example. In the real world you would choose which scripts to add based
    on actual spam getting through your forms unscathed.

Lastly, we're a toy store, not a software company or a digital agency. People will not send us snippets of HTML.
Spambots regularly do, in lame attempts at [XSS](https://en.wikipedia.org/wiki/Cross-site_scripting) or phishing.
We'll ensure it's not accepted.

So let's create a new profile `contact` specifically for the website's contact form:
```yaml title="config/packages/antispam.yaml" linenums="1"
antispam:
    profiles:
        contact:
            # We'll keep the honeypot and timer, they're non-invasive and
            # highly effective
            honeypot: email_address # (1)!
            timer:
                min: 3
                max: 14400 # (2)!
            
            # Reject content containing HTML or BBCode markup
            banned_markup: true

            # Reject forms that consist for more than half of Cyrillic
            # (Russian) or Hebrew text
            banned_scripts:
                scripts: ['cyrillic', 'hebrew']
                max_percentage: 50

            # Reject form fields containing more than 2 URLs, or repeating
            # identical URLs
            url_count:
                max: 2
                max_identical: 1
```

1. Remember that the field name will automatically become `email_address1` or a higher number in case of a naming conflict.
2. Toy stores attract kids :child:, and they may get distracted or go away. Allowing 4 hours for a response might be fair!

What happens now when you apply the `contact` profile to your contact form type:

- Your form will get automatically get 2 hidden fields injected:
    * A [Honeypot](form/honeypot.md) called `email_address`, that will fail the form if it contains any value on submit.
    * A [Submit Timer](form/submit_timer.md) cryptographically verifying the number of seconds between retrieving and
      posting the form. Many spambots either submit a form within a single second, as they are in a rush to attack
      millions of other sites, or they store the form so they can submit it hours, days or weeks later. The timer ensures
      that the form is posted with a delay that is reasonable for a human filling it in, without spending days to do so.
- All text fields on the form will get some extra validators injected:
    * A [BannedMarkup](validator/banned_markup.md) validator that ensures no content that attempts to (ab)use HTML or
      BBCode features is allowed.
    * A [BannedScripts](validator/banned_scripts.md) validator blocks all fields which consist for at least 50% of
      Cyrillic or Hebrew characters.
    * A [URLCount](validator/url_count.md) validator will block form submission if any of its text fields constain more
      than 2 URLs, or if a single URL is repeated more than once. 

!!! warning "Always consider carefully what is normal on your specific site!"
    All anti-spam measures can backfire at some point, and trigger false positives that may hurt your or your client's
    interests. Blocking all Cyrillic (Russian) text in the contact form of a translation agency is a really bad idea. 
    Blocking **all** links also means your new sales opportunity can't say *"I want to have a similar site to 
    https://example.org"*. Even banning the phrase *"WE SELL VIAGRA"* could theoretically block someone requesting
    commercial help with a spammer sending them that message every hour.

    Be prudent about the measures you implement, and try to err on the side of caution. Remember that it's better to
    receive a single uncaught spam email per week than to lose a valuable customer every month.

## Stealth Mode

By default, all **profiles** have "Stealth Mode" enabled. The way Symfony's form and validator mechanisms work together
is however counterintuitive for how we expect antispam measures to work, as they are really verbose, informative and
user friendly.

