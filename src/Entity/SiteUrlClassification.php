<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SiteUrlClassification
 *
 * @ORM\Table(name="site_url_classification")
 * @ORM\Entity(repositoryClass="App\Repository\SiteUrlClassificationRepository")
 */
class SiteUrlClassification
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
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50)
     */
    private $type; // e.g., 'money', 'info', 'trust', 'other'

    /**
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=50)
     */
    private $sourceType; // e.g., 'news', 'category', 'tag', 'static'

    /**
     * @var int
     *
     * @ORM\Column(name="source_id", type="integer", nullable=true)
     */
    private $sourceId;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return SiteUrlClassification
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
     * Set type
     *
     * @param string $type
     * @return SiteUrlClassification
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set sourceType
     *
     * @param string $sourceType
     * @return SiteUrlClassification
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * Get sourceType
     *
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * Set sourceId
     *
     * @param int $sourceId
     * @return SiteUrlClassification
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="inbound_links", type="integer", options={"default" : 0})
     */
    private $inboundLinks = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="outbound_links", type="integer", options={"default" : 0})
     */
    private $outboundLinks = 0;

    /**
     * Set inboundLinks
     *
     * @param int $inboundLinks
     * @return SiteUrlClassification
     */
    public function setInboundLinks($inboundLinks)
    {
        $this->inboundLinks = (int)$inboundLinks;

        return $this;
    }

    /**
     * Get inboundLinks
     *
     * @return int
     */
    public function getInboundLinks()
    {
        return $this->inboundLinks;
    }

    /**
     * Set outboundLinks
     *
     * @param int $outboundLinks
     * @return SiteUrlClassification
     */
    public function setOutboundLinks($outboundLinks)
    {
        $this->outboundLinks = (int)$outboundLinks;

        return $this;
    }

    /**
     * Get outboundLinks
     *
     * @return int
     */
    public function getOutboundLinks()
    {
        return $this->outboundLinks;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return SiteUrlClassification
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
