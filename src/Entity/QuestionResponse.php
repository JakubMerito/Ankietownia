<?php

namespace App\Entity;

use App\Repository\QuestionResponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionResponseRepository::class)]
class QuestionResponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SurveyResponse::class, inversedBy: 'questionResponses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SurveyResponse $surveyResponse = null;

    #[ORM\ManyToOne(targetEntity: Question::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    #[ORM\ManyToOne(targetEntity: QuestionOption::class)]
    private ?QuestionOption $questionOption = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textResponse = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurveyResponse(): ?SurveyResponse
    {
        return $this->surveyResponse;
    }

    public function setSurveyResponse(?SurveyResponse $surveyResponse): static
    {
        $this->surveyResponse = $surveyResponse;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;
        return $this;
    }

    public function getQuestionOption(): ?QuestionOption
    {
        return $this->questionOption;
    }

    public function setQuestionOption(?QuestionOption $questionOption): static
    {
        $this->questionOption = $questionOption;
        return $this;
    }

    public function getTextResponse(): ?string
    {
        return $this->textResponse;
    }

    public function setTextResponse(?string $textResponse): static
    {
        $this->textResponse = $textResponse;
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
}