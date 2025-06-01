<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\QuestionOption;
use App\Entity\Survey;
use App\Form\QuestionFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    #[Route('/survey/{id}/questions', name: 'survey_questions')]
    public function index(Survey $survey, Request $request, EntityManagerInterface $entityManager): Response
    {
        $question = new Question();
        $question->setSurvey($survey);

        $form = $this->createForm(QuestionFormType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($question);
            $entityManager->flush();

            $this->addFlash('success', 'Pytanie zostało dodane pomyślnie!');
            return $this->redirectToRoute('survey_questions', ['id' => $survey->getId()]);
        }

        $questions = $entityManager->getRepository(Question::class)
            ->findBy(['survey' => $survey]);

        return $this->render('question/index.html.twig', [
            'survey' => $survey,
            'questions' => $questions,
            'form' => $form,
        ]);
    }

    #[Route('/question/{questionId}/add-option', name: 'question_option_add', methods: ['POST'])]
    public function addOption(int $questionId, Request $request, EntityManagerInterface $entityManager): Response
    {
        $question = $entityManager->getRepository(Question::class)->find($questionId);

        if (!$question) {
            throw $this->createNotFoundException('Pytanie nie zostało znalezione');
        }

        $optionText = $request->request->get('option_text');

        if (empty(trim($optionText))) {
            $this->addFlash('error', 'Tekst opcji nie może być pusty');
        } else {
            $option = new QuestionOption();
            $option->setQuestion($question);
            $option->setText($optionText);

            $entityManager->persist($option);
            $entityManager->flush();

            $this->addFlash('success', 'Opcja odpowiedzi została dodana');
        }

        return $this->redirectToRoute('survey_questions', ['id' => $question->getSurvey()->getId()]);
    }

    #[Route('/question-option/{id}/delete', name: 'question_option_delete')]
    public function deleteOption(QuestionOption $option, EntityManagerInterface $entityManager): Response
    {
        $surveyId = $option->getQuestion()->getSurvey()->getId();

        $entityManager->remove($option);
        $entityManager->flush();

        $this->addFlash('success', 'Opcja odpowiedzi została usunięta');

        return $this->redirectToRoute('survey_questions', ['id' => $surveyId]);
    }

    #[Route('/question/{id}/delete', name: 'question_delete')]
    public function deleteQuestion(Question $question, EntityManagerInterface $entityManager): Response
    {
        $surveyId = $question->getSurvey()->getId();

        $entityManager->remove($question);
        $entityManager->flush();

        $this->addFlash('success', 'Pytanie zostało usunięte');

        return $this->redirectToRoute('survey_questions', ['id' => $surveyId]);
    }

    #[Route('/survey/{id}/toggle-active', name: 'survey_toggle_active', methods: ['POST'])]
    public function toggleActive(Survey $survey, Request $request, EntityManagerInterface $em): Response
    {
        // Sprawdź token CSRF
        if (!$this->isCsrfTokenValid('survey_toggle_' . $survey->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('survey_questions', ['id' => $survey->getId()]);
        }

        // Przed aktywacją sprawdź czy ankieta ma pytania
        if (!$survey->isActive() && $survey->getQuestions()->isEmpty()) {
            $this->addFlash('error', 'Nie można aktywować ankiety bez pytań. Dodaj przynajmniej jedno pytanie.');
            return $this->redirectToRoute('survey_questions', ['id' => $survey->getId()]);
        }

        // Przełącz status
        $survey->setIsActive(!$survey->isActive());

        try {
            $em->flush();

            if ($survey->isActive()) {
                $this->addFlash('success', 'Ankieta została aktywowana i jest dostępna publicznie.');
            } else {
                $this->addFlash('success', 'Ankieta została dezaktywowana.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas zmiany statusu ankiety.');
        }

        return $this->redirectToRoute('survey_questions', ['id' => $survey->getId()]);
    }
}