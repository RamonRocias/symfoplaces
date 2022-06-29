<?php

namespace App\Form;

use App\Entity\Comment;
use App\Form\Custom\DataListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Repository\CommentRepository;

class PlaceAddCommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', DataListType::class, [
                'class' =>Comment::class,
                'choice_label' =>'text',
                'label' => 'Añadir comentario',
                
                // para definir el orden de los resultados
                'query_builder' => function(CommentRepository $cr){
                return $cr->createQueryBuilder('a')
                ->orderBy('a.date','ASC');
                }
            ])
            ->add('Add', SubmitType::class, [
                'label' =>'Añadir comentario',
                'attr' => ['class'=>'btn btn-success my-3']
            ])
            ->setAction($options['action']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }
}
