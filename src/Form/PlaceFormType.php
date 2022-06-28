<?php

namespace App\Form;

use App\Entity\Place;
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

class PlaceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class)
        ->add('valoracion', NumberType::class,['empty_data'=>0, 'html5'=>TRUE,'attr'=>['min'=>0,'max'=>5, 'step'=>1]])
        ->add('city', TextType::class)
        ->add('country', TextType::class)
        ->add('continent', TextType::class)
        ->add('type', TextType::class)
        ->add('description', TextareaType::class,['empty_data'=>'', 'required'=>false])
        ->add('caratula', FileType::class, [
            'label' => 'Carátula (jpg, png o gif):',
            'attr' => ['class'=>'file-with-preview'],
            'required'=>false,
            'data_class' =>NULL,
            'constraints' => [new File([
                'maxSize'=> '2048K',
                'mimeTypes' =>['image/jpeg', 'image/png', 'image/gif'],
                'mimeTypesMessage'=> 'Debes subir una imagen png, jpg o gif'
            ])
            ]
        ])
        //  ->add('user')
        ->add('Guardar',SubmitType::class, ['attr'=>['class'=>'btn btn-success my-3']]);    //añadido por mi.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Place::class,   // Clase de la entidad
            'csrf_protection'=> true,   //habilita la
            'csrf_field_name' => '_token',  // nombre del campo hidden
            'csrf_token_id' => 'ramon', // semilla para generar el token
        ]);
    }
}
