<?php
namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Sortie;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < 30; $i++) {
            $comment = new Comment();
            $comment->setContent($faker->sentence(10));
            $comment->setCreatedAt($faker->dateTimeBetween('-30 days', 'now'));
            $comment->setUser($this->getReference(UserFixtures::USER_REFERENCE . $faker->numberBetween(0, 17), User::class));
            $comment->setSortie($this->getReference('sortie_' . $faker->numberBetween(0, 19), Sortie::class));
            $manager->persist($comment);
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            SortieFixtures::class,
        ];
    }
}

