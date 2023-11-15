# Extending functionality

## Examples

### "Fake pass"

A spammer who sees success is a happy spammer. One of the problems we have to cope with these days is that some people
actually consider it an effective strategy to pay people to randomly fill in forms on the internet. If actual people see
their spam is rejected, they might actually want to try again. Let's discourage that.

To lure the spammer into believing their spam was accepted and is going to be read, we have to enable `passive` mode on
the form profile:

```yaml linenums="1" hl_lines="4"
antispam:
  profiles:
    my_form:
      passive: true

      # Rest of your configuration
```
Our form will now pass, regardless of the violations. We can however use the result of the last test, which is always
stored in the `AntiSpam` class!
```php linenums="1" hl_lines="9 10 11 12 13"
#[Route('/contact')]
public function fakeSuccess(Request $request): Response
{
    $form = $this->createForm(ContactForm::class, options: [
        'antispam_profile' => 'my_form',
    ]);
    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        if (!AntiSpam::isSpam()) {
            // Only send an actual email if the form did not trigger
            // spam detections!
            $this->sendEmailTo('site-owner@example.org', $form->getData();
        }
        $this->addFlash('message', 'Form passed');
    }

    return $this->render('form.html.twig', [
        'form' => $form->createView(),
    ]);
}
```
