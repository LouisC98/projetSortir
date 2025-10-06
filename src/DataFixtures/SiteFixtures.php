<?php

namespace App\DataFixtures;

use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class SiteFixtures extends Fixture
{
    public const SITE_REFERENCE = 'site_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $siteNames = [
            'Rennes',
            'Nantes',
            'Paris',
            'Lyon',
            'Bordeaux',
            'Lille',
            'Toulouse',
            'Marseille',
            'Nice',
            'Strasbourg'
        ];

        foreach ($siteNames as $index => $siteName) {
            $site = new Site();
            $site->setName($siteName);

            $manager->persist($site);

            $this->addReference(self::SITE_REFERENCE . $index, $site);
        }

        $manager->flush();
    }
}