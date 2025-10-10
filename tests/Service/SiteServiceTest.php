<?php

namespace App\Tests\Service;

use App\Entity\Site;
use App\Entity\Sortie;
use App\Entity\User;
use App\Exception\SiteException;
use App\Exception\SortieException;
use App\Service\SiteService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SiteServiceTest extends TestCase
{
    private MockObject $entityManager;
    private SiteService $siteService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->siteService = new SiteService($this->entityManager);
    }

    public function testDeleteSiteSuccessfully()
    {
        $site = $this->createMock(Site::class);
        $site->method('getSorties')->willReturn(new ArrayCollection());
        $site->method('getUsers')->willReturn(new ArrayCollection());

        $this->entityManager->expects($this->once())->method('remove')->with($site);
        $this->entityManager->expects($this->once())->method('flush');

        $this->siteService->delete($site);
    }

    public function testDeleteSiteWithSortiesThrowsException()
    {
        $this->expectException(SortieException::class);
        $this->expectExceptionMessage("Impossible de supprimer le site, des sorties sont encore prévues");

        $site = $this->createMock(Site::class);
        $sortie = $this->createMock(Sortie::class);
        $site->method('getSorties')->willReturn(new ArrayCollection([$sortie]));

        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');

        $this->siteService->delete($site);
    }

    public function testDeleteSiteWithUsersThrowsException()
    {
        $this->expectException(SiteException::class);
        $this->expectExceptionMessage("Impossible de supprimer le site, des participants y sont encore rattaché");

        $site = $this->createMock(Site::class);
        $user = $this->createMock(User::class);
        $site->method('getSorties')->willReturn(new ArrayCollection());
        $site->method('getUsers')->willReturn(new ArrayCollection([$user]));

        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');

        $this->siteService->delete($site);
    }
}