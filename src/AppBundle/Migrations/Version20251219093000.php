<?php

namespace AppBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create notifications table
 */
final class Version20251219093000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create notifications table for internal notifications system';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('
            CREATE TABLE notification (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message LONGTEXT NOT NULL,
                related_id INT,
                related_type VARCHAR(50),
                is_read TINYINT(1) DEFAULT 0,
                created_at DATETIME NOT NULL,
                read_at DATETIME,
                PRIMARY KEY (id),
                INDEX idx_user_id (user_id),
                INDEX idx_is_read (is_read),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE IF EXISTS notification');
    }
}
