<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="construction_project")
 * @ORM\Entity(repositoryClass="App\Repository\ConstructionProjectRepository")
 */
class ConstructionProject
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="project_name", type="string", length=255)
     */
    private $projectName;

    /**
     * @ORM\Column(name="status", type="string", length=50, options={"default"="draft"})
     */
    private $status = 'draft';

    /**
     * @ORM\Column(name="project_type", type="integer")
     */
    private $type = 1;

    /**
     * @ORM\Column(name="finish_level", type="integer")
     */
    private $finishLevel = 2;

    /**
     * @ORM\Column(name="contract_value", type="decimal", precision=15, scale=2)
     */
    private $contractValue = 0;

    /**
     * @ORM\Column(name="target_margin", type="integer")
     */
    private $targetMargin = 18;

    /**
     * @ORM\Column(name="building_width", type="decimal", precision=10, scale=2)
     */
    private $wide = 0;

    /**
     * @ORM\Column(name="building_length", type="decimal", precision=10, scale=2)
     */
    private $long = 0;

    /**
     * @ORM\Column(name="floor_count", type="integer")
     */
    private $floor = 1;

    /**
     * @ORM\Column(name="basement", type="integer")
     */
    private $basement = 0;

    /**
     * @ORM\Column(name="foundation_type", type="integer")
     */
    private $mong = 2;

    /**
     * @ORM\Column(name="roof_type", type="integer")
     */
    private $mai = 1;

    /**
     * @ORM\Column(name="alley", type="integer")
     */
    private $alley = 2;

    /**
     * @ORM\Column(name="subcontract", type="integer")
     */
    private $subcontract = 2;

    /**
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @ORM\Column(name="customer_name", type="string", length=255, nullable=true)
     */
    private $customerName;

    /**
     * @ORM\Column(name="customer_phone", type="string", length=20, nullable=true)
     */
    private $customerPhone;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(name="real_progress", type="decimal", precision=5, scale=2, options={"default"=0})
     */
    private $realProgress = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="manager_id", referencedColumnName="id", nullable=true)
     */
    private $manager;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getProjectName()
    {
        return $this->projectName;
    }

    public function setProjectName($projectName)
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
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

    public function getFinishLevel()
    {
        return $this->finishLevel;
    }

    public function setFinishLevel($finishLevel)
    {
        $this->finishLevel = $finishLevel;
        return $this;
    }

    public function getContractValue()
    {
        return $this->contractValue;
    }

    public function setContractValue($contractValue)
    {
        $this->contractValue = $contractValue;
        return $this;
    }

    public function getTargetMargin()
    {
        return $this->targetMargin;
    }

    public function setTargetMargin($targetMargin)
    {
        $this->targetMargin = $targetMargin;
        return $this;
    }

    public function getWide()
    {
        return $this->wide;
    }

    public function setWide($wide)
    {
        $this->wide = $wide;
        return $this;
    }

    public function getLong()
    {
        return $this->long;
    }

    public function setLong($long)
    {
        $this->long = $long;
        return $this;
    }

    public function getFloor()
    {
        return $this->floor;
    }

    public function setFloor($floor)
    {
        $this->floor = $floor;
        return $this;
    }

    public function getBasement()
    {
        return $this->basement;
    }

    public function setBasement($basement)
    {
        $this->basement = $basement;
        return $this;
    }

    public function getMong()
    {
        return $this->mong;
    }

    public function setMong($mong)
    {
        $this->mong = $mong;
        return $this;
    }

    public function getMai()
    {
        return $this->mai;
    }

    public function setMai($mai)
    {
        $this->mai = $mai;
        return $this;
    }

    public function getAlley()
    {
        return $this->alley;
    }

    public function setAlley($alley)
    {
        $this->alley = $alley;
        return $this;
    }

    public function getSubcontract()
    {
        return $this->subcontract;
    }

    public function setSubcontract($subcontract)
    {
        $this->subcontract = $subcontract;
        return $this;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    public function getCustomerName()
    {
        return $this->customerName;
    }

    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerPhone()
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone($customerPhone)
    {
        $this->customerPhone = $customerPhone;
        return $this;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getRealProgress()
    {
        return $this->realProgress;
    }

    public function setRealProgress($realProgress)
    {
        $this->realProgress = $realProgress;
        return $this;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function setManager($manager)
    {
        $this->manager = $manager;
        return $this;
    }

    public function __toString()
    {
        return $this->projectName ?: '';
    }
}
