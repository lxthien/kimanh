<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AIContentAudit
 *
 * @ORM\Table(name="ai_content_audit")
 * @ORM\Entity(repositoryClass="App\Repository\AIContentAuditRepository")
 */
class AIContentAudit
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
     * @var News
     *
     * @ORM\OneToOne(targetEntity="App\Entity\News", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="news_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $news;

    /**
     * @var float
     *
     * @ORM\Column(name="eeat_score", type="float", nullable=true)
     */
    private $eeatScore;

    /**
     * @var string
     *
     * @ORM\Column(name="eeat_feedback", type="text", nullable=true)
     */
    private $eeatFeedback;

    /**
     * @var float
     *
     * @ORM\Column(name="aeo_score", type="float", nullable=true)
     */
    private $aeoScore;

    /**
     * @var string
     *
     * @ORM\Column(name="aeo_feedback", type="text", nullable=true)
     */
    private $aeoFeedback;

    /**
     * @var float
     *
     * @ORM\Column(name="geo_score", type="float", nullable=true)
     */
    private $geoScore;

    /**
     * @var string
     *
     * @ORM\Column(name="nlp_metrics", type="text", nullable=true)
     */
    private $nlpMetrics;

    /**
     * @var string
     *
     * @ORM\Column(name="semantic_triples", type="text", nullable=true)
     */
    private $semanticTriples;

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
     * Set news
     *
     * @param News $news
     * @return AIContentAudit
     */
    public function setNews(News $news)
    {
        $this->news = $news;

        return $this;
    }

    /**
     * Get news
     *
     * @return News
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * Set eeatScore
     *
     * @param float $eeatScore
     * @return AIContentAudit
     */
    public function setEeatScore($eeatScore)
    {
        $this->eeatScore = $eeatScore;

        return $this;
    }

    /**
     * Get eeatScore
     *
     * @return float
     */
    public function getEeatScore()
    {
        return $this->eeatScore;
    }

    /**
     * Set eeatFeedback
     *
     * @param string $eeatFeedback
     * @return AIContentAudit
     */
    public function setEeatFeedback($eeatFeedback)
    {
        $this->eeatFeedback = $eeatFeedback;

        return $this;
    }

    /**
     * Get eeatFeedback
     *
     * @return string
     */
    public function getEeatFeedback()
    {
        return $this->eeatFeedback;
    }

    /**
     * Set aeoScore
     *
     * @param float $aeoScore
     * @return AIContentAudit
     */
    public function setAeoScore($aeoScore)
    {
        $this->aeoScore = $aeoScore;

        return $this;
    }

    /**
     * Get aeoScore
     *
     * @return float
     */
    public function getAeoScore()
    {
        return $this->aeoScore;
    }

    /**
     * Set aeoFeedback
     *
     * @param string $aeoFeedback
     * @return AIContentAudit
     */
    public function setAeoFeedback($aeoFeedback)
    {
        $this->aeoFeedback = $aeoFeedback;

        return $this;
    }

    /**
     * Get aeoFeedback
     *
     * @return string
     */
    public function getAeoFeedback()
    {
        return $this->aeoFeedback;
    }

    /**
     * Set geoScore
     *
     * @param float $geoScore
     * @return AIContentAudit
     */
    public function setGeoScore($geoScore)
    {
        $this->geoScore = $geoScore;

        return $this;
    }

    /**
     * Get geoScore
     *
     * @return float
     */
    public function getGeoScore()
    {
        return $this->geoScore;
    }

    /**
     * Set nlpMetrics
     *
     * @param string $nlpMetrics
     * @return AIContentAudit
     */
    public function setNlpMetrics($nlpMetrics)
    {
        $this->nlpMetrics = $nlpMetrics;

        return $this;
    }

    /**
     * Get nlpMetrics
     *
     * @return string
     */
    public function getNlpMetrics()
    {
        return $this->nlpMetrics;
    }

    /**
     * Set semanticTriples
     *
     * @param string $semanticTriples
     * @return AIContentAudit
     */
    public function setSemanticTriples($semanticTriples)
    {
        $this->semanticTriples = $semanticTriples;

        return $this;
    }

    /**
     * Get semanticTriples
     *
     * @return string
     */
    public function getSemanticTriples()
    {
        return $this->semanticTriples;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return AIContentAudit
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
