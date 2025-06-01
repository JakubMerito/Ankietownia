<?php

namespace App\Controller;

use App\Entity\Survey;
use App\Entity\Question;
use App\Form\SurveyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SurveyController extends AbstractController
{
    #[Route('/create_survey/new', name: 'app_survey_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $survey = new Survey();
        $survey->setUser($this->getUser());
        $survey->setCreatedAt(new \DateTime());

        $form = $this->createForm(SurveyType::class, $survey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($survey);
            $em->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('survey/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/survey/{id}/questions', name: 'app_survey_questions')]
    public function editQuestions(Survey $survey, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $question = new Question();
        $question->setSurvey($survey);

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($question);
            $em->flush();

            return $this->redirectToRoute('app_survey_questions', ['id' => $survey->getId()]);
        }

        return $this->render('survey/questions.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
            'questions' => $survey->getQuestions(),
        ]);
    }
}
