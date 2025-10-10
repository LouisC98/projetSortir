<?php
// `src/Security/Voter/ParticipantGroupVoter.php`
namespace App\Security\Voter;

use App\Entity\ParticipantGroup;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ParticipantGroupVoter extends Voter
{
    public const VIEW = 'PG_VIEW';
    public const EDIT = 'PG_EDIT';
    public const DELETE = 'PG_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof ParticipantGroup;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        /** @var ParticipantGroup $group */
        $group = $subject;

        // Groupe privé: seul le propriétaire a tous les droits.
        return $group->getOwner() === $user;
    }
}
