<?php
// src/Controller/RegistrationController.php
namespace App\Controller;

use App\Entity\User;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SiteRepository $siteRepo // pour le champ site (ManyToOne)
    ): Response {
        if ($request->isMethod('POST')) {
            $pseudo = $request->request->get('pseudo');
            $email = $request->request->get('email');
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $phone = $request->request->get('phone');
            $password = $request->request->get('password');
            $siteId = $request->request->get('site');

            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');


            // vérification basique
            if (!$email || !$password || !$pseudo || !$siteId) {
                throw new CustomUserMessageAuthenticationException("Champs requis manquants");
            }

            // vérifier unicité email / pseudo
            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Cet email est déjà utilisé');
                return $this->redirectToRoute('app_register');
            }

            if ($em->getRepository(User::class)->findOneBy(['pseudo' => $pseudo])) {
                $this->addFlash('error', 'Ce pseudo est déjà pris');
                return $this->redirectToRoute('app_register');
            }

            $site = $siteRepo->find($siteId);
            if (!$site) {
                throw new \Exception('Site non trouvé');
            }

            // création user
            $user = new User();
            $user->setPseudo($pseudo);
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPhone($phone);
            $user->setSite($site);
            $user->setActive(true);


            // Vérifie que les mots de passe sont identiques
            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_register');
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        $sites = $siteRepo->findAll();

        return $this->render('security/register.html.twig', [
            'sites' => $sites,
        ]);
    }
}

