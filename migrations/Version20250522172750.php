<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250522172750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, survey_id INT NOT NULL, INDEX IDX_B6F7494EB3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE question_option (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, text VARCHAR(255) NOT NULL, INDEX IDX_5DDB2FB81E27F6BF (question_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE question_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE survey (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_AD5F9BFCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question ADD CONSTRAINT FK_B6F7494EB3FE509D FOREIGN KEY (survey_id) REFERENCES survey (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_option ADD CONSTRAINT FK_5DDB2FB81E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE survey ADD CONSTRAINT FK_AD5F9BFCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EB3FE509D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE question_option DROP FOREIGN KEY FK_5DDB2FB81E27F6BF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE survey DROP FOREIGN KEY FK_AD5F9BFCA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE question
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE question_option
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE question_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE survey
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
    }
}
