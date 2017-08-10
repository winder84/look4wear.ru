<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GoodsParam
 *
 * @ORM\Table(name="goods_param")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GoodsParamRepository")
 */
class GoodsParam
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="GoodsParamValue", mappedBy="GoodsParam")
     */
    private $goodsParamValues;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->goodsParamValues = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set name
     *
     * @param string $name
     *
     * @return GoodsParam
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add goodsParamValue
     *
     * @param \AppBundle\Entity\GoodsParamValue $goodsParamValue
     *
     * @return GoodsParam
     */
    public function addGoodsParamValue(\AppBundle\Entity\GoodsParamValue $goodsParamValue)
    {
        $this->goodsParamValues[] = $goodsParamValue;

        return $this;
    }

    /**
     * Remove goodsParamValue
     *
     * @param \AppBundle\Entity\GoodsParamValue $goodsParamValue
     */
    public function removeGoodsParamValue(\AppBundle\Entity\GoodsParamValue $goodsParamValue)
    {
        $this->goodsParamValues->removeElement($goodsParamValue);
    }

    /**
     * Get goodsParamValues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGoodsParamValues()
    {
        return $this->goodsParamValues;
    }
}
