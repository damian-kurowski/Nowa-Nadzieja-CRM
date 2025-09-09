<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\WystepMedialny;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, WystepMedialny>
 */
class WystepMedialnyVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof WystepMedialny;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $wystep = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($wystep, $user);
            case self::EDIT:
                return $this->canEdit($wystep, $user);
            case self::DELETE:
                return $this->canDelete($wystep, $user);
        }

        return false;
    }

    private function canView(WystepMedialny $wystep, User $user): bool
    {
        // Rzecznicy prasowi i admini widzą wszystko
        if ($this->hasRole($user, ['ROLE_RZECZNIK_PRASOWY', 'ROLE_ADMIN'])) {
            return true;
        }

        // Użytkownik widzi swoje zgłoszenia lub wystąpienia w których uczestniczy
        return $wystep->getZglaszajacy() === $user || $wystep->getMowcy()->contains($user);
    }

    private function canEdit(WystepMedialny $wystep, User $user): bool
    {
        // Rzecznicy prasowi i admini mogą edytować wszystko
        if ($this->hasRole($user, ['ROLE_RZECZNIK_PRASOWY', 'ROLE_ADMIN'])) {
            return true;
        }

        // Użytkownik może edytować tylko swoje zgłoszenia
        return $wystep->getZglaszajacy() === $user;
    }

    private function canDelete(WystepMedialny $wystep, User $user): bool
    {
        // Tylko rzecznicy prasowi i admini mogą usuwać
        return $this->hasRole($user, ['ROLE_RZECZNIK_PRASOWY', 'ROLE_ADMIN']);
    }

    /**
     * @param array<int, string> $roles
     */
    private function hasRole(User $user, array $roles): bool
    {
        foreach ($roles as $role) {
            if (in_array($role, $user->getRoles())) {
                return true;
            }
        }

        return false;
    }
}
