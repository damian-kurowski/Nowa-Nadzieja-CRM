<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, User>
 */
class UserVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $targetUser = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($targetUser, $user);
            case self::EDIT:
                return $this->canEdit($targetUser, $user);
            case self::DELETE:
                return $this->canDelete($targetUser, $user);
        }

        return false;
    }

    private function canView(User $targetUser, UserInterface $user): bool
    {
        // Logika uprawnień do oglądania
        if ($user instanceof User && $this->isAdmin($user)) {
            return true;
        }

        if ($user instanceof User && $targetUser === $user) {
            return true; // Każdy może zobaczyć siebie
        }

        // Sprawdzenie hierarchii organizacyjnej
        return $user instanceof User && $this->hasHierarchicalAccess($targetUser, $user);
    }

    private function canEdit(User $targetUser, UserInterface $user): bool
    {
        if ($user instanceof User && $this->isAdmin($user)) {
            return true;
        }

        if ($user instanceof User && $targetUser === $user) {
            return true; // Każdy może edytować siebie (ograniczone pola)
        }

        return $user instanceof User && $this->hasHierarchicalAccess($targetUser, $user);
    }

    private function canDelete(User $targetUser, UserInterface $user): bool
    {
        return ($user instanceof User && $this->isAdmin($user)) || ($user instanceof User && $this->hasHierarchicalAccess($targetUser, $user));
    }

    private function isAdmin(User $user): bool
    {
        $adminRoles = [
            'ROLE_PREZES_PARTII',
            'ROLE_WICEPREZES_PARTII',
            'ROLE_SEKRETARZ_PARTII',
            'ROLE_SKARBNIK_PARTII',
            'ROLE_PREZES_REGIONU',
        ];

        return !empty(array_intersect($user->getRoles(), $adminRoles));
    }

    private function hasHierarchicalAccess(User $targetUser, User $currentUser): bool
    {
        // Prezes regionu ma dostęp do wszystkich użytkowników w okręgach swojego regionu
        if ($currentUser->hasRole('ROLE_PREZES_REGIONU') && $currentUser->getRegion()) {
            $targetUserOkreg = $targetUser->getOkreg();
            if ($targetUserOkreg && $targetUserOkreg->getRegion() === $currentUser->getRegion()) {
                return true;
            }
        }

        // Prezes okręgu ma dostęp do użytkowników w swoim okręgu
        if ($currentUser->hasRole('ROLE_PREZES_OKREGU') && $currentUser->getOkreg()) {
            if ($targetUser->getOkreg() === $currentUser->getOkreg()) {
                return true;
            }
        }

        // Przewodniczący oddziału ma dostęp do użytkowników w swoim oddziale
        if ($currentUser->hasRole('ROLE_PRZEWODNICZACY_ODDZIALU') && $currentUser->getOddzial()) {
            if ($targetUser->getOddzial() === $currentUser->getOddzial()) {
                return true;
            }
        }

        return false;
    }
}
