<?php

namespace App\Controller;

use App\Entity\Survey;
use App\Entity\Question;
use App\Entity\SurveyResponse;
use App\Entity\QuestionResponse;
use App\Form\SurveyType;
use App\Form\QuestionType;
use App\Repository\SurveyResponseRepository;
use App\Repository\QuestionResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SurveyController extends AbstractController
{
    public function __construct(
        private SurveyResponseRepository $responseRepository,
        private QuestionResponseRepository $questionResponseRepository // Changed from answerRepository
    ) {}

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

            $this->addFlash('success', 'Pytanie zostało dodane pomyślnie!');
            return $this->redirectToRoute('app_survey_questions', ['id' => $survey->getId()]);
        }

        $responses = $this->responseRepository->findBy(['survey' => $survey]);

        $surveyStats = null;
        $questionResults = [];

        if (!empty($responses)) {
            $surveyStats = $this->prepareSurveyStats($survey, $responses);
            $questionResults = $this->prepareQuestionResults($survey->getQuestions()->toArray(), $responses);
        }

        return $this->render('survey/questions.html.twig', [
            'survey' => $survey,
            'form' => $form->createView(),
            'questions' => $survey->getQuestions(),
            'responses' => $responses,
            'surveyStats' => $surveyStats,
            'questionResults' => $questionResults,
        ]);
    }

    #[Route('/survey/{id}/export/csv', name: 'survey_export_csv')]
    public function exportCsv(Survey $survey): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $responses = $this->responseRepository->findBy(['survey' => $survey]);
        $questions = $survey->getQuestions()->toArray();

        $filename = sprintf('ankieta_%s_%s.csv',
            $survey->getId(),
            (new \DateTime())->format('Y-m-d_H-i-s')
        );

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $response->setCallback(function() use ($responses, $questions) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            $headers = ['ID Odpowiedzi', 'Data wypełnienia', 'Status'];
            foreach ($questions as $question) {
                $headers[] = $question->getText();
            }
            fputcsv($handle, $headers, ';');

            foreach ($responses as $response) {
                $row = [
                    $response->getId(),
                    $response->getCreatedAt()->format('Y-m-d H:i:s'),
                    $response->isCompleted() ? 'Ukończona' : 'Częściowa'
                ];

                foreach ($questions as $question) {
                    $questionResponse = $this->questionResponseRepository->findOneBy([
                        'surveyResponse' => $response,
                        'question' => $question
                    ]);

                    if ($questionResponse) {
                        if ($questionResponse->getTextResponse()) {
                            $row[] = $questionResponse->getTextResponse();
                        } elseif ($questionResponse->getQuestionOption()) {
                            $row[] = $questionResponse->getQuestionOption()->getText();
                        } else {
                            $row[] = '';
                        }
                    } else {
                        $row[] = '';
                    }
                }

                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        });

        return $response;
    }

    #[Route('/survey/{id}/export/excel', name: 'survey_export_excel')]
    public function exportExcel(Survey $survey): Response
    {
        return $this->redirectToRoute('survey_export_csv', ['id' => $survey->getId()]);
    }

    #[Route('/survey/{id}/export/pdf', name: 'survey_export_pdf')]
    public function exportPdf(Survey $survey): Response
    {
        $this->addFlash('info', 'Eksport PDF będzie dostępny wkrótce.');
        return $this->redirectToRoute('app_survey_questions', ['id' => $survey->getId()]);
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

    private function prepareQuestionResults(array $questions, array $responses): array
    {
        $results = [];

        foreach ($questions as $question) {
            $questionResponses = $this->questionResponseRepository->findBy([
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

    #[Route('/survey/{id}/toggle-active', name: 'survey_toggle_active', methods: ['POST'])]
    public function toggleActive(Survey $survey, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('survey_toggle_' . $survey->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('app_survey_questions', ['id' => $survey->getId()]);
        }

        if (!$survey->isActive() && $survey->getQuestions()->isEmpty()) {
            $this->addFlash('error', 'Nie można aktywować ankiety bez pytań. Dodaj przynajmniej jedno pytanie.');
            return $this->redirectToRoute('app_survey_questions', ['id' => $survey->getId()]);
        }

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

        return $this->redirectToRoute('app_survey_questions', ['id' => $survey->getId()]);
    }
}