<?php

namespace App\Entity;

use App\Repository\SurveyResponseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SurveyResponseRepository::class)]
class SurveyResponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'surveyResponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Survey $survey = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\Column]
    private bool $isCompleted = false;

    #[ORM\OneToMany(mappedBy: 'surveyResponse', targetEntity: QuestionResponse::class, cascade: ['persist', 'remove'])]
    private Collection $questionResponses;

    public function __construct()
    {
        $this->questionResponses = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): static
    {
        $this->survey = $survey;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): static
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }

    /**
     * @return Collection<int, QuestionResponse>
     */
    public function getQuestionResponses(): Collection
    {
        return $this->questionResponses;
    }

    public function addQuestionResponse(QuestionResponse $questionResponse): static
    {
        if (!$this->questionResponses->contains($questionResponse)) {
            $this->questionResponses->add($questionResponse);
            $questionResponse->setSurveyResponse($this);
        }
        return $this;
    }

    public function removeQuestionResponse(QuestionResponse $questionResponse): static
    {
        if ($this->questionResponses->removeElement($questionResponse)) {
            if ($questionResponse->getSurveyResponse() === $this) {
                $questionResponse->setSurveyResponse(null);
            }
        }
        return $this;
    }
}