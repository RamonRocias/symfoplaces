<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', EmailType::class,[
            'required' => TRUE
        ])
/*        
        ->add('agreeTerms', CheckboxType::class, [
            'mapped' => false,
            'constraints' => [
                new IsTrue([
                    'message' => 'You should agree to our terms.',
                ]),
            ],
        ])
*/
/*  La actualización del password no se hace en este formulario se hace en el formulario de ResetPassword
        ->add('plainPassword', PasswordType::class, [
            // instead of being set onto the object directly,
            // this is read and encoded in the controller
            'mapped' => false,
            'attr' => ['autocomplete' => 'new-password'],
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter a password',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                    // max length allowed by Symfony for security reasons
                    'max' => 4096,
                ]),
            ],
        ])
*/        
        ->add('displayname', TextType::class)
        ->add('name', TextType::class,[
            'required' => false
        ])
        ->add('phone', TelType::class,[
            'required' => false
        ])
        
        ->add('city', TextType::class, [
            'required' => false
        ])
        ->add('country', TextType::class, [
            'required' => false
        ])
        
        ->add( 'continent', TextType::class,[
            'required' => false,
        ])
        ->add('nacimiento', DateType::class,['widget'=>'single_text',
            'required' => false
        ])
        ->add('fotografia', FileType::class,[
            'required' => false,
            'data_class' => NULL,
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                    'mimeTypesMessage' => 'Debes subir una imagen png, jpg o gif'
                ])
            ]
        ])
        ->add('Guardar',SubmitType::class, ['attr'=>['class'=>'btn btn-success my-3']]);    //añadido por mi.
        
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
