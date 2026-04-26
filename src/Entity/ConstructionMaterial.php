<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="construction_material")
 * @ORM\Entity(repositoryClass="App\Repository\ConstructionMaterialRepository")
 */
class ConstructionMaterial
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="code", type="string", length=50, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(name="category", type="string", length=100)
     */
    private $category;

    /**
     * @ORM\Column(name="unit", type="string", length=30)
     */
    private $unit;

    /**
     * @ORM\Column(name="unitPrice", type="decimal", precision=15, scale=2)
     */
    private $unitPrice;

    /**
     * @ORM\Column(name="wastagePercent", type="decimal", precision=5, scale=2, options={"default"=0})
     */
    private $wastagePercent = 0;

    /**
     * @ORM\Column(name="isActive", type="boolean", options={"default"=1})
     */
    private $isActive = true;

    /**
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    private $note;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    public function getUnit()
    {
        return $this->unit;
    }

    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    public function setUnitPrice($unitPrice)
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getWastagePercent()
    {
        return $this->wastagePercent;
    }

    public function setWastagePercent($wastagePercent)
    {
        $this->wastagePercent = $wastagePercent;
        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote($note)
    {
        $this->note = $note;
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

    public function __toString()
    {
        return $this->name ?: '';
    }
}
