<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251006101856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_sortie (user_id INT NOT NULL, sortie_id INT NOT NULL, INDEX IDX_596DC8CFA76ED395 (user_id), INDEX IDX_596DC8CFCC72D953 (sortie_id), PRIMARY KEY(user_id, sortie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_sortie ADD CONSTRAINT FK_596DC8CFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_sortie ADD CONSTRAINT FK_596DC8CFCC72D953 FOREIGN KEY (sortie_id) REFERENCES sortie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE place ADD city_id INT NOT NULL');
        $this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('CREATE INDEX IDX_741D53CD8BAC62AF ON place (city_id)');
        $this->addSql('ALTER TABLE sortie ADD organisateur_id INT DEFAULT NULL, ADD site_id INT NOT NULL, ADD place_id INT NOT NULL');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D936B2FA FOREIGN KEY (organisateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2DA6A219 FOREIGN KEY (place_id) REFERENCES place (id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2D936B2FA ON sortie (organisateur_id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2F6BD1646 ON sortie (site_id)');
        $this->addSql('CREATE INDEX IDX_3C3FD3F2DA6A219 ON sortie (place_id)');
        $this->addSql('ALTER TABLE user ADD pseudo VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO ON user (pseudo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_sortie DROP FOREIGN KEY FK_596DC8CFA76ED395');
        $this->addSql('ALTER TABLE user_sortie DROP FOREIGN KEY FK_596DC8CFCC72D953');
        $this->addSql('DROP TABLE user_sortie');
        $this->addSql('ALTER TABLE place DROP FOREIGN KEY FK_741D53CD8BAC62AF');
        $this->addSql('DROP INDEX IDX_741D53CD8BAC62AF ON place');
        $this->addSql('ALTER TABLE place DROP city_id');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D936B2FA');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2F6BD1646');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2DA6A219');
        $this->addSql('DROP INDEX IDX_3C3FD3F2D936B2FA ON sortie');
        $this->addSql('DROP INDEX IDX_3C3FD3F2F6BD1646 ON sortie');
        $this->addSql('DROP INDEX IDX_3C3FD3F2DA6A219 ON sortie');
        $this->addSql('ALTER TABLE sortie DROP organisateur_id, DROP site_id, DROP place_id');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_PSEUDO ON user');
        $this->addSql('ALTER TABLE user DROP pseudo');
    }
}
