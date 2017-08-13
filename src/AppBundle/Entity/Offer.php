<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offer
 *
 * @ORM\Table(name="offer")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OfferRepository")
 */
class Offer
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
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Goods", mappedBy="offer")
     **/
    private $Goods;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
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
     * @ORM\Column(name="xmlParseUrl", type="string", length=255, nullable=true)
     */
    private $xmlParseUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="deliveryUrl", type="string", length=255, nullable=true)
     */
    private $deliveryUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="paymentUrl", type="string", length=255, nullable=true)
     */
    private $paymentUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="alias", type="string", unique=true, length=255)
     */
    private $alias;

    /**
     * @var int
     *
     * @ORM\Column(name="version", type="integer", unique=true)
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
     * Set name
     *
     * @param string $name
     *
     * @return Offer
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
     * @return Offer
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
     * Set xmlParseUrl
     *
     * @param string $xmlParseUrl
     *
     * @return Offer
     */
    public function setXmlParseUrl($xmlParseUrl)
    {
        $this->xmlParseUrl = $xmlParseUrl;

        return $this;
    }

    /**
     * Get xmlParseUrl
     *
     * @return string
     */
    public function getXmlParseUrl()
    {
        return $this->xmlParseUrl;
    }

    /**
     * Set deliveryUrl
     *
     * @param string $deliveryUrl
     *
     * @return Offer
     */
    public function setDeliveryUrl($deliveryUrl)
    {
        $this->deliveryUrl = $deliveryUrl;

        return $this;
    }

    /**
     * Get deliveryUrl
     *
     * @return string
     */
    public function getDeliveryUrl()
    {
        return $this->deliveryUrl;
    }

    /**
     * Set paymentUrl
     *
     * @param string $paymentUrl
     *
     * @return Offer
     */
    public function setPaymentUrl($paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;

        return $this;
    }

    /**
     * Get paymentUrl
     *
     * @return string
     */
    public function getPaymentUrl()
    {
        return $this->paymentUrl;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return Offer
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
     * Set alias
     *
     * @param string $alias
     *
     * @return Offer
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
     * @param integer $version
     *
     * @return Offer
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Add good
     *
     * @param \AppBundle\Entity\Goods $good
     *
     * @return Offer
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
     * Set isDelete
     *
     * @param boolean $isDelete
     *
     * @return Offer
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
}
