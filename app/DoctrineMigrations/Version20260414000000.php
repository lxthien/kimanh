<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20260414000000 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE `contact` ADD `isRead` TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('UPDATE `contact` SET `isRead` = 1');

        $this->addSql('ALTER TABLE `user` ADD `createdAt` DATETIME DEFAULT NULL, ADD `adminNotificationRead` TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql("UPDATE `user` SET `createdAt` = NULLIF(`last_login`, '0000-00-00 00:00:00'), `adminNotificationRead` = 1 WHERE `createdAt` IS NULL");
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE `contact` DROP COLUMN `isRead`');
        $this->addSql('ALTER TABLE `user` DROP COLUMN `createdAt`, DROP COLUMN `adminNotificationRead`');
    }
}
