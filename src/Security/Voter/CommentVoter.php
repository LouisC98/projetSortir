<?php

namespace App\Security\Voter;

use App\Entity\Comment;
use App\Entity\Sortie;
use App\Entity\User;
use App\Enum\State;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    public const CREATE = 'COMMENT_CREATE';
    public const EDIT = 'COMMENT_EDIT';
    public const DELETE = 'COMMENT_DELETE';
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::EDIT,
                self::DELETE,
                self::CREATE,
            ]) && $subject instanceof Comment;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Comment $comment */
        $comment = $subject;

        return match ($attribute) {
            self::CREATE => $this->canCreate($comment, $user),
            self::EDIT => $this->canEdit($comment, $user),
            self::DELETE => $this->canDelete($comment, $user),
            default => false,
        };
    }

    private function canCreate(Comment $comment, User $user): bool
    {
        $sortie = $comment->getSortie();

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isParticipant = in_array($user, $sortie->getParticipants()->toArray());
        $isNotCreated = $sortie->getState() !== State::CREATED;

        return $isAdmin || ($isParticipant && $isNotCreated);
    }

    private function canEdit(Comment $comment, User $user): bool
    {
        $isCommentOwner = $user === $comment->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

        return $isAdmin || $isCommentOwner;
    }

    private function canDelete(Comment $comment, User $user): bool
    {
        $isCommentOwner = $user === $comment->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

        return $isAdmin || $isCommentOwner;
    }
}