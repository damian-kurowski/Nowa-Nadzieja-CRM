<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogService
{
    private EntityManagerInterface $entityManager;
    private ActivityLogRepository $activityLogRepository;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActivityLogRepository $activityLogRepository,
        RequestStack $requestStack,
        Security $security,
    ) {
        $this->entityManager = $entityManager;
        $this->activityLogRepository = $activityLogRepository;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function log(string $action, string $description, ?string $entityType = null, ?int $entityId = null, ?array $metadata = null, ?User $user = null): void
    {
        $user = $user ?? $this->security->getUser();

        if (!$user instanceof User) {
            return; // Don't log if no user
        }

        $request = $this->requestStack->getCurrentRequest();

        $activityLog = new ActivityLog();
        $activityLog->setUser($user)
            ->setAction($action)
            ->setDescription($description)
            ->setEntityType($entityType)
            ->setEntityId($entityId)
            ->setMetadata($metadata);

        if ($request) {
            $activityLog->setIpAddress($request->getClientIp())
                ->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();
    }

    public function logLogin(User $user): void
    {
        // Update login timestamps
        $user->setPreviousLoginAt($user->getLastLoginAt());
        $user->setLastLoginAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->log(
            'login',
            sprintf('Użytkownik %s zalogował się do systemu', $user->getFullName()),
            'User',
            $user->getId(),
            [
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
            $user
        );
    }

    public function logEntityCreate(string $entityType, int $entityId, string $entityName, ?User $user = null): void
    {
        $this->log(
            'create',
            sprintf('Utworzono %s: %s', $this->getEntityDisplayName($entityType), $entityName),
            $entityType,
            $entityId,
            ['name' => $entityName],
            $user
        );
    }

    public function logEntityUpdate(string $entityType, int $entityId, string $entityName, ?User $user = null): void
    {
        $this->log(
            'update',
            sprintf('Zaktualizowano %s: %s', $this->getEntityDisplayName($entityType), $entityName),
            $entityType,
            $entityId,
            ['name' => $entityName],
            $user
        );
    }

    public function logEntityDelete(string $entityType, int $entityId, string $entityName, ?User $user = null): void
    {
        $this->log(
            'delete',
            sprintf('Usunięto %s: %s', $this->getEntityDisplayName($entityType), $entityName),
            $entityType,
            $entityId,
            ['name' => $entityName],
            $user
        );
    }

    public function logDocumentSign(int $documentId, string $documentTitle, ?User $user = null): void
    {
        $this->log(
            'document_sign',
            sprintf('Podpisano dokument: %s', $documentTitle),
            'Dokument',
            $documentId,
            ['title' => $documentTitle],
            $user
        );
    }

    public function logDocumentCreate(int $documentId, string $documentTitle, ?User $user = null): void
    {
        $this->log(
            'document_create',
            sprintf('Utworzono dokument: %s', $documentTitle),
            'Dokument',
            $documentId,
            ['title' => $documentTitle],
            $user
        );
    }

    public function logMediaAppearance(int $mediaId, string $mediaTitle, ?User $user = null): void
    {
        $this->log(
            'media_create',
            sprintf('Dodano wystąpienie medialne: %s', $mediaTitle),
            'WystepMedialny',
            $mediaId,
            ['title' => $mediaTitle],
            $user
        );
    }

    public function logPressConference(int $conferenceId, string $conferenceTitle, ?User $user = null): void
    {
        $this->log(
            'conference_create',
            sprintf('Dodano konferencję prasową: %s', $conferenceTitle),
            'KonferencjaPrasowa',
            $conferenceId,
            ['title' => $conferenceTitle],
            $user
        );
    }

    public function logDocumentReject(int $documentId, string $documentTitle, ?User $user = null): void
    {
        $this->log(
            'document_reject',
            sprintf('Odrzucono dokument: %s', $documentTitle),
            'Dokument',
            $documentId,
            ['title' => $documentTitle],
            $user
        );
    }

    public function logDocumentDownload(int $documentId, string $documentTitle, ?User $user = null): void
    {
        $this->log(
            'document_download',
            sprintf('Pobrano dokument: %s', $documentTitle),
            'Dokument',
            $documentId,
            ['title' => $documentTitle],
            $user
        );
    }

    /**
     * @return ActivityLog[]
     */
    public function getRecentActivitiesForUser(User $user, int $limit = 5): array
    {
        return $this->activityLogRepository->findRecentForUser($user, $limit);
    }

    /**
     * @return ActivityLog[]
     */
    public function getRecentActivitiesInScope(User $user, int $limit = 5): array
    {
        return $this->activityLogRepository->findRecentForUserScope($user, $limit);
    }

    private function getEntityDisplayName(string $entityType): string
    {
        return match ($entityType) {
            'User' => 'użytkownika',
            'Sympatyk' => 'sympatyka',
            'Darczyca' => 'darczyńcę',
            'CzlonekMlodziezowki' => 'członka młodzieżówki',
            'BylyCzlonek' => 'byłego członka',
            'Dokument' => 'dokument',
            'WystepMedialny' => 'wystąpienie medialne',
            'KonferencjaPrasowa' => 'konferencję prasową',
            default => strtolower($entityType),
        };
    }

    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        return $this->activityLogRepository->cleanOldLogs($daysToKeep);
    }
}
