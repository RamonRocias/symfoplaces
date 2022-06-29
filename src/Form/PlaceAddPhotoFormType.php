<?php

namespace App\Form;

use App\Entity\Photo;
use App\Form\Custom\DataListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Repository\PhotoRepository;

class PlaceAddPhotoFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photo', DataListType::class, [
                'class' =>Photo::class,
                'choice_label' =>'title',
                'label' => 'Añadir photo',
                
                // para definir el orden de los resultados
                'query_builder' => function(PhotoRepository $pr){
                return $pr->createQueryBuilder('a')
                ->orderBy('a.title','ASC');
                }
            ])
            ->add('Add', SubmitType::class, [
                'label' =>'Añadir Photo',
                'attr' => ['class'=>'btn btn-success my-3']
            ])
            ->setAction($options['action']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Photo::class,
        ]);
    }
}
