<?php

namespace App\DataFixtures;

use App\Entity\Sortie;
use App\Entity\User;
use App\Entity\Site;
use App\Entity\Place;
use App\Enum\State;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Types d'activités variées
        $activites = [
            'Sortie bowling',
            'Cinéma',
            'Randonnée',
            'Soirée karaoké',
            'Match de football',
            'Concert',
            'Visite musée',
            'Cours de cuisine',
            'Sortie vélo',
            'Paintball',
            'Escape game',
            'Théâtre',
            'Exposition',
            'Pique-nique',
            'Soirée jeux de société',
            'Course à pied',
            'Atelier poterie',
            'Dégustation vin',
            'Festival',
            'Barbecue',
            'Sortie plage',
            'Ski',
            'Piscine',
            'Tennis',
            'Golf'
        ];

        // États possibles
        $states = [
            State::CREATED,
            State::OPEN,
            State::CLOSED,
            State::IN_PROGRESS,
            State::PASSED,
            State::CANCELLED
        ];

        // Créer 30 sorties variées
        for ($i = 0; $i < 30; $i++) {
            $sortie = new Sortie();

            // Nom de la sortie
            $activite = $faker->randomElement($activites);
            $lieu = $faker->optional(0.7)->city();
            $sortie->setName($activite . ($lieu ? ' à ' . $lieu : ''));

            // Date de début (entre -1 mois et +3 mois)
            $startDate = $faker->dateTimeBetween('-1 month', '+3 months');
            $sortie->setStartDateTime($startDate);

            // Durée entre 1h et 8h
            $duration = $faker->numberBetween(5, 1440);
            $sortie->setDuration($duration);

            // Date limite d'inscription (1 à 7 jours avant la sortie)
            $daysBeforeDeadline = $faker->numberBetween(1, 7);
            $deadline = clone $startDate;
            $deadline->modify("-{$daysBeforeDeadline} days");
            $sortie->setRegistrationDeadline($deadline);

            // Nombre max de participants
            $sortie->setMaxRegistration($faker->numberBetween(5, 50));

            // État de la sortie
            $sortie->setState($faker->randomElement($states));

            // Description optionnelle
            if ($faker->boolean(70)) {
                $sortie->setDescription($faker->paragraphs(2, true));
            }

            // Assigner un site (référence depuis SiteFixtures)
            $siteIndex = $faker->numberBetween(0, 9); // 10 sites dans SiteFixtures
            $site = $this->getReference(SiteFixtures::SITE_REFERENCE . $siteIndex, Site::class);
            $sortie->setSite($site);

            // Assigner un lieu (référence depuis PlaceFixtures avec la bonne constante)
            $placeIndex = $faker->numberBetween(0, 49); // 50 places dans PlaceFixtures
            $place = $this->getReference(PlaceFixtures::PLACE_REFERENCE . $placeIndex, Place::class);
            $sortie->setPlace($place);

            // Assigner un organisateur (référence depuis UserFixtures)
            $organizerIndex = $faker->numberBetween(0, 17); // 18 utilisateurs aléatoires dans UserFixtures
            $organizer = $this->getReference(UserFixtures::USER_REFERENCE . $organizerIndex, User::class);
            $sortie->setOrganisateur($organizer);

            // Ajouter quelques participants aléatoirement
            $numParticipants = $faker->numberBetween(0, min(10, $sortie->getMaxRegistration()));
            $participantsAdded = [];

            for ($j = 0; $j < $numParticipants; $j++) {
                $participantIndex = $faker->numberBetween(0, 17);
                if (!in_array($participantIndex, $participantsAdded)) {
                    $participant = $this->getReference(UserFixtures::USER_REFERENCE . $participantIndex, User::class);
                    $sortie->addParticipant($participant);
                    $participantsAdded[] = $participantIndex;
                }
            }

            $manager->persist($sortie);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SiteFixtures::class,
            PlaceFixtures::class,
            UserFixtures::class,
        ];
    }
}
