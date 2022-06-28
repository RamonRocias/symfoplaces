<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Service\SimpleSearchService;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Form\SearchFormType;


class SearchFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('campo', ChoiceType::class, [
            'choices' => $options['field_choices']
        ])
        ->add('valor', TextType::class)
        ->add('orden', Choicetype::class,  [
            'choices' => $options['order_choices']
            
        ])
        ->add('sentido', ChoiceType::class, [
            'expanded' => true,
            'multiple' => false,
            'choices' => [
                'Asc' => 'ASC',
                'Desc' => 'DESC'
            ],
            'choice_attr'=> [
                'Asc' => [
                    'checked' => 'checked'
                ]
            ]
        ])
        ->add('limite', NumberType::class,['required'=>true, 'html5'=>true,
            'attr' =>['min'=>1,'max' => 25]
        ])
        ->add('Buscar', SubmitType::class,[
            'attr' =>['class'=>'btn btn-primary my-3']
        ]);
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // establece los valores por defecto para las opciones del form
            'data_class' 	=> SimpleSearchService::class,
            'field_choices' => ['id' => 'id'],
            'order_choices' => ['id' => 'id']
        ]);
    }
}