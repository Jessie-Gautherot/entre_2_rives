<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819112812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE boat_models (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL, description LONGTEXT NOT NULL, capacity INT NOT NULL, main_image VARCHAR(255) NOT NULL, second_image VARCHAR(255) DEFAULT NULL, third_image VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_A058C3915E237E06 (name), UNIQUE INDEX UNIQ_A058C391989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE boats (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, boat_model_id INT NOT NULL, UNIQUE INDEX UNIQ_8DDF09065E237E06 (name), INDEX IDX_8DDF0906E45950DD (boat_model_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bookings (id INT AUTO_INCREMENT NOT NULL, passenger_count INT NOT NULL, rental_rate_label VARCHAR(100) NOT NULL, total_price INT NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, boat_model_id INT NOT NULL, boat_id INT NOT NULL, rental_rate_id INT NOT NULL, INDEX IDX_7A853C35A76ED395 (user_id), INDEX IDX_7A853C35E45950DD (boat_model_id), INDEX IDX_7A853C35A1E84A29 (boat_id), INDEX IDX_7A853C356F7F4570 (rental_rate_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payments (id INT AUTO_INCREMENT NOT NULL, amount INT NOT NULL, payment_status VARCHAR(30) NOT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, booking_id INT NOT NULL, UNIQUE INDEX UNIQ_65D29B321A314A57 (stripe_session_id), UNIQUE INDEX UNIQ_65D29B32FC72F97E (stripe_payment_intent_id), UNIQUE INDEX UNIQ_65D29B323301C60 (booking_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rental_rates (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, price INT NOT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, boat_model_id INT NOT NULL, INDEX IDX_DF3F25D3E45950DD (boat_model_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE boats ADD CONSTRAINT FK_8DDF0906E45950DD FOREIGN KEY (boat_model_id) REFERENCES boat_models (id)');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C35A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C35E45950DD FOREIGN KEY (boat_model_id) REFERENCES boat_models (id)');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C35A1E84A29 FOREIGN KEY (boat_id) REFERENCES boats (id)');
        $this->addSql('ALTER TABLE bookings ADD CONSTRAINT FK_7A853C356F7F4570 FOREIGN KEY (rental_rate_id) REFERENCES rental_rates (id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_65D29B323301C60 FOREIGN KEY (booking_id) REFERENCES bookings (id)');
        $this->addSql('ALTER TABLE rental_rates ADD CONSTRAINT FK_DF3F25D3E45950DD FOREIGN KEY (boat_model_id) REFERENCES boat_models (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE boats DROP FOREIGN KEY FK_8DDF0906E45950DD');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY FK_7A853C35A76ED395');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY FK_7A853C35E45950DD');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY FK_7A853C35A1E84A29');
        $this->addSql('ALTER TABLE bookings DROP FOREIGN KEY FK_7A853C356F7F4570');
        $this->addSql('ALTER TABLE payments DROP FOREIGN KEY FK_65D29B323301C60');
        $this->addSql('ALTER TABLE rental_rates DROP FOREIGN KEY FK_DF3F25D3E45950DD');
        $this->addSql('DROP TABLE boat_models');
        $this->addSql('DROP TABLE boats');
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE rental_rates');
    }
}
