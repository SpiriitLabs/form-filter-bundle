<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Event\Subscriber;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;
use Spiriit\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Register listeners to compute conditions to be applied on a Doctrine ORM query builder.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineORMSubscriber extends AbstractDoctrineSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // spiriit form filter types
            'spiriit_form_filter.apply.orm.filter_boolean' => ['filterBoolean'],
            'spiriit_form_filter.apply.orm.filter_checkbox' => ['filterCheckbox'],
            'spiriit_form_filter.apply.orm.filter_choice' => ['filterValue'],
            'spiriit_form_filter.apply.orm.filter_date' => ['filterDate'],
            'spiriit_form_filter.apply.orm.filter_date_range' => ['filterDateRange'],
            'spiriit_form_filter.apply.orm.filter_datetime' => ['filterDateTime'],
            'spiriit_form_filter.apply.orm.filter_datetime_range' => ['filterDateTimeRange'],
            'spiriit_form_filter.apply.orm.filter_entity' => ['filterEntity'],
            'spiriit_form_filter.apply.orm.filter_enum' => ['filterEnum'],
            'spiriit_form_filter.apply.orm.filter_number' => ['filterNumber'],
            'spiriit_form_filter.apply.orm.filter_number_range' => ['filterNumberRange'],
            'spiriit_form_filter.apply.orm.filter_text' => ['filterText'],
            // Symfony types
            'spiriit_form_filter.apply.orm.text' => ['filterText'],
            'spiriit_form_filter.apply.orm.email' => ['filterValue'],
            'spiriit_form_filter.apply.orm.integer' => ['filterValue'],
            'spiriit_form_filter.apply.orm.money' => ['filterValue'],
            'spiriit_form_filter.apply.orm.number' => ['filterValue'],
            'spiriit_form_filter.apply.orm.percent' => ['filterValue'],
            'spiriit_form_filter.apply.orm.search' => ['filterValue'],
            'spiriit_form_filter.apply.orm.url' => ['filterValue'],
            'spiriit_form_filter.apply.orm.choice' => ['filterValue'],
            'spiriit_form_filter.apply.orm.entity' => ['filterEntity'],
            'spiriit_form_filter.apply.orm.country' => ['filterValue'],
            'spiriit_form_filter.apply.orm.language' => ['filterValue'],
            'spiriit_form_filter.apply.orm.locale' => ['filterValue'],
            'spiriit_form_filter.apply.orm.timezone' => ['filterValue'],
            'spiriit_form_filter.apply.orm.date' => ['filterDate'],
            'spiriit_form_filter.apply.orm.datetime' => ['filterDate'],
            'spiriit_form_filter.apply.orm.birthday' => ['filterDate'],
            'spiriit_form_filter.apply.orm.checkbox' => ['filterValue'],
            'spiriit_form_filter.apply.orm.radio' => ['filterValue'],
        ];
    }

    /**
     * @param GetFilterConditionEvent $event
     * @throws \Exception
     */
    public function filterEntity(GetFilterConditionEvent $event)
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (is_object($values['value'])) {
            $paramName = $this->generateParameterName($event->getField());
            $filterField = $event->getField();

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $event->getQueryBuilder();

            if ($dqlFrom = $event->getQueryBuilder()->getDQLPart('from')) {
                $rootPart = reset($dqlFrom);
                $fieldName = \str_replace(\sprintf('%s.', $rootPart->getAlias()), null, $event->getField());
                $metadata = $queryBuilder->getEntityManager()->getClassMetadata($rootPart->getFrom());

                if (isset($metadata->associationMappings[$fieldName]) && (!$metadata->associationMappings[$fieldName]['isOwningSide'] || $metadata->associationMappings[$fieldName]['type'] === ClassMetadataInfo::MANY_TO_MANY)) {
                    if (!$event->getFilterQuery()->hasJoinAlias($fieldName)) {
                        $queryBuilder->leftJoin($event->getField(), $fieldName);
                    }

                    $filterField = $fieldName;
                }
            }

            if ($values['value'] instanceof Collection) {
                $ids = [];

                foreach ($values['value'] as $value) {
                    $ids[] = $this->getEntityIdentifier($value, $queryBuilder->getEntityManager());
                }

                if (count($ids) > 0) {
                    $event->setCondition(
                        $expr->in($filterField, ':' . $paramName),
                        [$paramName => [$ids, \is_int($ids[0]) ? ArrayParameterType::INTEGER : ArrayParameterType::STRING]]
                    );
                }
            } else {
                $id = $this->getEntityIdentifier($values['value'], $queryBuilder->getEntityManager());

                $event->setCondition(
                    $expr->eq($filterField, ':' . $paramName),
                    [$paramName => [$id, \is_int($id) ? Types::INTEGER : Types::STRING]]
                );
            }
        }
    }

    /**
     * @param object $value
     * @return integer
     * @throws \RuntimeException
     */
    protected function getEntityIdentifier($value, EntityManagerInterface $em)
    {
        $class = get_class($value);
        $metadata = $em->getClassMetadata($class);

        if ($metadata->isIdentifierComposite) {
            throw new \RuntimeException(sprintf('Composite identifier is not supported by FilterEntityType.', $class));
        }

        $identifierValues = $metadata->getIdentifierValues($value);

        if (empty($identifierValues)) {
            throw new \RuntimeException(sprintf('Can\'t get identifier value for class "%s".', $class));
        }

        return array_shift($identifierValues);
    }
}
