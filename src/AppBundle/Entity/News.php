<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * News
 *
 * @ORM\Table(name="news", options={"collate"="utf8_general_ci"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NewsRepository")
 * @UniqueEntity("url")
 * @Vich\Uploadable
 */
class News
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
     * @var AppBundle\Entity\NewsCategory;
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\NewsCategory", inversedBy="news")
     * @ORM\JoinTable(
     *  name="news_newscategory",
     *  joinColumns={
     *      @ORM\JoinColumn(name="news_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="newscategory_id", referencedColumnName="id")
     *  }
     * )
     */
    private $category;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 10,
     *      max = 255,
     *      minMessage = "Your title must be at least {{ limit }} characters long",
     *      maxMessage = "Your title cannot be longer than {{ limit }} characters"
     * )
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="url", type="string", length=255, unique=true)
     */
    private $url;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var text
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="contents", type="text")
     */
    private $contents;

    /**
     * @var string
     *
     * @ORM\Column(name="images", type="string", length=255, nullable=true)
     */
    private $images;

    /**
     * @Vich\UploadableField(mapping="news_images", fileNameProperty="images")
     * @var File
     */
    private $imageFile;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enable", type="boolean")
     */
    private $enable = true;

    /**
     * @var string
     *
     * @ORM\Column(name="postType", type="string", length=255)
     */
    private $postType = 'post';

    /**
     * @var string
     *
     * @ORM\Column(name="pageTitle", type="string", length=255, nullable=true)
     */
    private $pageTitle = null;

    /**
     * @var string
     *
     * @ORM\Column(name="pageDescription", type="text", nullable=true)
     */
    private $pageDescription = null;

    /**
     * @var string
     *
     * @ORM\Column(name="pageKeyword", type="string", length=255, nullable=true)
     */
    private $pageKeyword = null;

    /**
     * @var string
     *
     * @ORM\Column(name="contactHotline", type="text", nullable=true)
     */
    private $contactHotline = null;

    /**
     * @var int
     *
     * @ORM\Column(name="viewCounts", type="integer")
     */
    private $viewCounts = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="ordering", type="integer", nullable=true)
     */
    private $ordering = null;

    /**
     * @var int
     *
     * @ORM\Column(name="categoryPrimary", type="integer")
     */
    private $categoryPrimary = 0;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="createdAt", type="datetime") 
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @var Tag[]|ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Tag", inversedBy="news", cascade={"persist"})
     * @ORM\OrderBy({"name": "ASC"})
     * @Assert\Count(max="10", maxMessage="news.too_many_tags")
     */
    private $tags;

    public function __toString()
    {
        return (string)$this->getTitle();
    }

    public function __construct()
    {
        $this->category = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

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
     * Add tag for the news
     *
     * @return Tag[]
     */
    public function addTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
    }

    /**
     * Remove tag for the news
     *
     * @return Tag[]
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get the list of tag associated to the news.
     *
     * @return \AppBundle\Entity\Tag
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set name
     *
     * @param string $title
     *
     * @return News
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set category
     *
     * @param \AppBundle\Entity\NewsCategory $category
     * @return NewsCategory
     */
    public function setCategory(\AppBundle\Entity\NewsCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return NewsCategory[]
     */
    public function addCategory(NewsCategory $newsCategory)
    {
        if (!$this->category->contains($newsCategory)) {
            $this->category->add($newsCategory);
        }
    }

    /**
     * @return NewsCategory[]
     */
    public function removeCategory(NewsCategory $newsCategory)
    {
        $this->category->removeElement($newsCategory);
    }

    /**
     * @return \AppBundle\Entity\NewsCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return News
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
     * Set description
     *
     * @param string $description
     * @return News
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
     * Set contents
     *
     * @param string $contents
     * @return News
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Get contents
     *
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * Set images file
     *
     * @param File $images
     * @return News
     */
    public function setImageFile(File $images = null)
    {
        $this->imageFile = $images;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($images) {
            $this->updatedAt = new \DateTime('now');
        }
    }

    /**
     * Get images file
     *
     * @return string
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Set images
     *
     * @param string $images
     * @return News
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setEnable($enable)
    {
        $this->enable = $enable;

        return $this;
    }

    public function getEnable()
    {
        return $this->enable;
    }

    public function setPostType($postType)
    {
        $this->postType = $postType;

        return $this;
    }

    public function getPostType()
    {
        return $this->postType;
    }

    public function isPage()
    {
        return ($this->postType == 'page') ? true : false;
    }

    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;

        return $this;
    }

    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    public function setPageDescription($pageDescription)
    {
        $this->pageDescription = $pageDescription;

        return $this;
    }

    public function getPageDescription()
    {
        return $this->pageDescription;
    }

    public function setPageKeyword($pageKeyword)
    {
        $this->pageKeyword = $pageKeyword;

        return $this;
    }

    public function getPageKeyword()
    {
        return $this->pageKeyword;
    }

    public function setContactHotline($contactHotline)
    {
        $this->contactHotline = $contactHotline;

        return $this;
    }

    public function getContactHotline()
    {
        return $this->contactHotline;
    }

    public function setViewCounts($viewCounts)
    {
        $this->viewCounts = $viewCounts;

        return $this;
    }

    public function getViewCounts()
    {
        return $this->viewCounts;
    }

    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;

        return $this;
    }

    public function getOrdering()
    {
        return $this->ordering;
    }

    public function setCategoryPrimary($categoryPrimary)
    {
        $this->categoryPrimary = $categoryPrimary;

        return $this;
    }

    public function getCategoryPrimary()
    {
        return $this->categoryPrimary;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(Comment $comment)
    {
        $comment->setNews($this);
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(Comment $comment)
    {
        $comment->setNews(null);
        $this->comments->removeElement($comment);
    }
}