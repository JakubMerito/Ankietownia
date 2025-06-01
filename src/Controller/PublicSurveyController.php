<?php

namespace App\Controller;

use App\Entity\Survey;
use App\Entity\SurveyResponse;
use App\Entity\QuestionResponse;
use App\Entity\Question;
use App\Entity\QuestionOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/survey')]
class PublicSurveyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/{id}', name: 'public_survey_show', methods: ['GET'])]
    public function show(Survey $survey): Response
    {
        // Sprawdź czy ankieta jest aktywna
        if (!$survey->isActive()) {
            throw $this->createNotFoundException('Ankieta nie jest dostępna.');
        }

        return $this->render('public_survey/show.html.twig', [
            'survey' => $survey,
        ]);
    }

    #[Route('/{id}/submit', name: 'public_survey_submit', methods: ['POST'])]
    public function submit(Survey $survey, Request $request): JsonResponse
    {
        if (!$survey->isActive()) {
            return new JsonResponse(['error' => 'Ankieta nie jest dostępna.'], 400);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['responses'])) {
            return new JsonResponse(['error' => 'Nieprawidłowe dane.'], 400);
        }

        try {
            // Walidacja wymaganych pól
            $requiredQuestions = $this->entityManager->getRepository(Question::class)
                ->findBy(['survey' => $survey, 'isRequired' => true]);

            foreach ($requiredQuestions as $question) {
                if (!isset($data['responses'][$question->getId()]) ||
                    empty($data['responses'][$question->getId()])) {
                    return new JsonResponse([
                        'error' => 'Pytanie "' . $question->getText() . '" jest wymagane.'
                    ], 400);
                }
            }

            // Stwórz nową sesję odpowiedzi
            $surveyResponse = new SurveyResponse();
            $surveyResponse->setSurvey($survey);
            $surveyResponse->setIpAddress($request->getClientIp());
            $surveyResponse->setUserAgent($request->headers->get('User-Agent'));

            $this->entityManager->persist($surveyResponse);

            // Przetwórz odpowiedzi
            foreach ($data['responses'] as $questionId => $responseData) {
                $question = $this->entityManager->getRepository(Question::class)->find($questionId);

                if (!$question || $question->getSurvey() !== $survey) {
                    continue;
                }

                // Dla pytań wielokrotnego wyboru
                if (is_array($responseData)) {
                    foreach ($responseData as $optionId) {
                        if (is_numeric($optionId)) {
                            $option = $this->entityManager->getRepository(QuestionOption::class)->find($optionId);
                            if ($option && $option->getQuestion() === $question) {
                                $questionResponse = new QuestionResponse();
                                $questionResponse->setSurveyResponse($surveyResponse);
                                $questionResponse->setQuestion($question);
                                $questionResponse->setQuestionOption($option);

                                $this->entityManager->persist($questionResponse);
                            }
                        }
                    }
                } else {
                    // Dla pytań jednokrotnego wyboru lub tekstowych
                    $questionResponse = new QuestionResponse();
                    $questionResponse->setSurveyResponse($surveyResponse);
                    $questionResponse->setQuestion($question);

                    if (is_numeric($responseData)) {
                        // Odpowiedź z opcji
                        $option = $this->entityManager->getRepository(QuestionOption::class)->find($responseData);
                        if ($option && $option->getQuestion() === $question) {
                            $questionResponse->setQuestionOption($option);
                        }
                    } else {
                        // Odpowiedź tekstowa
                        $questionResponse->setTextResponse((string)$responseData);
                    }

                    $this->entityManager->persist($questionResponse);
                }
            }

            // Oznacz jako ukończone
            $surveyResponse->setIsCompleted(true);
            $surveyResponse->setCompletedAt(new \DateTime());

            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Ankieta została przesłana pomyślnie.']);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Wystąpił błąd podczas zapisywania odpowiedzi.'], 500);
        }
    }

    #[Route('/{id}/thank-you', name: 'public_survey_thank_you', methods: ['GET'])]
    public function thankYou(Survey $survey): Response
    {
        return $this->render('public_survey/thank_you.html.twig', [
            'survey' => $survey,
        ]);
    }
}