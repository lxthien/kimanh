<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Notification
 *
 * @ORM\Table(name="notification", options={"collate"="utf8_general_ci"}, indexes={
 *     @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *     @ORM\Index(name="idx_is_read", columns={"is_read"}),
 *     @ORM\Index(name="idx_created_at", columns={"created_at"})
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NotificationRepository")
 */
class Notification
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
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50)
     * Description: 'comment_pending', 'contact_new'
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text")
     */
    private $message;

    /**
     * @var int
     *
     * @ORM\Column(name="related_id", type="integer", nullable=true)
     * Description: ID của Comment hoặc Contact
     */
    private $related_id;

    /**
     * @var string
     *
     * @ORM\Column(name="related_type", type="string", length=50, nullable=true)
     * Description: 'Comment', 'Contact'
     */
    private $related_type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_read", type="boolean", options={"default"=false})
     */
    private $is_read = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="read_at", type="datetime", nullable=true)
     */
    private $read_at;

    /**
     * Getter/Setter Methods
     */

    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getRelatedId()
    {
        return $this->related_id;
    }

    public function setRelatedId($related_id)
    {
        $this->related_id = $related_id;

        return $this;
    }

    public function getRelatedType()
    {
        return $this->related_type;
    }

    public function setRelatedType($related_type)
    {
        $this->related_type = $related_type;

        return $this;
    }

    public function isIsRead()
    {
        return $this->is_read;
    }

    public function setIsRead($is_read)
    {
        $this->is_read = $is_read;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getReadAt()
    {
        return $this->read_at;
    }

    public function setReadAt($read_at)
    {
        $this->read_at = $read_at;

        return $this;
    }
}
