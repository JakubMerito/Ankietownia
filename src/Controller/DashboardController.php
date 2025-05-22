<?php

namespace App\Controller;

use App\Repository\SurveyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(SurveyRepository $surveyRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $surveys = $surveyRepository->findBy(['user' => $user]);

        return $this->render('dashboard/index.html.twig', [
            'surveys' => $surveys,
            'user' => $user,
        ]);
    }
}
