<?php

namespace App\Tests\Service;

use App\Entity\Site;
use App\Entity\User;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use App\Service\UserImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserImportServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private SiteRepository $siteRepository;
    private ValidatorInterface $validator;
    private UserImportService $userImportService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->siteRepository = $this->createMock(SiteRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->userImportService = new UserImportService(
            $this->entityManager,
            $this->passwordHasher,
            $this->userRepository,
            $this->siteRepository,
            $this->validator
        );
    }

    private function createCsvFile(string $content): File
    {
        $filePath = tempnam(sys_get_temp_dir(), 'test_csv');
        file_put_contents($filePath, $content);
        return new File($filePath);
    }

    public function testImportUsersWithValidData(): void
    {
        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "test@example.com,testuser,Doe,John,0123456789,Nantes\n";
        $file = $this->createCsvFile($csvContent);

        $this->userRepository->expects($this->any())->method('findOneBy')->willReturn(null);
        $this->siteRepository->expects($this->once())->method('findOneBy')->with(['name' => 'Nantes'])->willReturn(new Site());
        $this->validator->expects($this->once())->method('validate')->willReturn(new ConstraintViolationList());
        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn('hashed_password');
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->userImportService->importUsers($file);

        $this->assertEquals(1, $result['successful']);
        $this->assertEmpty($result['errors']);

        unlink($file->getPathname());
    }

    public function testImportUsersWithExistingEmail(): void
    {
        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "test@example.com,testuser,Doe,John,0123456789,Nantes\n";
        $file = $this->createCsvFile($csvContent);

        $this->userRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn(new User());

        $result = $this->userImportService->importUsers($file);

        $this->assertEquals(0, $result['successful']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('L\'email "test@example.com" est déjà utilisé.', $result['errors'][0]);

        unlink($file->getPathname());
    }

    public function testImportUsersWithNonExistentSite(): void
    {
        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "test@example.com,testuser,Doe,John,0123456789,SiteInconnu\n";
        $file = $this->createCsvFile($csvContent);

        $this->userRepository->expects($this->any())->method('findOneBy')->willReturn(null);
        $this->siteRepository->expects($this->once())->method('findOneBy')->with(['name' => 'SiteInconnu'])->willReturn(null);

        $result = $this->userImportService->importUsers($file);

        $this->assertEquals(0, $result['successful']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Le site "SiteInconnu" n\'existe pas.', $result['errors'][0]);

        unlink($file->getPathname());
    }

    public function testImportUsersWithValidationError(): void
    {
        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "invalid-email,testuser,Doe,John,0123456789,Nantes\n";
        $file = $this->createCsvFile($csvContent);

        $this->userRepository->expects($this->any())->method('findOneBy')->willReturn(null);
        $this->siteRepository->expects($this->once())->method('findOneBy')->with(['name' => 'Nantes'])->willReturn(new Site());

        $violation = new ConstraintViolation('Email invalide.', '', [], '', 'email', 'invalid-email');
        $violations = new ConstraintViolationList([$violation]);
        $this->validator->expects($this->once())->method('validate')->willReturn($violations);

        $result = $this->userImportService->importUsers($file);

        $this->assertEquals(0, $result['successful']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Ligne 2 (email): Email invalide.', $result['errors'][0]);

        unlink($file->getPathname());
    }
    
    public function testImportUsersWithMalformedCsv(): void
    {
        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "test@example.com,testuser,Doe,John\n"; // Missing columns
        $file = $this->createCsvFile($csvContent);

        $result = $this->userImportService->importUsers($file);

        $this->assertEquals(0, $result['successful']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals('Ligne 2: Le nombre de colonnes ne correspond pas à l\'en-tête.', $result['errors'][0]);

        unlink($file->getPathname());
    }
}
