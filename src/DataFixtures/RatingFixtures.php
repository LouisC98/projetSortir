<?php
namespace App\DataFixtures;

use App\Entity\Rating;
use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class RatingFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < 30; $i++) {
            $rating = new Rating();
            $rating->setSortie($this->getReference('sortie_' . $faker->numberBetween(0, 19), Sortie::class));
            $rating->setUser($this->getReference(UserFixtures::USER_REFERENCE . $faker->numberBetween(0, 17), User::class));
            $rating->setScore($faker->numberBetween(1, 5));
            $manager->persist($rating);
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [SortieFixtures::class, UserFixtures::class];
    }
}
