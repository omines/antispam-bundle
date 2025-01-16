<?php

/*
 * Symfony Anti-Spam Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixture\Controller;

use Omines\AntiSpamBundle\AntiSpam;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tests\Fixture\Form\Type\BasicForm;
use Tests\Fixture\Form\Type\EmbeddingForm;
use Tests\Fixture\Form\Type\KitchenSinkForm;

class PageController extends AbstractController
{
    #[Route('/', 'home')]
    public function home(Request $request): Response
    {
        $form = $this->createForm(KitchenSinkForm::class);
        $form->add('submit', SubmitType::class);

        return $this->finishRequest($form, $request);
    }

    #[Route('/profile/{profile}')]
    public function profile(string $profile, Request $request): Response
    {
        $form = $this->createForm(BasicForm::class, options: [
            'antispam_profile' => $profile,
        ]);
        $form->add('submit', SubmitType::class);

        return $this->finishRequest($form, $request);
    }

    #[Route('/embedded')]
    public function embedded(Request $request): Response
    {
        $form = $this->createForm(EmbeddingForm::class, options: [
            'antispam_profile' => 'test1',
        ]);
        $form->add('submit', SubmitType::class);

        return $this->finishRequest($form, $request);
    }

    #[Route('/disabled')]
    public function disabled(Request $request, AntiSpam $antiSpam): Response
    {
        $antiSpam->disable();
        $form = $this->createForm(BasicForm::class, options: [
            'antispam_profile' => 'test1',
        ]);
        $form->add('submit', SubmitType::class);

        return $this->finishRequest($form, $request);
    }

    #[Route('/fake_success')]
    public function fakeSuccess(Request $request): Response
    {
        $form = $this->createForm(BasicForm::class, options: [
            'antispam_profile' => 'passive_empty',
        ]);
        $form->add('submit', SubmitType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (AntiSpam::isSpam()) {
                return new Response('<h1>FAKED</h1>');
            }
            $this->addFlash('message', 'Form passed');
        }

        return $this->render('form.html.twig', [
            'form' => $form->createView(),
            'languages' => self::getLanguages(),
        ]);
    }

    private function finishRequest(FormInterface $form, Request $request): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('message', 'Form passed');
        }

        return $this->render('form.html.twig', [
            'form' => $form->createView(),
            'languages' => self::getLanguages(),
        ]);
    }

    private static function getLanguages(): array
    {
        return array_map(fn (string $match) => mb_substr($match, -7, 2), glob(__DIR__ . '/../../../../translations/antispam+intl-icu.[a-z][a-z].yaml'));
    }
}
