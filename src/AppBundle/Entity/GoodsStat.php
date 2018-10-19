<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GoodsStat
 *
 * @ORM\Table(name="goods_stat")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GoodsStatRepository")
 */
class GoodsStat
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
     * @var string
     *
     * @ORM\Column(name="clientIp", type="string", length=255)
     */
    private $clientIp;

    /**
     * @var string|null
     *
     * @ORM\Column(name="clientUserAgent", type="string", length=255, nullable=true)
     */
    private $clientUserAgent;

    /**
     * @var int
     *
     * @ORM\Column(name="goodsId", type="integer")
     */
    private $goodsId;

    /**
     * @var array|null
     *
     * @ORM\Column(name="additionalInfo", type="json_array", nullable=true)
     */
    private $additionalInfo;


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set clientIp.
     *
     * @param string $clientIp
     *
     * @return GoodsStat
     */
    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;

        return $this;
    }

    /**
     * Get clientIp.
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * Set clientUserAgent.
     *
     * @param string|null $clientUserAgent
     *
     * @return GoodsStat
     */
    public function setClientUserAgent($clientUserAgent = null)
    {
        $this->clientUserAgent = $clientUserAgent;

        return $this;
    }

    /**
     * Get clientUserAgent.
     *
     * @return string|null
     */
    public function getClientUserAgent()
    {
        return $this->clientUserAgent;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return GoodsStat
     */
    public function setGoodsId($goodsId)
    {
        $this->goodsId = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goodsId;
    }

    /**
     * Set additionalInfo.
     *
     * @param array|null $additionalInfo
     *
     * @return GoodsStat
     */
    public function setAdditionalInfo($additionalInfo = null)
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    /**
     * Get additionalInfo.
     *
     * @return array|null
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }
}
