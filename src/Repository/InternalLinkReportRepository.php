<?php

namespace App\Repository;

use App\Entity\InternalLinkReport;
use App\Seo\InternalLink\InternalLinkAnalyzer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InternalLinkReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternalLinkReport::class);
    }

    public function findLatest(): ?InternalLinkReport
    {
        try {
            return $this->createQueryBuilder('r')
                ->orderBy('r.generatedAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (\Throwable $e) {
            $this->ensureTableExists();

            return null;
        }
    }

    /**
     * Chạy lại phân tích và thay bản báo cáo cũ bằng bản mới.
     */
    public function refresh(InternalLinkAnalyzer $analyzer): InternalLinkReport
    {
        $this->ensureTableExists();

        $report = (new InternalLinkReport())->setData($analyzer->analyze());
        $em = $this->getEntityManager();

        $em->createQueryBuilder()->delete(InternalLinkReport::class, 'r')->getQuery()->execute();
        $em->persist($report);
        $em->flush();

        return $report;
    }

    private function ensureTableExists(): void
    {
        try {
            $conn = $this->getEntityManager()->getConnection();
            $sql = 'CREATE TABLE IF NOT EXISTS internal_link_report (
                id INT AUTO_INCREMENT NOT NULL,
                generatedAt DATETIME NOT NULL,
                dataJson MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB';
            if (method_exists($conn, 'executeStatement')) {
                $conn->executeStatement($sql);
            } else {
                $conn->exec($sql);
            }
        } catch (\Throwable $e) {
            // Ignore if DB driver does not support or permission denied
        }
    }
}

