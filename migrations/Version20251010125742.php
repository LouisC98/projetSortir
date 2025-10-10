<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251010125742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participant_group (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(120) NOT NULL, is_private TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_AC75ED7C7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE participant_group_member (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, user_id INT NOT NULL, added_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3E4A66FAFE54D947 (group_id), INDEX IDX_3E4A66FAA76ED395 (user_id), UNIQUE INDEX uniq_group_user (group_id, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participant_group ADD CONSTRAINT FK_AC75ED7C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant_group_member ADD CONSTRAINT FK_3E4A66FAFE54D947 FOREIGN KEY (group_id) REFERENCES participant_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participant_group_member ADD CONSTRAINT FK_3E4A66FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participant_group DROP FOREIGN KEY FK_AC75ED7C7E3C61F9');
        $this->addSql('ALTER TABLE participant_group_member DROP FOREIGN KEY FK_3E4A66FAFE54D947');
        $this->addSql('ALTER TABLE participant_group_member DROP FOREIGN KEY FK_3E4A66FAA76ED395');
        $this->addSql('DROP TABLE participant_group');
        $this->addSql('DROP TABLE participant_group_member');
    }
}
