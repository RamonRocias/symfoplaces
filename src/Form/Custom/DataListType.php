<?php

namespace App\Form\Custom;


use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
/*
use App\Entity\Actor;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
*/

class DataListType extends AbstractType
{
    public function getParent()
    {
        return EntityType::class;
    }
}
