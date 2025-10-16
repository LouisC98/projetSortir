<?php

namespace App\DataFixtures;

use App\Entity\Place;
use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State; // Assurez-vous que le chemin vers votre Enum est correct
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $sortiesData = [
            ['name' => 'Visite du Musée du Louvre', 'place' => 0, 'description' => "Découverte des chefs-d'œuvre du Louvre.", 'date' => '+7 days', 'duration' => 120],
            ['name' => 'Pique-nique au Champ de Mars', 'place' => 1, 'description' => "Pique-nique convivial au pied de la Tour Eiffel.", 'date' => '+10 days', 'duration' => 90],
            ['name' => 'Balade au Parc de la Tête d’Or', 'place' => 2, 'description' => "Promenade et découverte du parc lyonnais.", 'date' => '+5 days', 'duration' => 60],
            ['name' => 'Sortie au Vieux-Port', 'place' => 3, 'description' => "Ambiance méditerranéenne et découverte des quais.", 'date' => '+12 days', 'duration' => 90],
            ['name' => 'Visite Place de la Bourse', 'place' => 4, 'description' => "Découverte de l’architecture bordelaise.", 'date' => '+8 days', 'duration' => 60],
            ['name' => 'Rencontre à la Grand Place', 'place' => 5, 'description' => "Moment convivial au cœur de Lille.", 'date' => '+15 days', 'duration' => 60],
            ['name' => 'Visite du Château des Ducs de Bretagne', 'place' => 6, 'description' => "Plongée dans l’histoire nantaise.", 'date' => '+20 days', 'duration' => 120],
            ['name' => 'Découverte de la Cathédrale Notre-Dame', 'place' => 7, 'description' => "Visite guidée de la cathédrale de Strasbourg.", 'date' => '+18 days', 'duration' => 90],
            ['name' => 'Pause à la Place de la Comédie', 'place' => 8, 'description' => "Moment détente à Montpellier.", 'date' => '+22 days', 'duration' => 60],
            ['name' => 'Visite du Capitole', 'place' => 9, 'description' => "Découverte du centre historique de Toulouse.", 'date' => '+25 days', 'duration' => 90],
            ['name' => 'Balade sur la Promenade des Anglais', 'place' => 10, 'description' => "Marche en bord de mer à Nice.", 'date' => '+30 days', 'duration' => 60],
            ['name' => 'Sortie au Parc du Thabor', 'place' => 11, 'description' => "Découverte du parc rennais.", 'date' => '+35 days', 'duration' => 60],
            ['name' => 'Randonnée à la Bastille', 'place' => 12, 'description' => "Randonnée urbaine à Grenoble.", 'date' => '+40 days', 'duration' => 120],
            ['name' => 'Visite de la Cathédrale Notre-Dame', 'place' => 13, 'description' => "Visite guidée à Rouen.", 'date' => '+45 days', 'duration' => 90],
            ['name' => 'Découverte de la Cathédrale de Reims', 'place' => 14, 'description' => "Visite du patrimoine rémois.", 'date' => '+50 days', 'duration' => 90],
            ['name' => 'Balade au Jardin japonais', 'place' => 15, 'description' => "Moment zen au Havre.", 'date' => '+55 days', 'duration' => 60],
            ['name' => 'Randonnée au Parc du Pilat', 'place' => 16, 'description' => "Randonnée nature à Saint-Étienne.", 'date' => '+60 days', 'duration' => 120],
            ['name' => 'Sortie aux Plages du Mourillon', 'place' => 17, 'description' => "Journée plage à Toulon.", 'date' => '+65 days', 'duration' => 180],
            ['name' => 'Visite du Château d’Angers', 'place' => 18, 'description' => "Découverte du château et de ses jardins.", 'date' => '+70 days', 'duration' => 120],
            ['name' => 'Balade au Palais des Ducs de Bourgogne', 'place' => 19, 'description' => "Visite historique à Dijon.", 'date' => '+75 days', 'duration' => 90],
        ];

        foreach ($sortiesData as $i => $data) {
            $sortie = new Sortie();

            // Récupérer l'organisateur (par exemple, le premier utilisateur)
            /** @var User $organizer */
            $organizer = $this->getReference(UserFixtures::USER_REFERENCE . '0', User::class);

            // Récupérer le lieu
            /** @var Place $place */
            $place = $this->getReference(PlaceFixtures::PLACE_REFERENCE . $data['place'], Place::class);

            // Définir les dates
            $startDate = (new \DateTime())->modify($data['date']);
            $deadline = (clone $startDate)->modify('-2 days');

            // Hydrater l'entité Sortie
            $sortie->setName($data['name']);
            $sortie->setDescription($data['description']);
            $sortie->setStartDateTime($startDate);
            $sortie->setRegistrationDeadline($deadline);
            $sortie->setDuration($data['duration']);
            $sortie->setMaxRegistration($faker->numberBetween(5, 25));
            $sortie->setPlace($place);
            $sortie->setOrganisateur($organizer);
            $sortie->setState(State::OPEN);

            $sortie->setSite($organizer->getSite());

            $manager->persist($sortie);

            $this->addReference('sortie_' . $i, $sortie);
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