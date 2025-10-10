<?php

namespace App\Service;

use App\Entity\Site;
use App\Exception\SiteException;
use App\Exception\SortieException;
use Doctrine\ORM\EntityManagerInterface;

class SiteService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {

    }

    /**
     * @throws SortieException
     * @throws SiteException
     */
    public function delete(Site $site): void
    {
        if (count($site->getSorties()) > 0) {
            throw new SortieException("Impossible de supprimer le site, des sorties sont encore prévues");
        }
        if (count($site->getUsers()) > 0) {
            throw new SiteException("Impossible de supprimer le site, des participants y sont encore rattaché");
        }
        $this->entityManager->remove($site);
        $this->entityManager->flush();
    }
}