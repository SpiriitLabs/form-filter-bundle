<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Filter\Doctrine;

/**
 * Filter query builder tests.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class DBALQueryBuilderUpdaterTest extends DoctrineQueryBuilderUpdater
{
    public function testBuildQuery(): void
    {
        parent::createBuildQueryTest('getSQL', [
            'SELECT i FROM item i',
            'SELECT i FROM item i WHERE i.name LIKE \'blabla\'',
            'SELECT i FROM item i WHERE (i.name LIKE \'blabla\') AND (i.position > :p_i_position)',
            'SELECT i FROM item i WHERE (i.name LIKE \'blabla\') AND (i.position > :p_i_position) AND (i.enabled = :p_i_enabled)',
            'SELECT i FROM item i WHERE (i.name LIKE \'blabla\') AND (i.position > :p_i_position) AND (i.enabled = :p_i_enabled)',
            'SELECT i FROM item i WHERE (i.name LIKE \'%blabla\') AND (i.position <= :p_i_position) AND (i.createdAt = :p_i_createdAt)',
            'SELECT i FROM item i WHERE (i.name LIKE \'%blabla\') AND (i.position <= :p_i_position) AND (i.createdAt = :p_i_createdAt)',
           ]
        );
    }

    public function testDisabledFieldQuery(): void
    {
        parent::createDisabledFieldTest('getSQL', ['SELECT i FROM item i WHERE i.position > :p_i_position']);
    }

    public function testApplyFilterOption(): void
    {
        parent::createApplyFilterOptionTest('getSQL', ['SELECT i FROM item i WHERE (i.name <> \'blabla\') AND (i.position <> 2)']);
    }

    public function testNumberRange(): void
    {
        parent::createNumberRangeTest('getSQL', ['SELECT i FROM item i WHERE (i.position > :p_i_position_left) AND (i.position < :p_i_position_right)']);
    }

    public function testNumberRangeWithSelector(): void
    {
        parent::createNumberRangeCompoundTest('getSQL', ['SELECT i FROM item i WHERE (i.position_selector > :p_i_position_selector_left) AND (i.position_selector <= :p_i_position_selector_right)']);
    }

    public function testNumberRangeDefaultValues(): void
    {
        parent::createNumberRangeDefaultValuesTest('getSQL', ['SELECT i FROM item i WHERE (i.default_position >= :p_i_default_position_left) AND (i.default_position <= :p_i_default_position_right)']);
    }

    public function testDateRange(): void
    {
        parent::createDateRangeTest('getSQL', ['SELECT i FROM item i WHERE (i.createdAt <= \'2012-05-22 23:59:59\') AND (i.createdAt >= \'2012-05-12 00:00:00\')']);
    }

    public function testDateRangeWithTimezone(): void
    {
        parent::createDateRangeWithTimezoneTest('getSQL', ['SELECT i FROM item i WHERE (i.startAt <= \'2015-10-20 18:59:59\') AND (i.startAt >= \'2015-10-19 19:00:00\')', 'SELECT i FROM item i WHERE (i.startAt <= \'2015-10-16 18:59:59\') AND (i.startAt >= \'2015-09-30 19:00:00\')']);
    }

    public function testDateTimeRange(): void
    {
        parent::createDateTimeRangeTest('getSQL', ['SELECT i FROM item i WHERE (i.updatedAt <= \'2012-06-10 22:12:00\') AND (i.updatedAt >= \'2012-05-12 14:55:00\')']);
    }

    public function testFilterStandardType(): void
    {
        parent::createFilterStandardTypeTest('getSQL', ['SELECT i FROM item i WHERE (i.name LIKE \'%hey dude%\') AND (i.position = 99)']);
    }

    protected function createDoctrineQueryBuilder()
    {
        return $this->conn
                    ->createQueryBuilder()
                    ->select('i')
                    ->from('item', 'i');
    }
}
