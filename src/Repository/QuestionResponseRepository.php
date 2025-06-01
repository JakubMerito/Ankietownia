<?php

namespace App\Repository;

use App\Entity\QuestionResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionResponse>
 */
class QuestionResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionResponse::class);
    }

    /**
     * Znajdź odpowiedzi dla konkretnego pytania
     */
    public function findByQuestion($question): array
    {
        return $this->createQueryBuilder('qr')
            ->join('qr.surveyResponse', 'sr')
            ->andWhere('qr.question = :question')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('question', $question)
            ->setParameter('completed', true)
            ->orderBy('qr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Policz odpowiedzi dla opcji pytania
     */
    public function countByQuestionOption($questionOption): int
    {
        return $this->createQueryBuilder('qr')
            ->select('COUNT(qr.id)')
            ->join('qr.surveyResponse', 'sr')
            ->andWhere('qr.questionOption = :option')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('option', $questionOption)
            ->setParameter('completed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Statystyki dla pytania jednokrotnego wyboru
     */
    public function getStatisticsForSingleChoice($question): array
    {
        return $this->createQueryBuilder('qr')
            ->select('qo.text as option_text, COUNT(qr.id) as count')
            ->join('qr.questionOption', 'qo')
            ->join('qr.surveyResponse', 'sr')
            ->andWhere('qr.question = :question')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('question', $question)
            ->setParameter('completed', true)
            ->groupBy('qo.id')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Odpowiedzi tekstowe dla pytania
     */
    public function getTextResponsesForQuestion($question): array
    {
        return $this->createQueryBuilder('qr')
            ->select('qr.textResponse, qr.createdAt')
            ->join('qr.surveyResponse', 'sr')
            ->andWhere('qr.question = :question')
            ->andWhere('qr.textResponse IS NOT NULL')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('question', $question)
            ->setParameter('completed', true)
            ->orderBy('qr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}