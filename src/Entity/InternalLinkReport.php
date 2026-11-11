<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InternalLinkReport — kết quả InternalLinkAnalyzer::analyze() đã tính sẵn,
 * ghi bởi app:compute-internal-links (cron) hoặc nút "Phân tích lại".
 *
 * @ORM\Table(name="internal_link_report", options={"charset"="utf8mb4", "collate"="utf8mb4_unicode_ci"})
 * @ORM\Entity(repositoryClass="App\Repository\InternalLinkReportRepository")
 */
class InternalLinkReport
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
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="generatedAt", type="datetime")
     */
    private $generatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="dataJson", type="text", length=16777215, columnDefinition="MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL")
     */
    private $dataJson = '{}';

    public function __construct()
    {
        $this->generatedAt = new \DateTime();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getGeneratedAt(): \DateTimeInterface
    {
        return $this->generatedAt;
    }

    public function getData(): array
    {
        return json_decode($this->dataJson, true) ?: [];
    }

    public function setData(array $data): self
    {
        $this->dataJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        return $this;
    }
}

