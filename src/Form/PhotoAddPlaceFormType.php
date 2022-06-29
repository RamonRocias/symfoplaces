<?php

namespace App\Form;

use App\Entity\Place;
use App\Form\Custom\DataListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Repository\PlaceRepository;

class PhotoAddPlaceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('place', DataListType::class, [
                'class' =>Place::class,
                'choice_label' =>'name',
                'label' => 'Indicar el lugar',
                
                // para definir el orden de los resultados
                'query_builder' => function(PlaceRepository $pr){
                return $pr->createQueryBuilder('a')
                ->orderBy('a.name','ASC');
                }
            ])
            ->add('Add', SubmitType::class, [
                'label' =>'Añadir Lugar',
                'attr' => ['class'=>'btn btn-success my-3']
            ])
            ->setAction($options['action']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,
        ]);
    }
}
