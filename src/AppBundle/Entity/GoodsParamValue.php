<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GoodsParamValue
 *
 * @ORM\Table(name="goods_param_value")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GoodsParamValueRepository")
 */
class GoodsParamValue
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
     * @ORM\ManyToMany(targetEntity="Goods", mappedBy="GoodsParamValues")
     */
    private $Goods;

    /**
     * @ORM\ManyToOne(targetEntity="GoodsParam", inversedBy="GoodsParamValues")
     * @ORM\JoinColumn(name="goodsParamId", referencedColumnName="id")
     */
    private $GoodsParam;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255)
     */
    private $value;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Goods = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set value
     *
     * @param string $value
     *
     * @return GoodsParamValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Add good
     *
     * @param \AppBundle\Entity\Goods $good
     *
     * @return GoodsParamValue
     */
    public function addGood(\AppBundle\Entity\Goods $good)
    {
        $this->Goods[] = $good;

        return $this;
    }

    /**
     * Remove good
     *
     * @param \AppBundle\Entity\Goods $good
     */
    public function removeGood(\AppBundle\Entity\Goods $good)
    {
        $this->Goods->removeElement($good);
    }

    /**
     * Get goods
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGoods()
    {
        return $this->Goods;
    }

    /**
     * Set goodsParam
     *
     * @param \AppBundle\Entity\GoodsParam $goodsParam
     *
     * @return GoodsParamValue
     */
    public function setGoodsParam(\AppBundle\Entity\GoodsParam $goodsParam = null)
    {
        $this->GoodsParam = $goodsParam;

        return $this;
    }

    /**
     * Get goodsParam
     *
     * @return \AppBundle\Entity\GoodsParam
     */
    public function getGoodsParam()
    {
        return $this->GoodsParam;
    }
}
