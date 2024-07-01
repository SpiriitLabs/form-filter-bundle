<?php

declare(strict_types=1);

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return EnumType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'filter_enum';
    }
}
