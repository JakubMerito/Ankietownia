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
use App\Repository\SurveyResponseRepository;
use App\Repository\QuestionResponseRepository;

class QuestionController extends AbstractController
{
    #[Route('/survey/{id}/questions', name: 'survey_questions')]
    public function index(Survey $survey, Request $request, EntityManagerInterface $entityManager, SurveyResponseRepository $surveyResponseRepository, QuestionResponseRepository $questionResponseRepository): Response
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

        $responses = $surveyResponseRepository->findBySurvey($survey);

        $surveyStats = null;
        $questionResults = [];

        if (!empty($responses)) {
            $surveyStats = $this->prepareSurveyStats($survey, $responses);
            $questionResults = $this->prepareQuestionResults($questions, $responses, $questionResponseRepository);
        }

        return $this->render('question/index.html.twig', [
            'survey' => $survey,
            'questions' => $questions,
            'form' => $form,
            'responses' => $responses,
            'surveyStats' => $surveyStats,
            'questionResults' => $questionResults,
        ]);
    }

    private function prepareSurveyStats(Survey $survey, array $responses): array
    {
        $totalResponses = count($responses);
        $completedResponses = 0;
        $partialResponses = 0;

        foreach ($responses as $response) {
            if ($response->isCompleted()) {
                $completedResponses++;
            } else {
                $partialResponses++;
            }
        }

        return [
            'totalResponses' => $totalResponses,
            'completedResponses' => $completedResponses,
            'partialResponses' => $partialResponses,
        ];
    }

    private function prepareQuestionResults(array $questions, array $responses, QuestionResponseRepository $questionResponseRepository): array
    {
        $results = [];

        foreach ($questions as $question) {
            $questionResponses = $questionResponseRepository->findBy([
                'question' => $question,
                'surveyResponse' => $responses
            ]);

            $questionResult = [
                'totalAnswers' => count($questionResponses),
            ];

            $questionType = $question->getQuestionType();

            if (!$questionType) {
                $results[$question->getId()] = $questionResult;
                continue;
            }

            switch ($questionType->getName()) {
                case 'single_choice':
                case 'multiple_choice':
                    $questionResult['options'] = $this->prepareChoiceResults($question, $questionResponses);
                    break;

                case 'text':
                    $questionResult['textAnswers'] = $this->prepareTextResults($questionResponses);
                    break;

                case 'number':
                    $questionResult['stats'] = $this->prepareNumberStats($questionResponses);
                    break;

                case 'rating':
                    $questionResult = array_merge($questionResult, $this->prepareRatingResults($questionResponses));
                    break;
            }

            $results[$question->getId()] = $questionResult;
        }

        return $results;
    }

    private function prepareChoiceResults(Question $question, array $questionResponses): array
    {
        $optionCounts = [];
        $totalSelections = 0;

        foreach ($question->getQuestionOptions() as $option) {
            $optionCounts[$option->getId()] = [
                'text' => $option->getText(),
                'count' => 0
            ];
        }

        foreach ($questionResponses as $questionResponse) {
            if ($questionResponse->getQuestionOption()) {
                $optionId = $questionResponse->getQuestionOption()->getId();
                if (isset($optionCounts[$optionId])) {
                    $optionCounts[$optionId]['count']++;
                    $totalSelections++;
                }
            }
        }

        $results = [];
        foreach ($optionCounts as $optionData) {
            $percentage = $totalSelections > 0 ? round(($optionData['count'] / $totalSelections) * 100, 1) : 0;
            $results[] = [
                'text' => $optionData['text'],
                'count' => $optionData['count'],
                'percentage' => $percentage
            ];
        }

        return $results;
    }

    private function prepareTextResults(array $questionResponses): array
    {
        $textAnswers = [];
        foreach ($questionResponses as $questionResponse) {
            if ($questionResponse->getTextResponse()) {
                $textAnswers[] = [
                    'answer' => $questionResponse->getTextResponse(),
                    'createdAt' => $questionResponse->getSurveyResponse()->getCreatedAt()
                ];
            }
        }

        usort($textAnswers, function($a, $b) {
            return $b['createdAt'] <=> $a['createdAt'];
        });

        return $textAnswers;
    }

    private function prepareNumberStats(array $questionResponses): array
    {
        $numbers = [];
        foreach ($questionResponses as $questionResponse) {
            if ($questionResponse->getTextResponse() !== null && is_numeric($questionResponse->getTextResponse())) {
                $numbers[] = (float)$questionResponse->getTextResponse();
            }
        }

        if (empty($numbers)) {
            return [
                'average' => 0,
                'min' => 0,
                'max' => 0,
                'median' => 0
            ];
        }

        sort($numbers);
        $count = count($numbers);
        $median = $count % 2 === 0
            ? ($numbers[$count/2 - 1] + $numbers[$count/2]) / 2
            : $numbers[floor($count/2)];

        return [
            'average' => array_sum($numbers) / $count,
            'min' => min($numbers),
            'max' => max($numbers),
            'median' => $median
        ];
    }

    private function prepareRatingResults(array $questionResponses): array
    {
        $ratings = [];
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($questionResponses as $questionResponse) {
            if ($questionResponse->getTextResponse() !== null && is_numeric($questionResponse->getTextResponse())) {
                $rating = (int)$questionResponse->getTextResponse();
                if ($rating >= 1 && $rating <= 5) {
                    $ratings[] = $rating;
                    $distribution[$rating]++;
                }
            }
        }

        $averageRating = empty($ratings) ? 0 : array_sum($ratings) / count($ratings);

        return [
            'averageRating' => $averageRating,
            'ratingDistribution' => $distribution
        ];
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
}