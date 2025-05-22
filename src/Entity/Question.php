<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Survey $survey = null;

    /**
     * @var Collection<int, QuestionOption>
     */
    #[ORM\OneToMany(targetEntity: QuestionOption::class, mappedBy: 'question', orphanRemoval: true)]
    private Collection $questionOptions;

    public function __construct()
    {
        $this->questionOptions = new ArrayCollection();
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

    /**
     * @return Collection<int, QuestionOption>
     */
    public function getQuestionOptions(): Collection
    {
        return $this->questionOptions;
    }

    public function addQuestionOption(QuestionOption $questionOption): static
    {
        if (!$this->questionOptions->contains($questionOption)) {
            $this->questionOptions->add($questionOption);
            $questionOption->setQuestion($this);
        }

        return $this;
    }

    public function removeQuestionOption(QuestionOption $questionOption): static
    {
        if ($this->questionOptions->removeElement($questionOption)) {
            // set the owning side to null (unless already changed)
            if ($questionOption->getQuestion() === $this) {
                $questionOption->setQuestion(null);
            }
        }

        return $this;
    }
}
