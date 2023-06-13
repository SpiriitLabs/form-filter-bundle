<?php

namespace Spiriit\Bundle\FormFilterBundle\Event\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *  Register listeners to compute conditions to be applied on a Doctrine DBAL query builder.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DoctrineDBALSubscriber extends AbstractDoctrineSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Lexik form filter types
            'spiriit_form_filter.apply.dbal.filter_boolean' => ['filterBoolean'],
            'spiriit_form_filter.apply.dbal.filter_checkbox' => ['filterCheckbox'],
            'spiriit_form_filter.apply.dbal.filter_choice' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.filter_date' => ['filterDate'],
            'spiriit_form_filter.apply.dbal.filter_date_range' => ['filterDateRange'],
            'spiriit_form_filter.apply.dbal.filter_datetime' => ['filterDateTime'],
            'spiriit_form_filter.apply.dbal.filter_datetime_range' => ['filterDateTimeRange'],
            'spiriit_form_filter.apply.dbal.filter_number' => ['filterNumber'],
            'spiriit_form_filter.apply.dbal.filter_number_range' => ['filterNumberRange'],
            'spiriit_form_filter.apply.dbal.filter_text' => ['filterText'],
            // Symfony field types
            'spiriit_form_filter.apply.dbal.text' => ['filterText'],
            'spiriit_form_filter.apply.dbal.email' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.integer' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.money' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.number' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.percent' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.search' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.url' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.choice' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.country' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.language' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.locale' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.timezone' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.date' => ['filterDate'],
            'spiriit_form_filter.apply.dbal.datetime' => ['filterDate'],
            'spiriit_form_filter.apply.dbal.birthday' => ['filterDate'],
            'spiriit_form_filter.apply.dbal.checkbox' => ['filterValue'],
            'spiriit_form_filter.apply.dbal.radio' => ['filterValue'],
        ];
    }
}
