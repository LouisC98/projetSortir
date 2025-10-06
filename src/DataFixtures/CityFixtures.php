<?php

namespace App\DataFixtures;

use App\Entity\City;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CityFixtures extends Fixture
{
    public const CITY_REFERENCE = 'city_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 20; $i++) {
            $city = new City();
            $city->setName($faker->city());
            $city->setPostalCode($faker->postcode());

            $manager->persist($city);

            $this->addReference(self::CITY_REFERENCE . $i, $city);
        }

        $manager->flush();
    }
}
