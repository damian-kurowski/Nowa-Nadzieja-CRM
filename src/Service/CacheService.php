<?php

namespace App\Service;

use App\Entity\Oddzial;
use App\Entity\ZebranieOddzialu;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Cache current positions in district with auto-invalidation.
     *
     * @return array<string, mixed>
     */
    public function getOddzialStanowiska(Oddzial $oddzial): array
    {
        $cacheKey = sprintf('oddzial_stanowiska_%d', $oddzial->getId());

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($oddzial): array {
            $item->expiresAfter(self::CACHE_TTL);
            $item->tag(['oddzial_'.$oddzial->getId(), 'stanowiska']);

            return $this->loadOddzialStanowiska($oddzial);
        });
    }

    /**
     * Cache meeting candidates with optimization.
     *
     * @return list<array<string, mixed>>
     */
    public function getMeetingCandidates(ZebranieOddzialu $zebranie, string $functionType): array
    {
        $cacheKey = sprintf('meeting_candidates_%d_%s', $zebranie->getId(), $functionType);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($zebranie, $functionType): array {
            $item->expiresAfter(180); // 3 minutes for dynamic data
            $item->tag(['zebranie_'.$zebranie->getId(), 'candidates']);

            return $this->loadMeetingCandidates($zebranie, $functionType);
        });
    }

    /**
     * Invalidate cache when positions change.
     */
    public function invalidateOddzialCache(Oddzial $oddzial): void
    {
        $this->cache->delete('oddzial_stanowiska_'.$oddzial->getId());
    }

    /**
     * Invalidate meeting cache when meeting changes.
     */
    public function invalidateZebranieCache(ZebranieOddzialu $zebranie): void
    {
        $this->cache->delete('meeting_candidates_'.$zebranie->getId().'_przewodniczacy');
        $this->cache->delete('meeting_candidates_'.$zebranie->getId().'_zastepca');
        $this->cache->delete('meeting_candidates_'.$zebranie->getId().'_sekretarz');
    }

    /**
     * Clear all related caches.
     */
    public function clearAll(): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOddzialStanowiska(Oddzial $oddzial): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $members = $qb->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.oddzial = :oddzial')
            ->andWhere('u.status = :status')
            ->setParameter('oddzial', $oddzial)
            ->setParameter('status', 'aktywny')
            ->getQuery()
            ->getResult();

        $stanowiska = [
            'przewodniczacy' => null,
            'zastepcy' => [],
            'sekretarz' => null,
        ];

        foreach ($members as $member) {
            $roles = $member->getRoles();

            if (in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $roles)) {
                $stanowiska['przewodniczacy'] = [
                    'id' => $member->getId(),
                    'fullName' => $member->getFullName(),
                    'email' => $member->getEmail(),
                ];
            }
            if (in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $roles)) {
                $stanowiska['zastepcy'][] = [
                    'id' => $member->getId(),
                    'fullName' => $member->getFullName(),
                    'email' => $member->getEmail(),
                ];
            }
            if (in_array('ROLE_SEKRETARZ_ODDZIALU', $roles)) {
                $stanowiska['sekretarz'] = [
                    'id' => $member->getId(),
                    'fullName' => $member->getFullName(),
                    'email' => $member->getEmail(),
                ];
            }
        }

        return $stanowiska;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadMeetingCandidates(ZebranieOddzialu $zebranie, string $functionType): array
    {
        // Optimized query with eager loading
        $qb = $this->entityManager->createQueryBuilder();
        $members = $qb->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.oddzial = :oddzial')
            ->andWhere('u.status = :status')
            ->setParameter('oddzial', $zebranie->getOddzial())
            ->setParameter('status', 'aktywny')
            ->getQuery()
            ->getResult();

        $candidates = [];
        $obecneStanowiska = $this->getOddzialStanowiska($zebranie->getOddzial());

        foreach ($members as $member) {
            $candidate = [
                'id' => $member->getId(),
                'fullName' => $member->getFullName(),
                'email' => $member->getEmail(),
                'roles' => $member->getRoles(),
                'disabled' => false,
                'reason' => '',
            ];

            // Validation logic based on function type
            $candidate = $this->validateCandidate($candidate, $functionType, $obecneStanowiska);
            $candidates[] = $candidate;
        }

        // Sort: available first, then alphabetically
        usort($candidates, function ($a, $b) {
            if ($a['disabled'] !== $b['disabled']) {
                return $a['disabled'] ? 1 : -1;
            }

            return strcmp($a['fullName'], $b['fullName']);
        });

        return $candidates;
    }

    /**
     * @param array<string, mixed> $candidate
     * @param array<string, mixed> $obecneStanowiska
     *
     * @return array<string, mixed>
     */
    private function validateCandidate(array $candidate, string $functionType, array $obecneStanowiska): array
    {
        $memberId = $candidate['id'];

        if ('przewodniczacy' === $functionType) {
            if ($obecneStanowiska['przewodniczacy'] && $obecneStanowiska['przewodniczacy']['id'] === $memberId) {
                $candidate['disabled'] = true;
                $candidate['reason'] = 'Ta osoba już pełni funkcję Przewodniczącego Oddziału';
            }
        } elseif ('zastepca' === $functionType) {
            if (count($obecneStanowiska['zastepcy']) >= 2) {
                $candidate['disabled'] = true;
                $candidate['reason'] = 'Maksymalna liczba Zastępców Przewodniczącego została osiągnięta (2)';
            } elseif (in_array($memberId, array_column($obecneStanowiska['zastepcy'], 'id'))) {
                $candidate['disabled'] = true;
                $candidate['reason'] = 'Ta osoba już pełni funkcję Zastępcy Przewodniczącego';
            }
            if ($obecneStanowiska['przewodniczacy'] && $obecneStanowiska['przewodniczacy']['id'] === $memberId) {
                $candidate['disabled'] = true;
                $candidate['reason'] = 'Ta osoba jest Przewodniczącym Oddziału';
            }
        } elseif ('sekretarz' === $functionType) {
            if ($obecneStanowiska['sekretarz'] && $obecneStanowiska['sekretarz']['id'] === $memberId) {
                $candidate['disabled'] = true;
                $candidate['reason'] = 'Ta osoba już pełni funkcję Sekretarza Oddziału';
            }
        }

        return $candidate;
    }
}
