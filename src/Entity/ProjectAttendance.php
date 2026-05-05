<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="project_attendance")
 * @ORM\Entity(repositoryClass="App\Repository\ProjectAttendanceRepository")
 */
class ProjectAttendance
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
     * @ORM\Column(name="attendance_date", type="date")
     */
    private $date;

    /**
     * @ORM\Column(name="worker_name", type="string", length=255)
     */
    private $workerName;

    /**
     * @ORM\Column(name="hours", type="decimal", precision=5, scale=2)
     */
    private $hours;

    /**
     * @ORM\Column(name="note", type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function getId() { return $this->id; }

    public function getProject() { return $this->project; }
    public function setProject($project) { $this->project = $project; return $this; }

    public function getDate() { return $this->date; }
    public function setDate($date) { $this->date = $date; return $this; }

    public function getWorkerName() { return $this->workerName; }
    public function setWorkerName($workerName) { $this->workerName = $workerName; return $this; }

    public function getHours() { return $this->hours; }
    public function setHours($hours) { $this->hours = $hours; return $this; }

    public function getNote() { return $this->note; }
    public function setNote($note) { $this->note = $note; return $this; }

    public function getCreatedAt() { return $this->createdAt; }
}
