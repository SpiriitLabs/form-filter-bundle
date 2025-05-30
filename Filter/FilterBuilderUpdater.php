<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Filter;

use Closure;
use RuntimeException;
use Spiriit\Bundle\FormFilterBundle\Event\ApplyFilterConditionEvent;
use Spiriit\Bundle\FormFilterBundle\Event\FilterEvents;
use Spiriit\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Spiriit\Bundle\FormFilterBundle\Event\PrepareEvent;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionBuilder;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionBuilderInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Condition\ConditionNodeInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\DataExtractor\FormDataExtractorInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\CollectionAdapterFilterType;
use Spiriit\Bundle\FormFilterBundle\Filter\Form\Type\EmbeddedFilterTypeInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\Query\QueryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Build a query from a given form object, we basically add conditions to the Doctrine query builder.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 * @author Jeremy Barthe <j.barthe@lexik.fr>
 */
class FilterBuilderUpdater implements FilterBuilderUpdaterInterface
{
    protected FormDataExtractorInterface $dataExtractor;

    protected EventDispatcherInterface $dispatcher;

    /**
     * @var array
     */
    protected RelationsAliasBag $parts;

    /**
     * @var ConditionBuilder
     */
    protected $conditionBuilder;

    /**
     * Constructor
     */
    public function __construct(FormDataExtractorInterface $dataExtractor, EventDispatcherInterface $dispatcher)
    {
        $this->dataExtractor = $dataExtractor;
        $this->dispatcher = $dispatcher;
        $this->parts = new RelationsAliasBag();
    }

    /**
     * Set joins aliases.
     */
    public function setParts(array $parts): void
    {
        $this->parts = new RelationsAliasBag($parts);
    }

    /**
     * Build a filter query.
     *
     * @param  object        $queryBuilder
     * @param  string|null   $alias
     *
     * @return object filter builder
     * @throws RuntimeException
     */
    public function addFilterConditions(FormInterface $form, $queryBuilder, $alias = null)
    {
        // create the right QueryInterface object
        $event = new PrepareEvent($queryBuilder);

        $this->dispatcher->dispatch($event, FilterEvents::PREPARE);

        if (!$event->getFilterQuery() instanceof QueryInterface) {
            throw new RuntimeException("Couldn't find any filter query object.");
        }

        $alias = $alias ?? $event->getFilterQuery()->getRootAlias();

        // init parts (= ['joins' -> 'alias']) / the root alias does not target a join
        $this->parts->add('__root__', $alias);

        // get conditions nodes defined by the 'filter_condition_builder' option
        // and add filters condition for each node
        $this->conditionBuilder = $this->getConditionBuilder($form);
        $this->addFilters($form, $event->getFilterQuery(), $alias);

        // walk condition nodes to add condition on the query builder instance
        $name = sprintf('spiriit_filter.apply_filters.%s', $event->getFilterQuery()->getEventPartName());

        $this->dispatcher->dispatch(new ApplyFilterConditionEvent($queryBuilder, $this->conditionBuilder), $name);

        $this->conditionBuilder = null;

        return $queryBuilder;
    }

    /**
     * Add filter conditions on the condition node instance.
     *
     * @param string         $alias
     *
     * @throws RuntimeException
     */
    protected function addFilters(FormInterface $form, QueryInterface $filterQuery, $alias = null)
    {
        /** @var $child FormInterface */
        foreach ($form->all() as $child) {
            $formType = $child->getConfig()->getType()->getInnerType();

            // this means we have a relation
            if ($child->getConfig()->hasAttribute('add_shared')) {
                $join = $child->getConfig()->getAttribute('filter_shared_name') ?? trim($alias . '.' . $child->getName(), '.');

                $addSharedClosure = $child->getConfig()->getAttribute('add_shared');

                if (!$addSharedClosure instanceof Closure) {
                    throw new RuntimeException('Please provide a closure to the "add_shared" option.');
                }

                $qbe = new FilterBuilderExecuter($filterQuery, $alias, $this->parts);
                $addSharedClosure($qbe);

                if (!$this->parts->has($join)) {
                    throw new RuntimeException(sprintf('No alias found for relation "%s".', $join));
                }

                $isCollection = ($formType instanceof CollectionAdapterFilterType);

                $this->addFilters($isCollection ? $child->get(0) : $child, $filterQuery, $this->parts->get($join));

                // Doctrine2 embedded object case
            } elseif ($formType instanceof EmbeddedFilterTypeInterface) {
                $this->addFilters($child, $filterQuery, $child->getConfig()->getAttribute('filter_field_name') ?? ($alias . '.' . $child->getName()));

                // inherit_data set to true
            } elseif ($child->getConfig()->getInheritData()) {
                $this->addFilters($child, $filterQuery, $alias);

                // default case
            } else {
                $condition = $this->getFilterCondition($child, $formType, $filterQuery, $alias);

                if ($condition instanceof ConditionInterface) {
                    $this->conditionBuilder->addCondition($condition);
                }
            }
        }
    }

    /**
     * Get the condition through event dispatcher.
     *
     * @param string         $alias
     * @return ConditionInterface|null
     */
    protected function getFilterCondition(FormInterface $form, AbstractType $formType, QueryInterface $filterQuery, $alias)
    {
        $values = $this->prepareFilterValues($form);
        $values += ['alias' => $alias];
        $field = $form->getConfig()->getAttribute('filter_field_name') ?? trim($values['alias'] . '.' . $form->getName(), '. ');

        $condition = null;

        // build a complete form name including parents
        $completeName = $form->getName();
        $parentForm = $form;
        do {
            $parentForm = $parentForm->getParent();
            if (
                !is_numeric($parentForm->getName())
                && $parentForm->getConfig()->getMapped()
                && !$parentForm->getConfig()->getInheritData()
            ) { // skip collection numeric index and not mapped fields and inherited data
                $completeName = $parentForm->getName() . '.' . $completeName;
            }
        } while (!$parentForm->isRoot());

        // apply the filter by using the closure set with the 'apply_filter' option
        $callable = $form->getConfig()->getAttribute('apply_filter');

        if (false === $callable) {
            return null;
        }

        if ($callable instanceof Closure) {
            $condition = $callable($filterQuery, $field, $values);
        } elseif (is_callable($callable)) {
            $condition = call_user_func($callable, $filterQuery, $field, $values);
        } else {
            // trigger a specific or a global event name
            $eventName = sprintf('spiriit_form_filter.apply.%s.%s', $filterQuery->getEventPartName(), $completeName);
            if (!$this->dispatcher->hasListeners($eventName)) {
                $eventName = sprintf('spiriit_form_filter.apply.%s.%s', $filterQuery->getEventPartName(), is_string($callable) ? $callable : $formType->getBlockPrefix());
            }

            $event = new GetFilterConditionEvent($filterQuery, $field, $values);

            $this->dispatcher->dispatch($event, $eventName);

            $condition = $event->getCondition();
        }

        // set condition path
        if ($condition instanceof ConditionInterface) {
            $condition->setName(
                trim(substr($completeName, strpos($completeName, '.')), '.') // remove first level
            );
        }

        return $condition;
    }

    /**
     * Prepare all values needed to apply the filter
     *
     * @return array
     */
    protected function prepareFilterValues(FormInterface $form)
    {
        $config = $form->getConfig();
        $values = $this->dataExtractor->extractData($form, $config->getOption('data_extraction_method', 'default'));

        if ($config->hasAttribute('filter_options')) {
            return array_merge($values, $config->getAttribute('filter_options'));
        }

        return $values;
    }

    /**
     * Get the conditon builder object for the given form.
     *
     * @return ConditionBuilderInterface
     */
    protected function getConditionBuilder(Form $form): ConditionBuilder
    {
        $builderClosure = $form->getConfig()->getAttribute('filter_condition_builder');

        $builder = new ConditionBuilder();

        if ($builderClosure instanceof Closure) {
            $builderClosure($builder);
        } else {
            $this->buildDefaultConditionNode($form, $builder->root('AND'));
        }

        return $builder;
    }

    /**
     * Create a default node hierarchy by using AND operator.
     *
     * @param string                 $parentName
     */
    protected function buildDefaultConditionNode(Form $form, ConditionNodeInterface $root, $parentName = '')
    {
        foreach ($form->all() as $child) {
            $formType = $child->getConfig()->getType()->getInnerType();

            $name = ('' !== $parentName) ? $parentName . '.' . $child->getName() : $child->getName();

            if ($child->getConfig()->hasAttribute('add_shared') || $formType instanceof EmbeddedFilterTypeInterface) {
                $isCollection = ($formType instanceof CollectionAdapterFilterType);

                $this->buildDefaultConditionNode(
                    $isCollection ? $child->get(0) : $child,
                    $root->andX(),
                    $name
                );
            } elseif ($child->getConfig()->getInheritData()) {
                $this->buildDefaultConditionNode(
                    $child,
                    $root
                );
            } else {
                $root->field($name);
            }
        }
    }
}
