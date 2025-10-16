<?php
namespace App\DataFixtures;

use App\Entity\ParticipantGroupMember;
use App\Entity\ParticipantGroup;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ParticipantGroupMemberFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $usedPairs = [];
        $maxGroups = 10;
        $maxUsers = 18;
        $count = 0;
        while ($count < 30) {
            $groupIdx = $faker->numberBetween(0, $maxGroups - 1);
            $userIdx = $faker->numberBetween(0, $maxUsers - 1);
            $pairKey = $groupIdx . '-' . $userIdx;
            if (isset($usedPairs[$pairKey])) {
                continue; // déjà utilisé, on saute
            }
            $usedPairs[$pairKey] = true;
            $member = new ParticipantGroupMember();
            $member->setGroup($this->getReference('group_' . $groupIdx, ParticipantGroup::class));
            $member->setUser($this->getReference(UserFixtures::USER_REFERENCE . $userIdx, User::class));
            $manager->persist($member);
            $count++;
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [ParticipantGroupFixtures::class, UserFixtures::class];
    }
}
