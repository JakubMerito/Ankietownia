<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Dodanie tabel dla odpowiedzi użytkowników
 */
final class Version20250529130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add survey responses and question types support';
    }

    public function up(Schema $schema): void
    {


        // Dodaj kolumnę is_required do pytań
        $this->addSql('ALTER TABLE question ADD is_required TINYINT(1) DEFAULT 0 NOT NULL');

        // Dodaj kolumnę is_active do ankiet (żeby można było publikować/unpublikować)
        $this->addSql('ALTER TABLE survey ADD is_active TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE survey ADD description TEXT DEFAULT NULL');

        // Tabela dla sesji odpowiedzi (jedna sesja = jedno wypełnienie ankiety)
        $this->addSql(<<<'SQL'
            CREATE TABLE survey_response (
                id INT AUTO_INCREMENT NOT NULL, 
                survey_id INT NOT NULL, 
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL, 
                completed_at DATETIME DEFAULT NULL,
                is_completed TINYINT(1) DEFAULT 0 NOT NULL,
                INDEX IDX_EC50373AB3FE509D (survey_id), 
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // Tabela dla pojedynczych odpowiedzi na pytania
        $this->addSql(<<<'SQL'
            CREATE TABLE question_response (
                id INT AUTO_INCREMENT NOT NULL, 
                survey_response_id INT NOT NULL, 
                question_id INT NOT NULL, 
                question_option_id INT DEFAULT NULL,
                text_response TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_DD80652D5A71C90F (survey_response_id), 
                INDEX IDX_DD80652D1E27F6BF (question_id),
                INDEX IDX_DD80652DCAB5B63F (question_option_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        // Klucze obce
        $this->addSql('ALTER TABLE survey_response ADD CONSTRAINT FK_EC50373AB3FE509D FOREIGN KEY (survey_id) REFERENCES survey (id)');
        $this->addSql('ALTER TABLE question_response ADD CONSTRAINT FK_DD80652D5A71C90F FOREIGN KEY (survey_response_id) REFERENCES survey_response (id)');
        $this->addSql('ALTER TABLE question_response ADD CONSTRAINT FK_DD80652D1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE question_response ADD CONSTRAINT FK_DD80652DCAB5B63F FOREIGN KEY (question_option_id) REFERENCES question_option (id)');

        // Dodaj podstawowe typy pytań
        $this->addSql("INSERT INTO question_type (name, label) VALUES 
            ('single_choice', 'Wybór jednokrotny'),
            ('multiple_choice', 'Wybór wielokrotny'),
            ('text', 'Tekst krótki'),
            ('textarea', 'Tekst długi'),
            ('number', 'Liczba'),
            ('email', 'Email'),
            ('date', 'Data')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question_response DROP FOREIGN KEY FK_DD80652D5A71C90F');
        $this->addSql('ALTER TABLE question_response DROP FOREIGN KEY FK_DD80652D1E27F6BF');
        $this->addSql('ALTER TABLE question_response DROP FOREIGN KEY FK_DD80652DCAB5B63F');
        $this->addSql('ALTER TABLE survey_response DROP FOREIGN KEY FK_EC50373AB3FE509D');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494ECB90598E');

        $this->addSql('DROP TABLE question_response');
        $this->addSql('DROP TABLE survey_response');

        $this->addSql('DROP INDEX IDX_B6F7494ECB90598E ON question');
        $this->addSql('ALTER TABLE question DROP question_type_id, DROP is_required');
        $this->addSql('ALTER TABLE survey DROP is_active, DROP description');
    }
}