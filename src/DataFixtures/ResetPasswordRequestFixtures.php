<?php
namespace App\DataFixtures;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ResetPasswordRequestFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < 5; $i++) {
            $user = $this->getReference(UserFixtures::USER_REFERENCE . $faker->numberBetween(0, 17), User::class);
            $expiresAt = new \DateTimeImmutable($faker->dateTimeBetween('now', '+10 days')->format('Y-m-d H:i:s'));
            $selector = bin2hex(random_bytes(8));
            $hashedToken = bin2hex(random_bytes(32));
            $request = new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);
            $manager->persist($request);
        }
        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
