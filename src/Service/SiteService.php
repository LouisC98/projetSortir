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
     * Supprime un site après vérification des dépendances
     *
     * Vérifie qu'aucune sortie n'est prévue sur ce site et qu'aucun participant
     * n'y est rattaché avant de procéder à la suppression.
     *
     * @param Site $site Le site à supprimer
     *
     * @return void
     *
     * @throws SortieException Si des sorties sont encore prévues sur ce site
     * @throws SiteException Si des participants sont encore rattachés à ce site
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