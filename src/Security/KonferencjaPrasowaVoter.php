<?php

namespace App\Security;

use App\Entity\KonferencjaPrasowa;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, KonferencjaPrasowa>
 */
class KonferencjaPrasowaVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof KonferencjaPrasowa;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $konferencja = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($konferencja, $user);
            case self::EDIT:
                return $this->canEdit($konferencja, $user);
            case self::DELETE:
                return $this->canDelete($konferencja, $user);
        }

        return false;
    }

    private function canView(KonferencjaPrasowa $konferencja, User $user): bool
    {
        // Rzecznicy prasowi i admini widzą wszystko
        if ($this->hasRole($user, ['ROLE_RZECZNIK_PRASOWY', 'ROLE_ADMIN'])) {
            return true;
        }

        // Użytkownik widzi swoje zgłoszenia lub konferencje w których uczestniczy
        return $konferencja->getZglaszajacy() === $user || $konferencja->getMowcy()->contains($user);
    }

    private function canEdit(KonferencjaPrasowa $konferencja, User $user): bool
    {
        // Rzecznicy prasowi i admini mogą edytować wszystko
        if ($this->hasRole($user, ['ROLE_RZECZNIK_PRASOWY', 'ROLE_ADMIN'])) {
            return true;
        }

        // Użytkownik może edytować tylko swoje zgłoszenia
        return $konferencja->getZglaszajacy() === $user;
    }

    private function canDelete(KonferencjaPrasowa $konferencja, User $user): bool
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
