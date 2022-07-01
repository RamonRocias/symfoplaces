<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class CommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('text', TextareaType::class)
        //->add('date', DateType::class,['widget'=>'single_text'])
        // No hace falta porque es un valor que recuperamos y no lo introduce el usuario
        //    ->add('user') 
        //     ->add('place')
        ->add('Guardar',SubmitType::class, ['attr'=>['class'=>'btn btn-success my-3']]);    
        //añadido por mi.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,   // Clase de la entidad
            'csrf_protection'=> true,   //habilita la
            'csrf_field_name' => '_token',  // nombre del campo hidden
            'csrf_token_id' => 'ramon', // semilla para generar el token
        ]);
    }
}
