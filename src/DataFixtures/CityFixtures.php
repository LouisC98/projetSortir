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
        $cities = [
            ['name' => 'Paris', 'postalCode' => '75000'],
            ['name' => 'Lyon', 'postalCode' => '69000'],
            ['name' => 'Marseille', 'postalCode' => '13000'],
            ['name' => 'Bordeaux', 'postalCode' => '33000'],
            ['name' => 'Lille', 'postalCode' => '59000'],
            ['name' => 'Nantes', 'postalCode' => '44000'],
            ['name' => 'Strasbourg', 'postalCode' => '67000'],
            ['name' => 'Montpellier', 'postalCode' => '34000'],
            ['name' => 'Toulouse', 'postalCode' => '31000'],
            ['name' => 'Nice', 'postalCode' => '06000'],
            ['name' => 'Rennes', 'postalCode' => '35000'],
            ['name' => 'Grenoble', 'postalCode' => '38000'],
            ['name' => 'Rouen', 'postalCode' => '76000'],
            ['name' => 'Reims', 'postalCode' => '51100'],
            ['name' => 'Le Havre', 'postalCode' => '76600'],
            ['name' => 'Saint-Étienne', 'postalCode' => '42000'],
            ['name' => 'Toulon', 'postalCode' => '83000'],
            ['name' => 'Angers', 'postalCode' => '49000'],
            ['name' => 'Dijon', 'postalCode' => '21000'],
            ['name' => 'Aix-en-Provence', 'postalCode' => '13100'],
        ];
        foreach ($cities as $i => $data) {
            $city = new City();
            $city->setName($data['name']);
            $city->setPostalCode($data['postalCode']);
            $manager->persist($city);
            $this->addReference(self::CITY_REFERENCE . $i, $city);
        }
        $manager->flush();
    }
}
