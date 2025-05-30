<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Dodanie pola question_type_id do tabeli question
 */
final class Version20250529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add question_type_id to question table and insert default question types';
    }

    public function up(Schema $schema): void
    {
        // Dodanie kolumny question_type_id do tabeli question
        $this->addSql('ALTER TABLE question ADD question_type_id INT DEFAULT NULL');

        // Dodanie klucza obcego
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494ECB90598E FOREIGN KEY (question_type_id) REFERENCES question_type (id)');

        // Dodanie indeksu
        $this->addSql('CREATE INDEX IDX_B6F7494ECB90598E ON question (question_type_id)');

        // Wstawienie domyślnych typów pytań
        $this->addSql("INSERT INTO question_type (name, label) VALUES 
            ('single_choice', 'Wybór pojedynczy'),
            ('multiple_choice', 'Wybór wielokrotny'),
            ('text', 'Odpowiedź tekstowa'),
            ('rating', 'Ocena (1-5)')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494ECB90598E');
        $this->addSql('DROP INDEX IDX_B6F7494ECB90598E ON question');
        $this->addSql('ALTER TABLE question DROP question_type_id');
        $this->addSql('DELETE FROM question_type');
    }
}