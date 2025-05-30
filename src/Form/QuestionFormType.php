<?php

namespace App\Form;

use App\Entity\Question;
use App\Entity\QuestionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => 'Treść pytania',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź treść pytania...'
                ]
            ])
            ->add('questionType', EntityType::class, [
                'class' => QuestionType::class,
                'choice_label' => 'label',
                'choice_value' => 'id',
                'label' => 'Typ pytania',
                'placeholder' => 'Wybierz typ pytania',
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}