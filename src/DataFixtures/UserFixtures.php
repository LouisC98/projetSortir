<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_REFERENCE = 'user_';

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer 20 utilisateurs
        for ($i = 0; $i < 20; $i++) {
            $user = new User();

            $firstName = $faker->firstName();
            $lastName = $faker->lastName();

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPseudo($faker->unique()->userName());
            $user->setEmail($faker->unique()->email());
            $user->setPhone($faker->phoneNumber());
            $user->setActive($faker->boolean(90)); // 90% d'utilisateurs actifs

            // Hash du mot de passe (tous ont le même mot de passe pour les tests)
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
            $user->setPassword($hashedPassword);

            $user->setRoles(['ROLE_USER']);

            // Assigner un site aléatoire
            $siteIndex = $faker->numberBetween(0, 9);
            $site = $this->getReference(SiteFixtures::SITE_REFERENCE . $siteIndex, Site::class);
            $user->setSite($site);

            $manager->persist($user);
            $this->addReference(self::USER_REFERENCE . $i, $user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SiteFixtures::class,
        ];
    }
}
