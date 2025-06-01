<?php

namespace App\Controller;

use App\Entity\Survey;
use App\Entity\SurveyResponse;
use App\Repository\SurveyRepository;
use App\Repository\SurveyResponseRepository;
use App\Repository\QuestionResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/survey')]
#[IsGranted('ROLE_USER')]
class AdminSurveyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SurveyResponseRepository $surveyResponseRepository,
        private QuestionResponseRepository $questionResponseRepository
    ) {}

    #[Route('/', name: 'admin_survey_index')]
    public function index(SurveyRepository $surveyRepository): Response
    {
        $surveys = $surveyRepository->findBy(['user' => $this->getUser()]);

        return $this->render('admin_survey/index.html.twig', [
            'surveys' => $surveys,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_survey_toggle_status', methods: ['POST'])]
    public function toggleStatus(Survey $survey): Response
    {
        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $survey->setIsActive(!$survey->isActive());
        $this->entityManager->flush();

        $this->addFlash('success',
            $survey->isActive() ? 'Ankieta została opublikowana.' : 'Ankieta została ukryta.'
        );

        return $this->redirectToRoute('admin_survey_index');
    }

    #[Route('/{id}/results', name: 'admin_survey_results')]
    public function results(Survey $survey): Response
    {
        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $responses = $this->surveyResponseRepository->findCompletedBySurvey($survey);
        $totalResponses = count($responses);

        $questionStats = [];
        foreach ($survey->getQuestions() as $question) {
            $questionType = $question->getQuestionType() ? $question->getQuestionType()->getName() : 'single_choice';

            if (in_array($questionType, ['single_choice', 'multiple_choice'])) {
                $stats = $this->questionResponseRepository->getStatisticsForSingleChoice($question);
                $questionStats[$question->getId()] = [
                    'type' => 'choice',
                    'data' => $stats,
                    'total' => array_sum(array_column($stats, 'count'))
                ];
            } else {
                $textResponses = $this->questionResponseRepository->getTextResponsesForQuestion($question);
                $questionStats[$question->getId()] = [
                    'type' => 'text',
                    'data' => $textResponses,
                    'total' => count($textResponses)
                ];
            }
        }

        return $this->render('admin_survey/results.html.twig', [
            'survey' => $survey,
            'responses' => $responses,
            'totalResponses' => $totalResponses,
            'questionStats' => $questionStats,
        ]);
    }

    #[Route('/{id}/share', name: 'admin_survey_share')]
    public function share(Survey $survey): Response
    {
        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $publicUrl = $this->generateUrl('public_survey_show', ['id' => $survey->getId()], true);

        return $this->render('admin_survey/share.html.twig', [
            'survey' => $survey,
            'publicUrl' => $publicUrl,
        ]);
    }

    #[Route('/{id}/responses/export', name: 'admin_survey_export')]
    public function exportResponses(Survey $survey): Response
    {
        if ($survey->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $responses = $this->surveyResponseRepository->findCompletedBySurvey($survey);

        $csvData = [];
        $headers = ['ID odpowiedzi', 'Data utworzenia', 'Data ukończenia'];

        foreach ($survey->getQuestions() as $question) {
            $headers[] = $question->getText();
        }
        $csvData[] = $headers;

        foreach ($responses as $response) {
            $row = [
                $response->getId(),
                $response->getCreatedAt()->format('Y-m-d H:i:s'),
                $response->getCompletedAt() ? $response->getCompletedAt()->format('Y-m-d H:i:s') : ''
            ];

            foreach ($survey->getQuestions() as $question) {
                $questionResponses = $response->getQuestionResponses()->filter(
                    fn($qr) => $qr->getQuestion() === $question
                );

                $answerText = '';
                foreach ($questionResponses as $qr) {
                    if ($qr->getQuestionOption()) {
                        $answerText .= $qr->getQuestionOption()->getText() . '; ';
                    } elseif ($qr->getTextResponse()) {
                        $answerText .= $qr->getTextResponse() . '; ';
                    }
                }
                $row[] = rtrim($answerText, '; ');
            }

            $csvData[] = $row;
        }


        $filename = 'ankieta_' . $survey->getId() . '_' . date('Y-m-d') . '.csv';

        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $output = fopen('php://temp', 'w+');


        fputs($output, "\xEF\xBB\xBF");

        foreach ($csvData as $row) {
            fputcsv($output, $row, ';');
        }

        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }
}