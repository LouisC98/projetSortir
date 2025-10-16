<?php
namespace App\DataFixtures;

use App\Entity\ParticipantGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ParticipantGroupFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < 10; $i++) {
            $group = new ParticipantGroup();
            $group->setName($faker->word());
            $group->setOwner($this->getReference(UserFixtures::USER_REFERENCE . $faker->numberBetween(0, 17), \App\Entity\User::class));
            $group->setIsPrivate($faker->boolean(50));
            $manager->persist($group);
            $this->addReference('group_' . $i, $group);
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [SortieFixtures::class];
    }
}
