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
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 50; $i++) {
            $place = new Place();

            $place->setName($faker->company());

            $place->setStreet($faker->streetAddress());

            $place->setLatitude($faker->latitude(41, 51));

            $place->setLongitude($faker->longitude(-5, 10));

            $cityReference = CityFixtures::CITY_REFERENCE . $faker->numberBetween(0, 19);
            $place->setCity($this->getReference($cityReference, City::class));


            $manager->persist($place);

            // Ajouter la référence pour pouvoir l'utiliser dans d'autres fixtures
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
