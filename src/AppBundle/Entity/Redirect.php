<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Redirect
 *
 * @ORM\Table(name="redirects")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\RedirectRepository")
 * @UniqueEntity("oldUrl")
 */
class Redirect
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
     * @Assert\NotBlank()
     * @ORM\Column(name="old_url", type="string", length=255, unique=true)
     */
    private $oldUrl;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="new_url", type="string", length=255)
     */
    private $newUrl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable", type="boolean")
     */
    private $enable = true;

    /**
     * @var int
     *
     * @ORM\Column(name="status_code", type="integer")
     */
    private $statusCode = 301;

    /**
     * @var int
     *
     * @ORM\Column(name="hits", type="integer")
     */
    private $hits = 0;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="last_accessed_at", type="datetime", nullable=true)
     */
    private $lastAccessedAt;

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
     * Set oldUrl
     *
     * @param string $oldUrl
     *
     * @return Redirect
     */
    public function setOldUrl($oldUrl)
    {
        $this->oldUrl = $oldUrl;

        return $this;
    }

    /**
     * Get oldUrl
     *
     * @return string
     */
    public function getOldUrl()
    {
        return $this->oldUrl;
    }

    /**
     * Set newUrl
     *
     * @param string $newUrl
     *
     * @return Redirect
     */
    public function setNewUrl($newUrl)
    {
        $this->newUrl = $newUrl;

        return $this;
    }

    /**
     * Get newUrl
     *
     * @return string
     */
    public function getNewUrl()
    {
        return $this->newUrl;
    }

    /**
     * Set enable
     *
     * @param boolean $enable
     *
     * @return Redirect
     */
    public function setEnable($enable)
    {
        $this->enable = $enable;

        return $this;
    }

    /**
     * Get enable
     *
     * @return boolean
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * Set statusCode
     *
     * @param integer $statusCode
     *
     * @return Redirect
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get statusCode
     *
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set hits
     *
     * @param integer $hits
     *
     * @return Redirect
     */
    public function setHits($hits)
    {
        $this->hits = $hits;

        return $this;
    }

    /**
     * Get hits
     *
     * @return integer
     */
    public function getHits()
    {
        return $this->hits;
    }

    /**
     * Set lastAccessedAt
     *
     * @param \DateTime|null $lastAccessedAt
     *
     * @return Redirect
     */
    public function setLastAccessedAt($lastAccessedAt)
    {
        $this->lastAccessedAt = $lastAccessedAt;

        return $this;
    }

    /**
     * Get lastAccessedAt
     *
     * @return \DateTime|null
     */
    public function getLastAccessedAt()
    {
        return $this->lastAccessedAt;
    }
}
