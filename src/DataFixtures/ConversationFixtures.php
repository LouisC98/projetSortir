<?php

namespace App\DataFixtures;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ConversationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < 10; $i++) {
            $conversation = new Conversation();
            $conversation->setName($faker->sentence(3));
            $conversation->setType($faker->randomElement(['private', 'group']));
            $manager->persist($conversation);
            $this->addReference('conversation_' . $i, $conversation);
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
