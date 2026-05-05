<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="project_journal")
 * @ORM\Entity(repositoryClass="App\Repository\ProjectJournalRepository")
 */
class ProjectJournal
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ConstructionProject")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $project;

    /**
     * @ORM\Column(name="log_date", type="date")
     */
    private $logDate;

    /**
     * @ORM\Column(name="weather", type="string", length=100, nullable=true)
     */
    private $weather;

    /**
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @ORM\Column(name="worker_count", type="integer", options={"default"=0})
     */
    private $workerCount = 0;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function getId() { return $this->id; }

    public function getProject() { return $this->project; }
    public function setProject($project) { $this->project = $project; return $this; }

    public function getLogDate() { return $this->logDate; }
    public function setLogDate($logDate) { $this->logDate = $logDate; return $this; }

    public function getWeather() { return $this->weather; }
    public function setWeather($weather) { $this->weather = $weather; return $this; }

    public function getContent() { return $this->content; }
    public function setContent($content) { $this->content = $content; return $this; }

    public function getWorkerCount() { return $this->workerCount; }
    public function setWorkerCount($workerCount) { $this->workerCount = $workerCount; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
}
