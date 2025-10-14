<?php

namespace App\Tests\Security\Voter;

use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use App\Entity\Place;
use App\Entity\City;
use App\Enum\State;
use App\Security\Voter\SortieVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SortieVoterTest extends TestCase
{
    private SortieVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new SortieVoter();
    }

    private function createUser(string $pseudo = 'user', array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setPseudo($pseudo);
        $user->setEmail($pseudo . '@test.com');
        $user->setRoles($roles);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPhone('0123456789');
        $user->setActive(true);

        $site = new Site();
        $site->setName('Test Site');
        $user->setSite($site);

        return $user;
    }

    private function createSortie(User $organisateur, State $state = State::OPEN): Sortie
    {
        $sortie = new Sortie();
        $sortie->setName('Test Sortie');
        $sortie->setStartDateTime(new \DateTime('+1 week'));
        $sortie->setDuration(120);
        $sortie->setRegistrationDeadline(new \DateTime('+3 days'));
        $sortie->setMaxRegistration(10);
        $sortie->setState($state);
        $sortie->setOrganisateur($organisateur);
        $sortie->setSite($organisateur->getSite());

        $city = new City();
        $city->setName('Test City');
        $city->setPostalCode('75000');

        $place = new Place();
        $place->setName('Test Place');
        $place->setStreet('Test Street');
        $place->setCity($city);

        $sortie->setPlace($place);

        return $sortie;
    }

    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }

    public function testOrganisateurCanEditSortieOpen(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganisateurCanEditSortieCreated(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::CREATED);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotEditOtherUserSortie(): void
    {
        $organisateur = $this->createUser('organisateur');
        $otherUser = $this->createUser('other');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($otherUser);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanEditAnySortie(): void
    {
        $organisateur = $this->createUser('organisateur');
        $admin = $this->createUser('admin', ['ROLE_USER', 'ROLE_ADMIN']);
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($admin);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganisateurCanDeleteSortieCreated(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::CREATED);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganisateurCannotDeleteSortieOpen(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOrganisateurCanCancelSortieOpen(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::CANCEL]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCanCancelAnySortie(): void
    {
        $organisateur = $this->createUser('organisateur');
        $admin = $this->createUser('admin', ['ROLE_USER', 'ROLE_ADMIN']);
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($admin);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::CANCEL]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganisateurCanPublishSortieCreated(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::CREATED);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::PUBLISH]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCanRegisterToSortieOpen(): void
    {
        $organisateur = $this->createUser('organisateur');
        $user = $this->createUser('user');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::REGISTER]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOrganisateurCannotRegisterToOwnSortie(): void
    {
        $organisateur = $this->createUser('organisateur');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($organisateur);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::REGISTER]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUserCanUnregisterFromSortie(): void
    {
        $organisateur = $this->createUser('organisateur');
        $user = $this->createUser('user');
        $sortie = $this->createSortie($organisateur, State::OPEN);

        // Inscrire l'utilisateur
        $sortie->addParticipant($user);

        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::UNREGISTER]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testUserCannotUnregisterIfNotRegistered(): void
    {
        $organisateur = $this->createUser('organisateur');
        $user = $this->createUser('user');
        $sortie = $this->createSortie($organisateur, State::OPEN);
        $token = $this->createToken($user);

        $result = $this->voter->vote($token, $sortie, [SortieVoter::UNREGISTER]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}

