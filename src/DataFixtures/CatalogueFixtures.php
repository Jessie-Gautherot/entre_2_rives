<?php

namespace App\DataFixtures;

use App\Entity\Boat;
use App\Entity\BoatModel;
use App\Entity\RentalRate;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Loads the boat catalogue into the database.
 */
class CatalogueFixtures extends Fixture
{
    /**
     * Creates a physical boat for a boat model.
     */
    private function createBoat(
        ObjectManager $manager,
        BoatModel $boatModel,
        string $name
    ): void {
        $boat = new Boat();

        $boat->setName($name);
        $boat->setIsActive(true);
        $boatModel->addBoat($boat);

        $manager->persist($boat);
    }

    /**
     * Creates a rental rate for a boat model.
     */
    private function createRentalRate(
        ObjectManager $manager,
        BoatModel $boatModel,
        string $label,
        int $durationHours,
        string $startTime,
        int $price
    ): void {
        $rentalRate = new RentalRate();

        $rentalRate->setLabel($label);
        $rentalRate->setDurationHours($durationHours);
        $rentalRate->setStartTime(new \DateTimeImmutable($startTime));
        $rentalRate->setPrice($price);
        $rentalRate->setIsActive(true);
        $boatModel->addRentalRate($rentalRate);

        $manager->persist($rentalRate);
    }

    /**
     * Loads boat models, physical boats and rental rates.
     */
    public function load(ObjectManager $manager): void
    {
        // Create Escapade model.
        $escapade = new BoatModel();

        $escapade->setName('Escapade');
        $escapade->setSlug('escapade');
        $escapade->setDescription(
            'Compact et convivial, Escapade est idéal pour une balade en couple ou en famille. '
            . 'Ses banquettes confortables et sa table accueillent jusqu’à 4 personnes dans un espace chaleureux. '
            . 'Facile à prendre en main, ce bateau sans permis est parfait pour profiter d’un moment de détente au fil de l\'eau.'
        );
        $escapade->setCapacity(4);
        $escapade->setMainImage('images/boats/escapade-1.png');
        $escapade->setSecondImage('images/boats/escapade-2.png');
        $escapade->setThirdImage('images/boats/escapade-3.png');

        $manager->persist($escapade);

        // Create Escapade physical boats.
        $this->createBoat(
            $manager,
            $escapade,
            'Escapade 1'
        );

        $this->createBoat(
            $manager,
            $escapade,
            'Escapade 2'
        );

        $this->createBoat(
            $manager,
            $escapade,
            'Escapade 3'
        );

        // Create Escapade rental rates.
        $this->createRentalRate(
            $manager,
            $escapade,
            'Matin',
            2,
            '09:00',
            5000
        );

        $this->createRentalRate(
            $manager,
            $escapade,
            'Midi',
            2,
            '11:00',
            5000
        );

        $this->createRentalRate(
            $manager,
            $escapade,
            'Après-midi',
            2,
            '14:00',
            5000
        );

        $this->createRentalRate(
            $manager,
            $escapade,
            'Fin de journée',
            2,
            '16:00',
            5000
        );

        $this->createRentalRate(
            $manager,
            $escapade,
            'Matin',
            4,
            '09:00',
            9000
        );

        $this->createRentalRate(
            $manager,
            $escapade,
            'Après-midi',
            4,
            '14:00',
            9000
        );

        $this->createRentalRate(
            $manager,
            $escapade,
            'Journée',
            9,
            '09:00',
            15000
        );

        // Create Évasion model.
        $evasion = new BoatModel();

        $evasion->setName('Évasion');
        $evasion->setSlug('evasion');
        $evasion->setDescription(
            'Spacieux et convivial, Évasion est idéal pour une sortie en famille ou entre amis. '
            . 'Ses banquettes confortables et sa table accueillent jusqu’à 7 personnes pour partager un moment agréable. '
            . 'Facile à prendre en main, ce bateau sans permis offre davantage d’espace pour profiter pleinement de votre balade.'
        );
        $evasion->setCapacity(7);
        $evasion->setMainImage('images/boats/evasion-1.png');
        $evasion->setSecondImage('images/boats/evasion-2.png');
        $evasion->setThirdImage('images/boats/evasion-3.png');

        $manager->persist($evasion);

        // Create Évasion physical boats.
        $this->createBoat(
            $manager,
            $evasion,
            'Évasion 1'
        );

        $this->createBoat(
            $manager,
            $evasion,
            'Évasion 2'
        );

        $this->createBoat(
            $manager,
            $evasion,
            'Évasion 3'
        );

        // Create Évasion rental rates.
        $this->createRentalRate(
            $manager,
            $evasion,
            'Matin',
            2,
            '09:00',
            8000
        );

        $this->createRentalRate(
            $manager,
            $evasion,
            'Midi',
            2,
            '11:00',
            8000
        );

        $this->createRentalRate(
            $manager,
            $evasion,
            'Après-midi',
            2,
            '14:00',
            8000
        );

        $this->createRentalRate(
            $manager,
            $evasion,
            'Fin de journée',
            2,
            '16:00',
            8000
        );

        $this->createRentalRate(
            $manager,
            $evasion,
            'Matin',
            4,
            '09:00',
            14000
        );

        $this->createRentalRate(
            $manager,
            $evasion,
            'Après-midi',
            4,
            '14:00',
            14000
        );

        $this->createRentalRate(
            $manager,
            $evasion,
            'Journée',
            9,
            '09:00',
            22000
        );

        // Create Grand Large model.
        $grandLarge = new BoatModel();

        $grandLarge->setName('Grand Large');
        $grandLarge->setSlug('grand-large');
        $grandLarge->setDescription(
            'Spacieux et généreux, Grand Large est conçu pour les sorties en groupe jusqu’à 12 personnes. '
            . 'Ses larges banquettes, ses deux tables et son bain de soleil offrent tout l’espace nécessaire '
            . 'pour partager un moment convivial et confortable. '
            . 'Ce bateau sans permis est idéal pour profiter d’une belle balade en famille ou entre amis.'
        );
        $grandLarge->setCapacity(12);
        $grandLarge->setMainImage('images/boats/grand-large-1.png');
        $grandLarge->setSecondImage('images/boats/grand-large-2.png');
        $grandLarge->setThirdImage('images/boats/grand-large-3.png');

        $manager->persist($grandLarge);

        // Create Grand Large physical boats.
        $this->createBoat(
            $manager,
            $grandLarge,
            'Grand Large 1'
        );

        $this->createBoat(
            $manager,
            $grandLarge,
            'Grand Large 2'
        );

        $this->createBoat(
            $manager,
            $grandLarge,
            'Grand Large 3'
        );

        // Create Grand Large rental rates.
        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Matin',
            2,
            '09:00',
            11000
        );

        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Midi',
            2,
            '11:00',
            11000
        );

        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Après-midi',
            2,
            '14:00',
            11000
        );

        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Fin de journée',
            2,
            '16:00',
            11000
        );

        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Matin',
            4,
            '09:00',
            20000
        );

        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Après-midi',
            4,
            '14:00',
            20000
        );

        $this->createRentalRate(
            $manager,
            $grandLarge,
            'Journée',
            9,
            '09:00',
            30000
        );

        // Save all catalogue data.
        $manager->flush();
    }
}