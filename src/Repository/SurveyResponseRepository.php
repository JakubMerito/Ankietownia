<?php

namespace App\Repository;

use App\Entity\SurveyResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SurveyResponse>
 */
class SurveyResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyResponse::class);
    }

    /**
     * Znajdź wszystkie odpowiedzi dla danej ankiety
     */
    public function findBySurvey($survey): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.survey = :survey')
            ->setParameter('survey', $survey)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź ukończone odpowiedzi dla danej ankiety
     */
    public function findCompletedBySurvey($survey): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.survey = :survey')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('survey', $survey)
            ->setParameter('completed', true)
            ->orderBy('sr.completedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Policz odpowiedzi dla ankiety
     */
    public function countBySurvey($survey): int
    {
        return $this->createQueryBuilder('sr')
            ->select('COUNT(sr.id)')
            ->andWhere('sr.survey = :survey')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('survey', $survey)
            ->setParameter('completed', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Znajdź odpowiedzi z ostatnich X dni
     */
    public function findRecentBySurvey($survey, int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('sr')
            ->andWhere('sr.survey = :survey')
            ->andWhere('sr.createdAt >= :date')
            ->andWhere('sr.isCompleted = :completed')
            ->setParameter('survey', $survey)
            ->setParameter('date', $date)
            ->setParameter('completed', true)
            ->orderBy('sr.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}