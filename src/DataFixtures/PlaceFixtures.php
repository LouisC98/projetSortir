<?php

namespace App\DataFixtures;

use App\Entity\City;
use App\Entity\Place;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
class PlaceFixtures extends Fixture implements DependentFixtureInterface
{
    public const PLACE_REFERENCE = 'place_';

    public function load(ObjectManager $manager): void
    {
        $places = [
            ['name' => 'Musée du Louvre', 'street' => 'Rue de Rivoli', 'latitude' => 48.8606, 'longitude' => 2.3376, 'city' => 0],
            ['name' => 'Tour Eiffel', 'street' => 'Champ de Mars', 'latitude' => 48.8584, 'longitude' => 2.2945, 'city' => 0],
            ['name' => 'Parc de la Tête d’Or', 'street' => 'Boulevard des Belges', 'latitude' => 45.7772, 'longitude' => 4.8556, 'city' => 1],
            ['name' => 'Vieux-Port', 'street' => 'Quai du Port', 'latitude' => 43.2965, 'longitude' => 5.3698, 'city' => 2],
            ['name' => 'Place de la Bourse', 'street' => 'Place de la Bourse', 'latitude' => 44.8412, 'longitude' => -0.5706, 'city' => 3],
            ['name' => 'Grand Place', 'street' => 'Place du Général de Gaulle', 'latitude' => 50.6364, 'longitude' => 3.0633, 'city' => 4],
            ['name' => 'Château des Ducs de Bretagne', 'street' => '4 Place Marc Elder', 'latitude' => 47.2162, 'longitude' => -1.5536, 'city' => 5],
            ['name' => 'Cathédrale Notre-Dame', 'street' => 'Place de la Cathédrale', 'latitude' => 48.5831, 'longitude' => 7.7458, 'city' => 6],
            ['name' => 'Place de la Comédie', 'street' => 'Place de la Comédie', 'latitude' => 43.6108, 'longitude' => 3.8767, 'city' => 7],
            ['name' => 'Place du Capitole', 'street' => 'Place du Capitole', 'latitude' => 43.6045, 'longitude' => 1.4442, 'city' => 8],
            ['name' => 'Promenade des Anglais', 'street' => 'Promenade des Anglais', 'latitude' => 43.6950, 'longitude' => 7.2650, 'city' => 9],
            ['name' => 'Parc du Thabor', 'street' => 'Place Saint-Melaine', 'latitude' => 48.1147, 'longitude' => -1.6750, 'city' => 10],
            ['name' => 'Bastille', 'street' => 'Quai Stéphane Jay', 'latitude' => 45.1885, 'longitude' => 5.7245, 'city' => 11],
            ['name' => 'Cathédrale Notre-Dame', 'street' => 'Place de la Cathédrale', 'latitude' => 49.4431, 'longitude' => 1.0993, 'city' => 12],
            ['name' => 'Cathédrale de Reims', 'street' => 'Place du Cardinal Luçon', 'latitude' => 49.2539, 'longitude' => 4.0347, 'city' => 13],
            ['name' => 'Jardin japonais', 'street' => 'Rue Albert Dubosc', 'latitude' => 49.4944, 'longitude' => 0.1079, 'city' => 14],
            ['name' => 'Parc du Pilat', 'street' => 'Route du Pilat', 'latitude' => 45.4200, 'longitude' => 4.5200, 'city' => 15],
            ['name' => 'Plages du Mourillon', 'street' => 'Plage du Mourillon', 'latitude' => 43.1155, 'longitude' => 5.9406, 'city' => 16],
            ['name' => 'Château d’Angers', 'street' => '2 Promenade du Bout du Monde', 'latitude' => 47.4716, 'longitude' => -0.5562, 'city' => 17],
            ['name' => 'Palais des Ducs de Bourgogne', 'street' => 'Place de la Libération', 'latitude' => 47.3220, 'longitude' => 5.0415, 'city' => 18],
            ['name' => 'Cours Mirabeau', 'street' => 'Cours Mirabeau', 'latitude' => 43.5263, 'longitude' => 5.4474, 'city' => 19],
        ];
        foreach ($places as $i => $data) {
            $place = new Place();
            $place->setName($data['name']);
            $place->setStreet($data['street']);
            $place->setLatitude($data['latitude']);
            $place->setLongitude($data['longitude']);
            $cityReference = CityFixtures::CITY_REFERENCE . $data['city'];
            $place->setCity($this->getReference($cityReference, City::class));
            $manager->persist($place);
            $this->addReference(self::PLACE_REFERENCE . $i, $place);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CityFixtures::class,
        ];
    }
}
