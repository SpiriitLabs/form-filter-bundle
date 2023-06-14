<?php

/*
 * This file is part of the composer-write-changelogs project.
 *
 * (c) Dev Spiriit <dev@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\FormFilterBundle\Tests\Fixtures\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as Mongo;

/**
 * @Mongo\EmbeddedDocument()
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Options
{
    /**
     * @Mongo\Field(type="string")
     */
    protected $label;

    /**
     * @Mongo\Field(type="int")
     */
    protected $rank;

    /**
     * @Mongo\Field(type="date")
     */
    protected $createdAt;

    /**
     * @Mongo\ReferenceOne(name="Item")
     */
    private $item;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get rank
     *
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set rank
     *
     * @param int $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }
}
