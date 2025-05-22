<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SurveyController extends AbstractController
{
    #[Route('/survey', name: 'app_survey_new')]
    public function index(): Response
    {
        return $this->render('survey/new.html.twig');
    }
}
