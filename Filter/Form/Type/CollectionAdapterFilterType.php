<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter\Form\Type;

use ArrayAccess;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

/**
 * Filter to used to simulate a collection and get filter to apply on collection elements.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class CollectionAdapterFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // update collection to only get one element
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();

            if (null === $data) {
                $data = $options['default_data'];
                $event->setData($data);
            }

            if (!is_array($data) && !($data instanceof Traversable && $data instanceof ArrayAccess)) {
                throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
            }

            // remove all rows
            foreach ($form as $name => $child) {
                $form->remove($name);
            }

            // then add one row that will be used for filtering
            $index = 0;
            $childOptions = array_replace(['property_path' => sprintf('[%d]', $index)], $options['entry_options']);

            $form->add($index, $options['entry_type'], $childOptions);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['entry_type' => null, 'entry_options' => [], 'default_data' => []]);

        $resolver->setRequired(['entry_type']);
    }

    public function getParent(): ?string
    {
        return SharedableFilterType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'filter_collection_adapter';
    }
}
