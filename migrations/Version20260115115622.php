<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115115622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table for JWT authentication';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('CREATE TABLE users (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            created_at TIMESTAMP NOT NULL,
            last_login_at TIMESTAMP DEFAULT NULL
        )');

        // Add index on email for faster lookups
        $this->addSql('CREATE INDEX idx_users_email ON users(email)');
    }

    public function down(Schema $schema): void
    {
        // Drop users table
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}
