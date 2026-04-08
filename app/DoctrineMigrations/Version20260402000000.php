<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create Menu and MenuItem tables
 */
class Version20260402000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // Create menu table
        $this->addSql('
            CREATE TABLE `menu` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `name` VARCHAR(255) NOT NULL UNIQUE,
                `description` LONGTEXT,
                `enable` TINYINT(1) DEFAULT 1,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB
        ');

        // Create menu_item table
        $this->addSql('
            CREATE TABLE `menu_item` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `menu_id` INT NOT NULL,
                `parent_id` INT,
                `title` VARCHAR(255) NOT NULL,
                `type` VARCHAR(50) NOT NULL,
                `url` VARCHAR(500),
                `target_id` INT,
                `target_type` VARCHAR(50),
                `css_class` VARCHAR(255),
                `target_attr` VARCHAR(20) DEFAULT "_self",
                `title_attr` VARCHAR(255),
                `position` INT DEFAULT 0,
                `enable` TINYINT(1) DEFAULT 1,
                `createdAt` DATETIME NOT NULL,
                `updatedAt` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `IDX_MENUID` (`menu_id`),
                INDEX `IDX_PARENTID` (`parent_id`),
                CONSTRAINT `FK_MENU_ITEM_MENU` FOREIGN KEY (`menu_id`)
                    REFERENCES `menu` (`id`) ON DELETE CASCADE,
                CONSTRAINT `FK_MENU_ITEM_PARENT` FOREIGN KEY (`parent_id`)
                    REFERENCES `menu_item` (`id`) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE = InnoDB
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE IF EXISTS `menu_item`');
        $this->addSql('DROP TABLE IF EXISTS `menu`');
    }
}
