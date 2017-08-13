<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Goods
 *
 * @ORM\Table(name="goods", indexes={
 *     @ORM\Index(name="externalId", columns={"externalId"}),
 *     @ORM\Index(name="groupId", columns={"groupId"}),
 *     @ORM\Index(name="alias", columns={"alias"}),
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GoodsRepository")
 */
class Goods
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Offer", inversedBy="goods", cascade={"persist"})
     * @ORM\JoinColumn(name="offerId", referencedColumnName="id")
     **/
    private $Offer;

    /**
     * @ORM\ManyToOne(targetEntity="Vendor", inversedBy="Goods")
     * @ORM\JoinColumn(name="vendorId", referencedColumnName="id")
     */
    private $Vendor;

    /**
     * @ORM\ManyToMany(targetEntity="GoodsParamValue", inversedBy="Goods")
     * @ORM\JoinTable(name="goodsParamValueLink")
     */
    private $GoodsParamValues;

    /**
     * @var string
     *
     * @ORM\Column(name="externalId", type="string")
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", unique=true)
     */
    private $alias;

    /**
     * @var int
     *
     * @ORM\Column(name="groupId", type="string", nullable=true)
     */
    private $groupId;

    /**
     * @var string
     *
     * @ORM\Column(name="vendorCode", type="string", length=255, nullable=true)
     */
    private $vendorCode;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=255, nullable=true)
     */
    private $category;

    /**
     * @var string
     *
     * @ORM\Column(name="model", type="string", length=255, nullable=true)
     */
    private $model;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="currency", type="string", length=255, nullable=true)
     */
    private $currency;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", nullable=true)
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="oldPrice", type="float", nullable=true)
     */
    private $oldPrice;

    /**
     * @var json_encode
     *
     * @ORM\Column(name="pictures", type="json_array", nullable=true)
     */
    private $pictures;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=255, nullable=true)
     */
    private $version;

    /**
     * @var bool
     *
     * @ORM\Column(name="isDelete", type="boolean")
     */
    private $isDelete;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->GoodsParamValues = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set externalId
     *
     * @param string $externalId
     *
     * @return Goods
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * Get externalId
     *
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * Set groupId
     *
     * @param string $groupId
     *
     * @return Goods
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return string
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set vendorCode
     *
     * @param string $vendorCode
     *
     * @return Goods
     */
    public function setVendorCode($vendorCode)
    {
        $this->vendorCode = $vendorCode;

        return $this;
    }

    /**
     * Get vendorCode
     *
     * @return string
     */
    public function getVendorCode()
    {
        return $this->vendorCode;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return Goods
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set model
     *
     * @param string $model
     *
     * @return Goods
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get model
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Goods
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
     * Set description
     *
     * @param string $description
     *
     * @return Goods
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Goods
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return Goods
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set oldPrice
     *
     * @param float $oldPrice
     *
     * @return Goods
     */
    public function setOldPrice($oldPrice)
    {
        $this->oldPrice = $oldPrice;

        return $this;
    }

    /**
     * Get oldPrice
     *
     * @return float
     */
    public function getOldPrice()
    {
        return $this->oldPrice;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Goods
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set isDelete
     *
     * @param boolean $isDelete
     *
     * @return Goods
     */
    public function setIsDelete($isDelete)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete
     *
     * @return boolean
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * Set offer
     *
     * @param \AppBundle\Entity\Offer $offer
     *
     * @return Goods
     */
    public function setOffer(\AppBundle\Entity\Offer $offer = null)
    {
        $this->Offer = $offer;

        return $this;
    }

    /**
     * Get offer
     *
     * @return \AppBundle\Entity\Offer
     */
    public function getOffer()
    {
        return $this->Offer;
    }

    /**
     * Set vendor
     *
     * @param \AppBundle\Entity\Vendor $vendor
     *
     * @return Goods
     */
    public function setVendor(\AppBundle\Entity\Vendor $vendor = null)
    {
        $this->Vendor = $vendor;

        return $this;
    }

    /**
     * Get vendor
     *
     * @return \AppBundle\Entity\Vendor
     */
    public function getVendor()
    {
        return $this->Vendor;
    }

    /**
     * Add goodsParamValue
     *
     * @param \AppBundle\Entity\GoodsParamValue $goodsParamValue
     *
     * @return Goods
     */
    public function addGoodsParamValue(\AppBundle\Entity\GoodsParamValue $goodsParamValue)
    {
        $this->GoodsParamValues[] = $goodsParamValue;

        return $this;
    }

    /**
     * Remove goodsParamValue
     *
     * @param \AppBundle\Entity\GoodsParamValue $goodsParamValue
     */
    public function removeGoodsParamValue(\AppBundle\Entity\GoodsParamValue $goodsParamValue)
    {
        $this->GoodsParamValues->removeElement($goodsParamValue);
    }

    /**
     * Get goodsParamValues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGoodsParamValues()
    {
        return $this->GoodsParamValues;
    }

    /**
     * Set alias
     *
     * @param string $alias
     *
     * @return Goods
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set version
     *
     * @param string $version
     *
     * @return Goods
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set pictures
     *
     * @param array $pictures
     *
     * @return Goods
     */
    public function setPictures($pictures)
    {
        $this->pictures = $pictures;

        return $this;
    }

    /**
     * Get pictures
     *
     * @return array
     */
    public function getPictures()
    {
        return $this->pictures;
    }
}
