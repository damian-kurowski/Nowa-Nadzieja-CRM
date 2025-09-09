<?php

namespace App\Service;

use App\Entity\Dokument;
use App\Entity\Oddzial;
use App\Entity\Okreg;
use App\Entity\PodpisDokumentu;
use App\Entity\User;
use App\Repository\DokumentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\DocumentTemplates;
use App\Document\DocumentFactory;

class DokumentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DokumentRepository $dokumentRepository,
        private UserRepository $userRepository,
        private FormFactoryInterface $formFactory,
        private LoggerInterface $logger,
        private ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    /**
     * Zwraca dostępne typy dokumentów dla użytkownika.
     *
     * @return array<string, array<string, string>>
     */
    public function getAvailableDocumentTypes(User $user): array
    {
        $types = [];
        $userRoles = $user->getRoles();

        // 1. Okręgowy Pełnomocnik ds. przyjmowania nowych członków
        if (in_array('ROLE_PELNOMOCNIK_PRZYJMOWANIA', $userRoles)) {
            $types[Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK] = [
                'title' => 'Przyjęcie członka przez Pełnomocnika',
                'category' => 'Członkostwo',
                'description' => 'Dokument przyjmujący kandydata do partii przez Okręgowego Pełnomocnika ds. przyjmowania nowych członków',
            ];
        }

        // 2. Zarząd okręgu (Prezes Okręgu + drugi członek zarządu)
        if (in_array('ROLE_PREZES_OKREGU', $userRoles)) {
            $types[Dokument::TYP_PRZYJECIE_CZLONKA_OKREG] = [
                'title' => 'Przyjęcie członka przez zarząd okręgu',
                'category' => 'Członkostwo',
                'description' => 'Dokument przyjmujący kandydata do partii przez zarząd okręgu',
            ];
        }

        // 3. Zarząd krajowy (Prezes Partii lub Sekretarz Partii + drugi członek zarządu krajowego)
        if (in_array('ROLE_PREZES_PARTII', $userRoles) || in_array('ROLE_SEKRETARZ_PARTII', $userRoles)) {
            $types[Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY] = [
                'title' => 'Przyjęcie członka przez zarząd krajowy',
                'category' => 'Członkostwo',
                'description' => 'Dokument przyjmujący kandydata do partii przez zarząd krajowy',
            ];
        }

        // 4. Dokumenty dostępne tylko dla Prezesa Partii
        if (in_array('ROLE_PREZES_PARTII', $userRoles)) {
            $types[Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR] = [
                'title' => 'Powołanie Pełnomocnika ds. Struktur',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Pełnomocnika ds. Struktur przez Prezesa Partii',
            ];

            $types[Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR] = [
                'title' => 'Odwołanie Pełnomocnika ds. Struktur',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Pełnomocnika ds. Struktur przez Prezesa Partii',
            ];

            $types[Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII] = [
                'title' => 'Powołanie Sekretarza Partii',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Sekretarza Partii przez Prezesa Partii',
            ];

            $types[Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII] = [
                'title' => 'Odwołanie Sekretarza Partii',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Sekretarza Partii przez Prezesa Partii',
            ];

            $types[Dokument::TYP_POWOLANIE_SKARBNIK_PARTII] = [
                'title' => 'Powołanie Skarbnika Partii',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Skarbnika Partii przez Prezesa Partii',
            ];

            $types[Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII] = [
                'title' => 'Odwołanie Skarbnika Partii',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Skarbnika Partii przez Prezesa Partii',
            ];

            $types[Dokument::TYP_POWOLANIE_WICEPREZES_PARTII] = [
                'title' => 'Powołanie Wiceprezesa Partii',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Wiceprezesa Partii przez Prezesa Partii',
            ];

            $types[Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII] = [
                'title' => 'Odwołanie Wiceprezesa Partii',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Wiceprezesa Partii przez Prezesa Partii',
            ];

            $types[Dokument::TYP_ODWOLANIE_PREZES_OKREGU] = [
                'title' => 'Odwołanie Prezesa Okręgu',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Prezesa Okręgu przez Prezesa Partii',
            ];
        }

        // 5. Dokumenty dostępne dla Prezesa Partii i Sekretarza Partii
        if (in_array('ROLE_PREZES_PARTII', $userRoles) || in_array('ROLE_SEKRETARZ_PARTII', $userRoles)) {
            $types[Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU] = [
                'title' => 'Powołanie p.o. Prezesa Okręgu',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Pełniącego Obowiązki Prezesa Okręgu',
            ];

            $types[Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU] = [
                'title' => 'Odwołanie p.o. Prezesa Okręgu',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Pełniącego Obowiązki Prezesa Okręgu',
            ];
        }

        // 6. Dokumenty dostępne dla Prezesów Okręgów
        if (in_array('ROLE_PREZES_OKREGU', $userRoles) || in_array('ROLE_PO_PREZES_OKREGU', $userRoles)) {
            $types[Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU] = [
                'title' => 'Powołanie Sekretarza Okręgu',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Sekretarza Okręgu przez Prezesa Okręgu',
            ];

            $types[Dokument::TYP_ODWOLANIE_SEKRETARZ_OKREGU] = [
                'title' => 'Odwołanie Sekretarza Okręgu',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Sekretarza Okręgu przez Prezesa Okręgu',
            ];

            $types[Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU] = [
                'title' => 'Powołanie Skarbnika Okręgu',
                'category' => 'Powołania',
                'description' => 'Dokument powołujący Skarbnika Okręgu przez Prezesa Okręgu',
            ];

            $types[Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU] = [
                'title' => 'Odwołanie Skarbnika Okręgu',
                'category' => 'Odwołania',
                'description' => 'Dokument odwołujący Skarbnika Okręgu przez Prezesa Okręgu',
            ];

            $types[Dokument::TYP_UTWORZENIE_ODDZIALU] = [
                'title' => 'Utworzenie Oddziału',
                'category' => 'Struktura',
                'description' => 'Dokument tworzący nowy oddział w okręgu przez Zarząd Okręgu',
            ];
        }

        // 6. Dokumenty dostępne dla Sekretarza Okręgu
        if (in_array('ROLE_SEKRETARZ_OKREGU', $userRoles)) {
            $types[Dokument::TYP_WYZNACZENIE_OBSERWATORA] = [
                'title' => 'Wyznaczenie Obserwatora Zebrania',
                'category' => 'Zebrania',
                'description' => 'Dokument wyznaczający obserwatora zebrania członków oddziału',
            ];
        }

        // Admin może wszystko
        if (in_array('ROLE_ADMIN', $userRoles)) {
            $types = [
                Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => [
                    'title' => 'Przyjęcie członka przez Pełnomocnika',
                    'category' => 'Członkostwo',
                    'description' => 'Dokument przyjmujący kandydata do partii przez Okręgowego Pełnomocnika ds. przyjmowania nowych członków',
                ],
                Dokument::TYP_PRZYJECIE_CZLONKA_OKREG => [
                    'title' => 'Przyjęcie członka przez zarząd okręgu',
                    'category' => 'Członkostwo',
                    'description' => 'Dokument przyjmujący kandydata do partii przez zarząd okręgu',
                ],
                Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY => [
                    'title' => 'Przyjęcie członka przez zarząd krajowy',
                    'category' => 'Członkostwo',
                    'description' => 'Dokument przyjmujący kandydata do partii przez zarząd krajowy',
                ],
                Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR => [
                    'title' => 'Powołanie Pełnomocnika ds. Struktur',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Pełnomocnika ds. Struktur przez Prezesa Partii',
                ],
                Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR => [
                    'title' => 'Odwołanie Pełnomocnika ds. Struktur',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Pełnomocnika ds. Struktur przez Prezesa Partii',
                ],
                Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII => [
                    'title' => 'Powołanie Sekretarza Partii',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Sekretarza Partii przez Prezesa Partii',
                ],
                Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII => [
                    'title' => 'Odwołanie Sekretarza Partii',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Sekretarza Partii przez Prezesa Partii',
                ],
                Dokument::TYP_POWOLANIE_SKARBNIK_PARTII => [
                    'title' => 'Powołanie Skarbnika Partii',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Skarbnika Partii przez Prezesa Partii',
                ],
                Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII => [
                    'title' => 'Odwołanie Skarbnika Partii',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Skarbnika Partii przez Prezesa Partii',
                ],
                Dokument::TYP_POWOLANIE_WICEPREZES_PARTII => [
                    'title' => 'Powołanie Wiceprezesa Partii',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Wiceprezesa Partii przez Prezesa Partii',
                ],
                Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII => [
                    'title' => 'Odwołanie Wiceprezesa Partii',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Wiceprezesa Partii przez Prezesa Partii',
                ],
                Dokument::TYP_ODWOLANIE_PREZES_OKREGU => [
                    'title' => 'Odwołanie Prezesa Okręgu',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Prezesa Okręgu przez Prezesa Partii',
                ],
                Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU => [
                    'title' => 'Powołanie p.o. Prezesa Okręgu',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Pełniącego Obowiązki Prezesa Okręgu przez Prezesa Partii',
                ],
                Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU => [
                    'title' => 'Odwołanie p.o. Prezesa Okręgu',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Pełniącego Obowiązki Prezesa Okręgu przez Prezesa Partii',
                ],
                Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU => [
                    'title' => 'Powołanie Sekretarza Okręgu',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Sekretarza Okręgu przez Prezesa Okręgu',
                ],
                Dokument::TYP_ODWOLANIE_SEKRETARZ_OKREGU => [
                    'title' => 'Odwołanie Sekretarza Okręgu',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Sekretarza Okręgu przez Prezesa Okręgu',
                ],
                Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU => [
                    'title' => 'Powołanie Skarbnika Okręgu',
                    'category' => 'Powołania',
                    'description' => 'Dokument powołujący Skarbnika Okręgu przez Prezesa Okręgu',
                ],
                Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU => [
                    'title' => 'Odwołanie Skarbnika Okręgu',
                    'category' => 'Odwołania',
                    'description' => 'Dokument odwołujący Skarbnika Okręgu przez Prezesa Okręgu',
                ],
                Dokument::TYP_UTWORZENIE_ODDZIALU => [
                    'title' => 'Utworzenie Oddziału',
                    'category' => 'Struktura',
                    'description' => 'Dokument tworzący nowy oddział w okręgu przez Zarząd Okręgu',
                ],
                Dokument::TYP_WYZNACZENIE_OBSERWATORA => [
                    'title' => 'Wyznaczenie Obserwatora Zebrania',
                    'category' => 'Zebrania',
                    'description' => 'Dokument wyznaczający obserwatora zebrania członków oddziału',
                ],
                Dokument::TYP_WYZNACZENIE_PROTOKOLANTA => [
                    'title' => 'Wyznaczenie Protokolanta Zebrania',
                    'category' => 'Zebrania',
                    'description' => 'Dokument wyznaczający protokolanta zebrania członków oddziału',
                ],
                Dokument::TYP_WYZNACZENIE_PROWADZACEGO => [
                    'title' => 'Wyznaczenie Prowadzącego Zebrania',
                    'category' => 'Zebrania',
                    'description' => 'Dokument wyznaczający prowadzącego zebrania członków oddziału',
                ],
                Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU => [
                    'title' => 'Powołanie Przewodniczącego Oddziału',
                    'category' => 'Zebrania',
                    'description' => 'Dokument powołujący przewodniczącego oddziału przez zebranie członków',
                ],
                Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU => [
                    'title' => 'Odwołanie Przewodniczącego Oddziału',
                    'category' => 'Zebrania',
                    'description' => 'Dokument odwołujący przewodniczącego oddziału przez zebranie członków',
                ],
                Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => [
                    'title' => 'Powołanie Zastępcy Przewodniczącego',
                    'category' => 'Zebrania',
                    'description' => 'Dokument powołujący zastępcę przewodniczącego oddziału przez zebranie członków',
                ],
                Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => [
                    'title' => 'Odwołanie Zastępcy Przewodniczącego',
                    'category' => 'Zebrania',
                    'description' => 'Dokument odwołujący zastępcę przewodniczącego oddziału przez zebranie członków',
                ],
                Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU => [
                    'title' => 'Powołanie Sekretarza Oddziału',
                    'category' => 'Zebrania',
                    'description' => 'Dokument powołujący sekretarza oddziału przez zebranie członków',
                ],
                Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU => [
                    'title' => 'Odwołanie Sekretarza Oddziału',
                    'category' => 'Zebrania',
                    'description' => 'Dokument odwołujący sekretarza oddziału przez zebranie członków',
                ],
            ];
        }

        return $types;
    }

    /**
     * Sprawdza czy użytkownik może tworzyć dany typ dokumentu.
     */
    public function canCreateDocumentType(User $user, string $type): bool
    {
        return true;
    }

    /**
     * Zwraca definicję dokumentu.
     *
     * @return array<string, mixed>|null
     */
    public function getDocumentDefinition(string $type): ?array
    {
        return match ($type) {
            Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => [
                'title' => 'Dokument przyjęcia członka przez Okręgowego Pełnomocnika',
                'description' => 'Dokument przyjmujący kandydata do partii przez Okręgowego Pełnomocnika ds. przyjmowania nowych członków',
                'fields' => [
                    'kandydat' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Kandydat',
                        'required' => true,
                        'query_filter' => 'kandydaci',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Pełnomocnika
                ],
            ],
            Dokument::TYP_PRZYJECIE_CZLONKA_OKREG => [
                'title' => 'Dokument przyjęcia członka przez zarząd okręgu',
                'description' => 'Dokument przyjmujący kandydata do partii przez zarząd okręgu',
                'fields' => [
                    'kandydat' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Kandydat',
                        'required' => true,
                        'query_filter' => 'kandydaci',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'drugi_podpisujacy' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Drugi podpisujący (członek zarządu okręgu)',
                        'required' => true,
                        'query_filter' => 'board_members',
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Prezes okręgu
                    'drugi_podpisujacy' => true,  // Wybrany członek zarządu
                ],
            ],
            Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY => [
                'title' => 'Dokument przyjęcia członka przez zarząd krajowy',
                'description' => 'Dokument przyjmujący kandydata do partii przez zarząd krajowy',
                'fields' => [
                    'kandydat' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Kandydat',
                        'required' => true,
                        'query_filter' => 'kandydaci_krajowy',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'drugi_podpisujacy' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Drugi podpisujący (członek zarządu krajowego)',
                        'required' => true,
                        'query_filter' => 'national_board_members',
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Prezes Partii lub Sekretarz Partii
                    'drugi_podpisujacy' => true,  // Wybrany członek zarządu krajowego
                ],
            ],
            Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR => [
                'title' => 'Dokument powołania Pełnomocnika ds. Struktur',
                'description' => 'Dokument powołujący Pełnomocnika ds. Struktur przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania',
                        'required' => true,
                        'query_filter' => 'active_members_all',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR => [
                'title' => 'Dokument odwołania Pełnomocnika ds. Struktur',
                'description' => 'Dokument odwołujący Pełnomocnika ds. Struktur przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Pełnomocnik do odwołania',
                        'required' => true,
                        'query_filter' => 'pelnomocnicy_struktur',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'powod' => [
                        'type' => 'textarea',
                        'label' => 'Powód odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII => [
                'title' => 'Dokument powołania Sekretarza Partii',
                'description' => 'Dokument powołujący Sekretarza Partii przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania na Sekretarza Partii',
                        'required' => true,
                        'query_filter' => 'active_members_all',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII => [
                'title' => 'Dokument odwołania Sekretarza Partii',
                'description' => 'Dokument odwołujący Sekretarza Partii przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Sekretarz Partii do odwołania',
                        'required' => true,
                        'query_filter' => 'sekretarze_partii',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'powod' => [
                        'type' => 'textarea',
                        'label' => 'Powód odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_POWOLANIE_SKARBNIK_PARTII => [
                'title' => 'Dokument powołania Skarbnika Partii',
                'description' => 'Dokument powołujący Skarbnika Partii przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania na Skarbnika Partii',
                        'required' => true,
                        'query_filter' => 'active_members_all',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII => [
                'title' => 'Dokument odwołania Skarbnika Partii',
                'description' => 'Dokument odwołujący Skarbnika Partii przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Skarbnik Partii do odwołania',
                        'required' => true,
                        'query_filter' => 'skarbnicy_partii',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'powod' => [
                        'type' => 'textarea',
                        'label' => 'Powód odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_POWOLANIE_WICEPREZES_PARTII => [
                'title' => 'Dokument powołania Wiceprezesa Partii',
                'description' => 'Dokument powołujący Wiceprezesa Partii przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania na Wiceprezesa Partii',
                        'required' => true,
                        'query_filter' => 'active_members_all',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII => [
                'title' => 'Dokument odwołania Wiceprezesa Partii',
                'description' => 'Dokument odwołujący Wiceprezesa Partii przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Wiceprezes Partii do odwołania',
                        'required' => true,
                        'query_filter' => 'wiceprezesi_partii',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'powod' => [
                        'type' => 'textarea',
                        'label' => 'Powód odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_ODWOLANIE_PREZES_OKREGU => [
                'title' => 'Dokument odwołania Prezesa Okręgu',
                'description' => 'Dokument odwołujący Prezesa Okręgu przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Prezes Okręgu do odwołania',
                        'required' => true,
                        'query_filter' => 'prezesi_okregu',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU => [
                'title' => 'Dokument powołania p.o. Prezesa Okręgu',
                'description' => 'Dokument powołujący Pełniącego Obowiązki Prezesa Okręgu przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Osoba do powołania na p.o. Prezesa Okręgu',
                        'required' => true,
                        'query_filter' => 'active_members_all',
                    ],
                    'okreg' => [
                        'type' => 'entity',
                        'entity' => 'Okreg',
                        'label' => 'Okręg',
                        'required' => true,
                        'query_filter' => 'all',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU => [
                'title' => 'Dokument odwołania p.o. Prezesa Okręgu',
                'description' => 'Dokument odwołujący Pełniącego Obowiązki Prezesa Okręgu przez Prezesa Partii',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'p.o. Prezesa Okręgu do odwołania',
                        'required' => true,
                        'query_filter' => 'po_prezesi_okregu',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Partii
                ],
            ],
            Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU => [
                'title' => 'Dokument powołania Sekretarza Okręgu',
                'description' => 'Dokument powołujący Sekretarza Okręgu przez Prezesa Okręgu',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Osoba do powołania na Sekretarza Okręgu',
                        'required' => true,
                        'query_filter' => 'district_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Okręgu
                ],
            ],
            Dokument::TYP_ODWOLANIE_SEKRETARZ_OKREGU => [
                'title' => 'Dokument odwołania Sekretarza Okręgu',
                'description' => 'Dokument odwołujący Sekretarza Okręgu przez Prezesa Okręgu',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Sekretarz Okręgu do odwołania',
                        'required' => true,
                        'query_filter' => 'sekretarze_okregu',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Okręgu
                ],
            ],
            Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU => [
                'title' => 'Dokument powołania Skarbnika Okręgu',
                'description' => 'Dokument powołujący Skarbnika Okręgu przez Prezesa Okręgu',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Osoba do powołania na Skarbnika Okręgu',
                        'required' => true,
                        'query_filter' => 'district_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Okręgu
                ],
            ],
            Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU => [
                'title' => 'Dokument odwołania Skarbnika Okręgu',
                'description' => 'Dokument odwołujący Skarbnika Okręgu przez Prezesa Okręgu',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Skarbnik Okręgu do odwołania',
                        'required' => true,
                        'query_filter' => 'skarbnicy_okregu',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko podpis Prezesa Okręgu
                ],
            ],
            Dokument::TYP_UTWORZENIE_ODDZIALU => [
                'title' => 'Dokument utworzenia oddziału',
                'description' => 'Dokument tworzący nowy oddział w okręgu przez Zarząd Okręgu',
                'fields' => [
                    'nazwa_oddzialu' => [
                        'type' => 'text',
                        'label' => 'Nazwa Oddziału',
                        'required' => true,
                        'maxlength' => 100,
                    ],
                    'czlonkowie_oddzialu' => [
                        'type' => 'choice_multiple_enhanced',
                        'entity' => 'User',
                        'label' => 'Członkowie Oddziału (minimum 2)',
                        'required' => true,
                        'query_filter' => 'district_members_available',
                        'attr' => [
                            'class' => 'enhanced-multi-select',
                            'data-min-selection' => '2',
                        ],
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'drugi_podpisujacy' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Drugi podpisujący (członek zarządu okręgu)',
                        'required' => true,
                        'query_filter' => 'district_board_members',
                        'help' => 'Wybierz Wiceprezesa, Sekretarza lub Skarbnika Okręgu, który podpisze dokument razem z Prezesem Okręgu',
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie utworzenia oddziału',
                        'required' => false,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Prezes Okręgu
                    'district_board_member' => true,  // Jeden członek zarządu okręgu
                ],
            ],
            Dokument::TYP_WYZNACZENIE_OBSERWATORA => [
                'title' => 'Dokument wyznaczenia obserwatora zebrania',
                'description' => 'Dokument wyznaczający obserwatora zebrania członków oddziału',
                'fields' => [
                    'obserwator' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Obserwator zebrania',
                        'required' => true,
                        'query_filter' => 'okreg_members',
                    ],
                    'oddzial' => [
                        'type' => 'entity',
                        'entity' => 'Oddzial',
                        'label' => 'Oddział',
                        'required' => true,
                        'query_filter' => 'okreg_oddzialy',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 3,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Tylko Sekretarz Okręgu
                ],
            ],
            Dokument::TYP_WYZNACZENIE_PROTOKOLANTA => [
                'title' => 'Dokument wyznaczenia protokolanta zebrania',
                'description' => 'Dokument wyznaczający protokolanta zebrania członków oddziału',
                'fields' => [
                    'protokolant' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Protokolant zebrania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 3,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Obserwator
                ],
            ],
            Dokument::TYP_WYZNACZENIE_PROWADZACEGO => [
                'title' => 'Dokument wyznaczenia prowadzącego zebranie',
                'description' => 'Dokument wyznaczający prowadzącego zebranie członków oddziału',
                'fields' => [
                    'prowadzacy' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Prowadzący zebranie',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie',
                        'required' => false,
                        'rows' => 3,
                    ],
                ],
                'signers' => [
                    'creator' => true,  // Obserwator
                ],
            ],

            // District position appointments/dismissals
            Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU => [
                'title' => 'Dokument powołania Przewodniczącego Oddziału',
                'description' => 'Dokument powołujący Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie powołania',
                        'required' => false,
                        'rows' => 3,
                    ],
                ],
                'signers' => [
                    'meeting_protokolant' => true,  // Protokolant zebrania
                    'meeting_prowadzacy' => true,    // Prowadzący zebranie
                ],
            ],

            Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU => [
                'title' => 'Dokument odwołania Przewodniczącego Oddziału',
                'description' => 'Dokument odwołujący Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do odwołania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'meeting_protokolant' => true,  // Protokolant zebrania
                    'meeting_prowadzacy' => true,    // Prowadzący zebranie
                ],
            ],

            Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => [
                'title' => 'Dokument powołania Zastępcy Przewodniczącego Oddziału',
                'description' => 'Dokument powołujący Zastępcę Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie powołania',
                        'required' => false,
                        'rows' => 3,
                    ],
                ],
                'signers' => [
                    'meeting_protokolant' => true,  // Protokolant zebrania
                    'meeting_prowadzacy' => true,    // Prowadzący zebranie
                ],
            ],

            Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => [
                'title' => 'Dokument odwołania Zastępcy Przewodniczącego Oddziału',
                'description' => 'Dokument odwołujący Zastępcę Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do odwołania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'meeting_protokolant' => true,  // Protokolant zebrania
                    'meeting_prowadzacy' => true,    // Prowadzący zebranie
                ],
            ],

            Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU => [
                'title' => 'Dokument powołania Sekretarza Oddziału',
                'description' => 'Dokument powołujący Sekretarza Oddziału przez Zebranie Członków Oddziału',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do powołania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie powołania',
                        'required' => false,
                        'rows' => 3,
                    ],
                ],
                'signers' => [
                    'meeting_protokolant' => true,  // Protokolant zebrania
                    'meeting_prowadzacy' => true,    // Prowadzący zebranie
                ],
            ],

            Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU => [
                'title' => 'Dokument odwołania Sekretarza Oddziału',
                'description' => 'Dokument odwołujący Sekretarza Oddziału przez Zebranie Członków Oddziału',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Członek do odwołania',
                        'required' => true,
                        'query_filter' => 'oddzial_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'uzasadnienie' => [
                        'type' => 'textarea',
                        'label' => 'Uzasadnienie odwołania',
                        'required' => true,
                        'rows' => 4,
                    ],
                ],
                'signers' => [
                    'meeting_protokolant' => true,  // Protokolant zebrania
                    'meeting_prowadzacy' => true,    // Prowadzący zebranie
                ],
            ],
            
            Dokument::TYP_WYBOR_PREZESA_OKREGU_WALNE => [
                'title' => 'Dokument wyboru Prezesa Okręgu przez Walne Zgromadzenie',
                'description' => 'Dokument wybierający Prezesa Okręgu przez Walne Zgromadzenie Członków Okręgu',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Wybrany Prezes Okręgu',
                        'required' => true,
                        'query_filter' => 'okreg_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'okreg' => [
                        'type' => 'text',
                        'label' => 'Nazwa Okręgu',
                        'required' => true,
                    ],
                ],
                'signers' => [
                    'obserwator' => true,
                    'protokolant' => true,
                    'prowadzacy' => true,
                ],
            ],
            
            Dokument::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE => [
                'title' => 'Dokument wyboru Wiceprezesa Okręgu przez Walne Zgromadzenie',
                'description' => 'Dokument wybierający Wiceprezesa Okręgu przez Walne Zgromadzenie Członków Okręgu',
                'fields' => [
                    'czlonek' => [
                        'type' => 'entity',
                        'entity' => 'User',
                        'label' => 'Wybrany Wiceprezes Okręgu',
                        'required' => true,
                        'query_filter' => 'okreg_members',
                    ],
                    'data_wejscia_w_zycie' => [
                        'type' => 'date',
                        'label' => 'Data wejścia w życie',
                        'required' => true,
                    ],
                    'okreg' => [
                        'type' => 'text',
                        'label' => 'Nazwa Okręgu',
                        'required' => true,
                    ],
                ],
                'signers' => [
                    'obserwator' => true,
                    'protokolant' => true,
                    'prowadzacy' => true,
                ],
            ],

            default => null,
        };
    }

    /**
     * Tworzy formularz dla danego typu dokumentu.
     */
    public function createDocumentForm(string $type, User $user): \Symfony\Component\Form\FormInterface
    {
        $definition = $this->getDocumentDefinition($type);
        if (!$definition) {
            throw new \InvalidArgumentException("Nieznany typ dokumentu: $type");
        }

        $formBuilder = $this->formFactory->createBuilder(FormType::class);

        foreach ($definition['fields'] as $fieldName => $fieldConfig) {
            $this->addFormField($formBuilder, $fieldName, $fieldConfig, $user);
        }

        return $formBuilder->getForm();
    }

    /**
     * Dodaje pole do formularza na podstawie konfiguracji.
     *
     * @param \Symfony\Component\Form\FormBuilderInterface<mixed> $formBuilder
     * @param array<string, mixed>                                $fieldConfig
     */
    private function addFormField(\Symfony\Component\Form\FormBuilderInterface $formBuilder, string $fieldName, array $fieldConfig, User $user): void
    {
        $options = [
            'label' => $fieldConfig['label'],
            'required' => $fieldConfig['required'] ?? false,
            'attr' => [],
        ];

        if (isset($fieldConfig['help'])) {
            $options['help'] = $fieldConfig['help'];
        }

        switch ($fieldConfig['type']) {
            case 'text':
                $formBuilder->add($fieldName, TextType::class, $options);
                break;

            case 'textarea':
                $options['attr'] = ['rows' => $fieldConfig['rows'] ?? 4];
                $formBuilder->add($fieldName, TextareaType::class, $options);
                break;

            case 'date':
                $options['widget'] = 'single_text';
                $formBuilder->add($fieldName, DateType::class, $options);
                break;

            case 'choice':
                $options['choices'] = $fieldConfig['choices'];
                if (isset($fieldConfig['expanded'])) {
                    $options['expanded'] = $fieldConfig['expanded'];
                }
                if (isset($fieldConfig['multiple'])) {
                    $options['multiple'] = $fieldConfig['multiple'];
                }
                $formBuilder->add($fieldName, ChoiceType::class, $options);
                break;

            case 'entity':
                $options['class'] = 'App\\Entity\\'.$fieldConfig['entity'];
                $options['choice_label'] = $this->getEntityChoiceLabel($fieldConfig['entity']);

                if (isset($fieldConfig['query_filter'])) {
                    $options['choices'] = $this->getFilteredEntityChoices(
                        $fieldConfig['entity'],
                        $fieldConfig['query_filter'],
                        $user
                    );
                }

                $formBuilder->add($fieldName, EntityType::class, $options);
                break;

            case 'entity_multiple':
                $options['class'] = 'App\\Entity\\'.$fieldConfig['entity'];
                $options['choice_label'] = $this->getEntityChoiceLabel($fieldConfig['entity']);
                $options['multiple'] = true;
                $options['expanded'] = true; // Pokazuje jako checkboxy

                if (isset($fieldConfig['query_filter'])) {
                    $options['choices'] = $this->getFilteredEntityChoices(
                        $fieldConfig['entity'],
                        $fieldConfig['query_filter'],
                        $user
                    );
                }

                $formBuilder->add($fieldName, EntityType::class, $options);
                break;

            case 'choice_multiple_enhanced':
                // Pobierz dostępne opcje z filtra
                $choices = [];
                if (isset($fieldConfig['query_filter'])) {
                    $entities = $this->getFilteredEntityChoices(
                        $fieldConfig['entity'],
                        $fieldConfig['query_filter'],
                        $user
                    );

                    foreach ($entities as $entity) {
                        $choices[$entity->getFullName()] = $entity->getId();
                    }
                }

                $options['choices'] = $choices;
                $options['multiple'] = true;
                $options['expanded'] = false; // Nie jako checkboxy, ale jako wielokrotny select
                $options['attr'] = array_merge(
                    $options['attr'],
                    $fieldConfig['attr'] ?? [],
                    ['class' => 'form-control enhanced-multi-select']
                );

                $formBuilder->add($fieldName, ChoiceType::class, $options);
                break;

            default:
                $formBuilder->add($fieldName, TextType::class, $options);
        }
    }

    /**
     * Zwraca etykietę dla wyboru encji.
     */
    private function getEntityChoiceLabel(string $entityName): string
    {
        switch ($entityName) {
            case 'User':
                return 'fullName';
            case 'Okreg':
                return 'nazwa';
            case 'Oddzial':
                return 'nazwa';
            default:
                return 'id';
        }
    }

    /**
     * Zwraca przefiltrowane opcje encji na podstawie zapytania.
     *
     * @return array<string, mixed>
     */
    private function getFilteredEntityChoices(string $entityName, string $queryFilter, User $user): array
    {
        switch ($entityName) {
            case 'User':
                return $this->getUserChoices($queryFilter, $user);
            case 'Okreg':
                return $this->getOkregChoices($queryFilter, $user);
            case 'Oddzial':
                return $this->getOddzialChoices($queryFilter, $user);
            default:
                return [];
        }
    }

    /**
     * Zwraca opcje użytkowników na podstawie filtru.
     *
     * @return array<string, mixed>
     */
    private function getUserChoices(string $filter, User $currentUser): array
    {
        switch ($filter) {
            case 'board_members':
                // Członkowie zarządu okręgu (bez current user) - używamy native SQL dla PostgreSQL JSON
                $connection = $this->entityManager->getConnection();
                $sql = 'SELECT * FROM "user" u 
                        WHERE u.okreg_id = :okreg 
                        AND u.id != :currentUserId 
                        AND u.status = :status 
                        AND (
                            u.roles::jsonb @> :sekretarz OR 
                            u.roles::jsonb @> :skarbnik OR 
                            u.roles::jsonb @> :wiceprezes
                        )
                        ORDER BY u.nazwisko, u.imie';

                $stmt = $connection->prepare($sql);
                $result = $stmt->executeQuery([
                    'okreg' => $currentUser->getOkreg()?->getId(),
                    'currentUserId' => $currentUser->getId(),
                    'status' => 'aktywny',
                    'sekretarz' => '["ROLE_SEKRETARZ_OKREGU"]',
                    'skarbnik' => '["ROLE_SKARBNIK_OKREGU"]',
                    'wiceprezes' => '["ROLE_WICEPREZES_OKREGU"]',
                ]);

                $userIds = array_column($result->fetchAllAssociative(), 'id');
                if (empty($userIds)) {
                    return [];
                }

                $users = $this->userRepository->findBy(['id' => $userIds]);
                $choices = [];
                foreach ($users as $user) {
                    $choices[$user->getFullName()] = $user;
                }

                return $choices;

            case 'national_board_members':
                // Członkowie zarządu krajowego - używamy native SQL dla PostgreSQL JSON
                $connection = $this->entityManager->getConnection();
                $sql = 'SELECT * FROM "user" u 
                        WHERE u.status = :status 
                        AND u.id != :currentUserId 
                        AND (
                            u.roles::jsonb @> :prezes OR 
                            u.roles::jsonb @> :wiceprezes OR 
                            u.roles::jsonb @> :sekretarz OR 
                            u.roles::jsonb @> :skarbnik
                        )
                        ORDER BY u.nazwisko, u.imie';

                $stmt = $connection->prepare($sql);
                $result = $stmt->executeQuery([
                    'status' => 'aktywny',
                    'currentUserId' => $currentUser->getId(),
                    'prezes' => '["ROLE_PREZES_PARTII"]',
                    'wiceprezes' => '["ROLE_WICEPREZES_PARTII"]',
                    'sekretarz' => '["ROLE_SEKRETARZ_PARTII"]',
                    'skarbnik' => '["ROLE_SKARBNIK_PARTII"]',
                ]);

                $userIds = array_column($result->fetchAllAssociative(), 'id');
                if (empty($userIds)) {
                    return [];
                }

                $users = $this->userRepository->findBy(['id' => $userIds]);
                $choices = [];
                foreach ($users as $user) {
                    $choices[$user->getFullName()] = $user;
                }

                return $choices;

            default:
                // Dla wszystkich innych filtrów używamy standardowego DQL
                $queryBuilder = $this->userRepository->createQueryBuilder('u');

                switch ($filter) {
                    case 'candidates_ready':
                        // Kandydaci gotowi do przyjęcia (100% postęp)
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.okreg = :okreg')
                                   ->andWhere('u.status = :status')
                                   ->andWhere('u.dataWypelnienieFormularza IS NOT NULL')
                                   ->andWhere('u.dataWeryfikacjaDokumentow IS NOT NULL')
                                   ->andWhere('u.dataRozmowaPrekwalifikacyjna IS NOT NULL')
                                   ->andWhere('u.dataOpiniaRadyOddzialu IS NOT NULL')
                                   ->andWhere('u.dataDecyzjaZarzadu IS NOT NULL')
                                   ->andWhere('u.dataPrzyjecieUroczyste IS NOT NULL')
                                   ->setParameter('typ', 'kandydat')
                                   ->setParameter('okreg', $currentUser->getOkreg())
                                   ->setParameter('status', 'aktywny');
                        break;

                    case 'active_members':
                        // Aktywni członkowie z okręgu
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.okreg = :okreg')
                                   ->andWhere('u.status = :status')
                                   ->setParameter('typ', 'czlonek')
                                   ->setParameter('okreg', $currentUser->getOkreg())
                                   ->setParameter('status', 'aktywny');
                        break;

                    case 'eligible_members':
                        // Członkowie mogący pełnić funkcje
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.status = :status')
                                   ->setParameter('typ', 'czlonek')
                                   ->setParameter('status', 'aktywny');
                        break;

                    case 'district_members':
                        // Członkowie z tego samego okręgu
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.okreg = :okreg')
                                   ->andWhere('u.status = :status')
                                   ->setParameter('typ', 'czlonek')
                                   ->setParameter('okreg', $currentUser->getOkreg())
                                   ->setParameter('status', 'aktywny');
                        break;

                    case 'kandydaci':
                        // Kandydaci gotowi do przyjęcia
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.status = :status')
                                   ->setParameter('typ', 'kandydat')
                                   ->setParameter('status', 'aktywny');

                        // Jeśli user ma okręg, pokazuj tylko kandydatów z tego okręgu
                        if ($currentUser->getOkreg()) {
                            $queryBuilder->andWhere('u.okreg = :okreg')
                                       ->setParameter('okreg', $currentUser->getOkreg());
                        }
                        break;

                    case 'active_members_all':
                        // Wszyscy aktywni członkowie partii (dla powołania)
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.status = :status')
                                   ->setParameter('typ', 'czlonek')
                                   ->setParameter('status', 'aktywny');
                        break;

                    case 'pelnomocnicy_struktur':
                        // Członkowie z rolą Pełnomocnika ds. Struktur (dla odwołania)
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_PELNOMOCNIK_STRUKTUR"]',
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'sekretarze_partii':
                        // Członkowie z rolą Sekretarza Partii (dla odwołania)
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_SEKRETARZ_PARTII"]',
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'skarbnicy_partii':
                        // Członkowie z rolą Skarbnika Partii (dla odwołania)
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_SKARBNIK_PARTII"]',
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'wiceprezesi_partii':
                        // Członkowie z rolą Wiceprezesa Partii (dla odwołania)
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_WICEPREZES_PARTII"]',
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'kandydaci_krajowy':
                        // Kandydaci z całego kraju (dla zarządu krajowego)
                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.status = :status')
                                   ->setParameter('typ', 'kandydat')
                                   ->setParameter('status', 'aktywny');
                        break;

                    case 'prezesi_okregu':
                        // Członkowie z rolą Prezesa Okręgu (dla odwołania)
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_PREZES_OKREGU"]',
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'po_prezesi_okregu':
                        // Członkowie pełniący obowiązki Prezesa Okręgu (dla odwołania)
                        // Zakładam, że będzie specjalna rola ROLE_PO_PREZES_OKREGU
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_PO_PREZES_OKREGU"]',
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'sekretarze_okregu':
                        // Członkowie z rolą Sekretarza Okręgu z okręgu użytkownika (dla odwołania)
                        if (!$currentUser->getOkreg()) {
                            return [];
                        }

                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                AND u.okreg_id = :okreg_id
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_SEKRETARZ_OKREGU"]',
                            'okreg_id' => $currentUser->getOkreg()->getId(),
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'skarbnicy_okregu':
                        // Członkowie z rolą Skarbnika Okręgu z okręgu użytkownika (dla odwołania)
                        if (!$currentUser->getOkreg()) {
                            return [];
                        }

                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.roles::jsonb @> :role
                                AND u.okreg_id = :okreg_id
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'role' => '["ROLE_SKARBNIK_OKREGU"]',
                            'okreg_id' => $currentUser->getOkreg()->getId(),
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    case 'district_members_available':
                        // Członkowie z okręgu użytkownika, którzy nie są przypisani do żadnego oddziału
                        if (!$currentUser->getOkreg()) {
                            return [];
                        }

                        $queryBuilder->where('u.typUzytkownika = :typ')
                                   ->andWhere('u.status = :status')
                                   ->andWhere('u.okreg = :okreg')
                                   ->andWhere('u.oddzial IS NULL')  // Nie przypisani do oddziału
                                   ->setParameter('typ', 'czlonek')
                                   ->setParameter('status', 'aktywny')
                                   ->setParameter('okreg', $currentUser->getOkreg());
                        break;

                    case 'okreg_members':
                        // Wszyscy członkowie z okręgu użytkownika
                        if (!$currentUser->getOkreg()) {
                            return [];
                        }

                        $queryBuilder->where('u.status = :status')
                                   ->andWhere('u.okreg = :okreg')
                                   ->setParameter('status', 'aktywny')
                                   ->setParameter('okreg', $currentUser->getOkreg());
                        break;

                    case 'okreg_oddzialy':
                        // Wszystkie oddziały z okręgu użytkownika
                        if (!$currentUser->getOkreg()) {
                            return [];
                        }

                        $oddzialRepository = $this->entityManager->getRepository(Oddzial::class);
                        $oddzialy = $oddzialRepository->findBy(['okreg' => $currentUser->getOkreg()]);
                        $choices = [];
                        foreach ($oddzialy as $oddzial) {
                            $choices[$oddzial->getNazwa()] = $oddzial;
                        }

                        return $choices;

                    case 'oddzial_members':
                        // Członkowie konkretnego oddziału (do użycia w kontekście zebrania)
                        // Tu będzie trudniej bo potrzebujemy kontekstu oddziału
                        // Na razie zwróć pustą tablicę - będzie obsługiwane osobno w kontrolerze
                        return [];

                    case 'district_board_members':
                        // Członkowie zarządu okręgu (Wiceprezes, Sekretarz i Skarbnik Okręgu) z okręgu użytkownika
                        if (!$currentUser->getOkreg()) {
                            return [];
                        }

                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM "user" u 
                                WHERE u.status = :status 
                                AND u.okreg_id = :okreg_id
                                AND (u.roles::jsonb @> :wiceprezes_role OR u.roles::jsonb @> :sekretarz_role OR u.roles::jsonb @> :skarbnik_role)
                                AND u.id != :current_user_id
                                ORDER BY u.nazwisko, u.imie';

                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeQuery([
                            'status' => 'aktywny',
                            'okreg_id' => $currentUser->getOkreg()->getId(),
                            'wiceprezes_role' => '["ROLE_WICEPREZES_OKREGU"]',
                            'sekretarz_role' => '["ROLE_SEKRETARZ_OKREGU"]',
                            'skarbnik_role' => '["ROLE_SKARBNIK_OKREGU"]',
                            'current_user_id' => $currentUser->getId(),  // Wyklucz obecnego użytkownika
                        ]);

                        $userIds = array_column($result->fetchAllAssociative(), 'id');
                        if (empty($userIds)) {
                            return [];
                        }

                        $users = $this->userRepository->findBy(['id' => $userIds]);
                        $choices = [];
                        foreach ($users as $user) {
                            $choices[$user->getFullName()] = $user;
                        }

                        return $choices;

                    default:
                        return [];
                }

                $queryBuilder->orderBy('u.nazwisko, u.imie');

                return $queryBuilder->getQuery()->getResult();
        }
    }

    /**
     * Zwraca opcje okręgów na podstawie filtru.
     */
    /**
     * @return array<string, mixed>
     */
    private function getOkregChoices(string $filter, User $currentUser): array
    {
        $repository = $this->entityManager->getRepository(Okreg::class);

        switch ($filter) {
            case 'all':
                $okregi = $repository->findAll();
                $choices = [];
                foreach ($okregi as $okreg) {
                    $choices[$okreg->getNazwa()] = $okreg;
                }

                return $choices;
            case 'current_user':
                $okreg = $currentUser->getOkreg();

                return $okreg ? [$okreg->getNazwa() => $okreg] : [];
            default:
                return [];
        }
    }

    /**
     * Zwraca opcje oddziałów na podstawie filtru.
     */
    /**
     * @return array<string, mixed>
     */
    private function getOddzialChoices(string $filter, User $currentUser): array
    {
        switch ($filter) {
            case 'okreg_oddzialy':
                // Oddziały z okręgu użytkownika
                if (!$currentUser->getOkreg()) {
                    return [];
                }

                $oddzialy = $this->entityManager->getRepository(Oddzial::class)
                    ->findBy(['okreg' => $currentUser->getOkreg()]);
                $choices = [];
                foreach ($oddzialy as $oddzial) {
                    $choices[$oddzial->getNazwa()] = $oddzial;
                }

                return $choices;

            case 'all':
                // Wszystkie oddziały (dla adminów)
                $oddzialy = $this->entityManager->getRepository(Oddzial::class)
                    ->findAll();
                $choices = [];
                foreach ($oddzialy as $oddzial) {
                    $choices[$oddzial->getNazwa()] = $oddzial;
                }

                return $choices;

            default:
                return [];
        }
    }

    /**
     * Tworzy dokument na podstawie typu i danych z formularza.
     *
     * @param array<string, mixed> $data
     */
    public function createDocument(string $type, array $data, User $creator): Dokument
    {
        $definition = $this->getDocumentDefinition($type);
        if (!$definition) {
            throw new \InvalidArgumentException("Nieznany typ dokumentu: $type");
        }

        // Sprawdź uprawnienia
        if (!$this->canCreateDocumentType($creator, $type)) {
            throw new AccessDeniedException('Brak uprawnień do utworzenia tego typu dokumentu');
        }

        $dokument = new Dokument();
        $dokument->setTyp($type);
        $dokument->setTworca($creator);
        $dokument->setOkreg($creator->getOkreg());
        
        // Generuj numer dokumentu po ustawieniu typu
        $dokument->generateNumerDokumentu();

        // Ustaw dane z formularza
        $this->mapFormDataToDocument($dokument, $data, $definition);

        // Generuj unikalny numer dokumentu
        $numerDokumentu = $this->dokumentRepository->generateNextDocumentNumber(
            $type,
            $creator->getOkreg()
        );
        $dokument->setNumerDokumentu($numerDokumentu);

        // Ustaw tytuł i treść na podstawie szablonu
        $this->generateDocumentContent($dokument, $definition, $data);

        // Dodaj podpisujących na podstawie definicji
        $this->addSignersToDocument($dokument, $definition, $data, $creator);

        // Generuj hash dokumentu
        $dokument->setHashDokumentu($dokument->generateHash());

        $this->entityManager->persist($dokument);
        $this->entityManager->flush();

        return $dokument;
    }

    /**
     * Mapuje dane z formularza na dokument.
     */
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $definition
     */
    private function mapFormDataToDocument(Dokument $dokument, array $data, array $definition): void
    {
        foreach ($definition['fields'] as $fieldName => $fieldConfig) {
            if (!isset($data[$fieldName])) {
                continue;
            }

            $value = $data[$fieldName];

            switch ($fieldName) {
                case 'kandydat':
                case 'powolywan_czlonek':
                case 'odwolywan_czlonek':
                    $dokument->setKandydat($value);
                    break;
                case 'czlonek':
                case 'obserwator':
                case 'protokolant':
                case 'prowadzacy':
                    $dokument->setCzlonek($value);
                    break;
                case 'data_wejscia_w_zycie':
                case 'data_powolania':
                case 'data_odwolania':
                case 'data_rezygnacji':
                case 'data_zawieszenia':
                case 'data_wykluczenia':
                    $dokument->setDataWejsciaWZycie($value);
                    break;
                case 'okreg':
                    $dokument->setOkreg($value);
                    break;
            }
        }

        // Zapisz wszystkie dane w polu daneDodatkowe dla późniejszego użycia
        $dokument->setDaneDodatkowe($data);
    }

    /**
     * Generuje tytuł i treść dokumentu na podstawie szablonu.
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $data
     */
    private function generateDocumentContent(Dokument $dokument, array $definition, array $data): void
    {
        $title = $this->generateDocumentTitle($definition, $data);
        
        // Użyj nowego systemu klas dokumentów jeśli typ jest obsługiwany
        if (DocumentFactory::isSupported($dokument->getTyp())) {
            try {
                $content = DocumentFactory::generateContent($dokument->getTyp(), $dokument, $data);
            } catch (\Exception $e) {
                $this->logger->error('Błąd generowania dokumentu przez DocumentFactory', [
                    'type' => $dokument->getTyp(),
                    'error' => $e->getMessage()
                ]);
                // Fallback do starej metody
                $content = $this->generateDocumentContentText($definition, $data);
            }
        } else {
            // Użyj szablonu z DocumentTemplates jeśli istnieje
            $template = DocumentTemplates::getTemplate($dokument->getTyp());
            if ($template) {
                // Przygotuj dane do wypełnienia szablonu
                $templateData = $this->prepareTemplateData($dokument, $data);
                $content = DocumentTemplates::fillTemplate($template, $templateData);
            } else {
                // Fallback do starej metody generowania
                $content = $this->generateDocumentContentText($definition, $data);
            }
        }

        $dokument->setTytul($title);
        $dokument->setTresc($content);
    }

    /**
     * Generuje tytuł dokumentu.
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $data
     */
    private function generateDocumentTitle(array $definition, array $data): string
    {
        $baseTitle = $definition['title'];

        // Dodaj specyficzne informacje do tytułu na podstawie typu
        if (isset($data['kandydat'])) {
            return $baseTitle.' - '.$data['kandydat']->getFullName();
        }

        if (isset($data['powolywan_czlonek'])) {
            return $baseTitle.' - '.$data['powolywan_czlonek']->getFullName();
        }

        if (isset($data['odwolywan_czlonek'])) {
            return $baseTitle.' - '.$data['odwolywan_czlonek']->getFullName();
        }

        if (isset($data['czlonek'])) {
            return $baseTitle.' - '.$data['czlonek']->getFullName();
        }

        return $baseTitle;
    }

    /**
     * Przygotowuje dane do wypełnienia szablonu.
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function prepareTemplateData(Dokument $dokument, array $data): array
    {
        $templateData = [];
        
        // Podstawowe dane dokumentu
        $templateData['numer_dokumentu'] = $dokument->getNumerDokumentu();
        $templateData['data'] = date('d.m.Y');
        $templateData['data_wejscia'] = $dokument->getDataWejsciaWZycie()->format('d.m.Y');
        
        // Dane okręgu
        if ($dokument->getOkreg()) {
            $templateData['okreg'] = $dokument->getOkreg()->getNazwa();
        }
        
        // Dane kandydata/członka
        if (isset($data['kandydat'])) {
            $kandydat = $data['kandydat'];
            $templateData['imie_nazwisko'] = $kandydat->getFullName();
            $templateData['pesel'] = $kandydat->getPesel() ?? 'BRAK';
            $templateData['adres'] = $kandydat->getFullAddress() ?? 'BRAK ADRESU';
        } elseif (isset($data['czlonek'])) {
            $czlonek = $data['czlonek'];
            $templateData['imie_nazwisko'] = $czlonek->getFullName();
            $templateData['pesel'] = $czlonek->getPesel() ?? 'BRAK';
            $templateData['adres'] = $czlonek->getFullAddress() ?? 'BRAK ADRESU';
        } elseif (isset($data['powolywan_czlonek'])) {
            $czlonek = $data['powolywan_czlonek'];
            $templateData['imie_nazwisko'] = $czlonek->getFullName();
            $templateData['pesel'] = $czlonek->getPesel() ?? 'BRAK';
            $templateData['adres'] = $czlonek->getFullAddress() ?? 'BRAK ADRESU';
        } elseif (isset($data['odwolywan_czlonek'])) {
            $czlonek = $data['odwolywan_czlonek'];
            $templateData['imie_nazwisko'] = $czlonek->getFullName();
            $templateData['pesel'] = $czlonek->getPesel() ?? 'BRAK';
            $templateData['adres'] = $czlonek->getFullAddress() ?? 'BRAK ADRESU';
        }
        
        // Dane podpisujących
        if ($dokument->getTworca()) {
            $tworca = $dokument->getTworca();
            
            // Określ rolę twórcy dla poprawnego podpisu
            if (in_array('ROLE_PELNOMOCNIK_PRZYJMOWANIA', $tworca->getRoles())) {
                $templateData['podpisujacy'] = $tworca->getFullName();
            } elseif (in_array('ROLE_PREZES_PARTII', $tworca->getRoles())) {
                $templateData['prezes_partii'] = $tworca->getFullName();
            } elseif (in_array('ROLE_PREZES_OKREGU', $tworca->getRoles())) {
                $templateData['prezes_okregu'] = $tworca->getFullName();
            } elseif (in_array('ROLE_SEKRETARZ_PARTII', $tworca->getRoles())) {
                $templateData['sekretarz_partii'] = $tworca->getFullName();
            } elseif (in_array('ROLE_SEKRETARZ_OKREGU', $tworca->getRoles())) {
                $templateData['sekretarz_okregu'] = $tworca->getFullName();
            }
        }
        
        // Drugi podpisujący
        if (isset($data['drugi_podpisujacy'])) {
            $drugi = $data['drugi_podpisujacy'];
            
            // Określ rolę drugiego podpisującego
            if (in_array('ROLE_SEKRETARZ_PARTII', $drugi->getRoles())) {
                $templateData['sekretarz_partii'] = $drugi->getFullName();
            } elseif (in_array('ROLE_SEKRETARZ_OKREGU', $drugi->getRoles())) {
                $templateData['sekretarz_okregu'] = $drugi->getFullName();
            } else {
                $templateData['czlonek_zarzadu'] = $drugi->getFullName();
            }
        }
        
        // Dane dodatkowe z formularzaTy
        if ($daneDodatkowe = $dokument->getDaneDodatkowe()) {
            // Dane oddziału
            if (isset($daneDodatkowe['nazwa_oddzialu'])) {
                $templateData['oddzial'] = $daneDodatkowe['nazwa_oddzialu'];
            }
            if (isset($daneDodatkowe['gminy'])) {
                $templateData['gminy'] = implode(', ', $daneDodatkowe['gminy']);
            }
            
            // Dane zebrania
            if (isset($daneDodatkowe['data_zebrania'])) {
                $templateData['data_zebrania'] = $daneDodatkowe['data_zebrania'];
            }
            if (isset($daneDodatkowe['prowadzacy'])) {
                $templateData['prowadzacy'] = $daneDodatkowe['prowadzacy'];
            }
            if (isset($daneDodatkowe['protokolant'])) {
                $templateData['protokolant'] = $daneDodatkowe['protokolant'];
            }
        }
        
        // Dane z zebrania
        if ($zebranie = $dokument->getZebranieOddzialu()) {
            if ($zebranie->getProtokolant()) {
                $templateData['protokolant'] = $zebranie->getProtokolant()->getFullName();
            }
            if ($zebranie->getProwadzacy()) {
                $templateData['prowadzacy'] = $zebranie->getProwadzacy()->getFullName();
            }
        }
        
        // Dane z zebrania okręgu
        if ($zebranieOkregu = $dokument->getZebranieOkregu()) {
            $templateData['przewodniczacy_walnego'] = 'Przewodniczący Walnego Zgromadzenia';
            $templateData['sekretarz_walnego'] = 'Sekretarz Walnego Zgromadzenia';
        }
        
        return $templateData;
    }

    /**
     * Generuje treść dokumentu (stara metoda - fallback).
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $data
     */
    private function generateDocumentContentText(array $definition, array $data): string
    {
        // Określ typ dokumentu
        if (str_contains($definition['title'], 'powołania') || str_contains($definition['title'], 'odwołania')) {
            $content = "DECYZJA\n";
        } else {
            $content = "UCHWAŁA\n";
        }

        // Określ organ wydający na podstawie typu dokumentu
        if (str_contains($definition['title'], 'Okręgowego Pełnomocnika ds. przyjmowania')) {
            $content .= "OKRĘGOWEGO PEŁNOMOCNIKA DS. PRZYJMOWANIA NOWYCH CZŁONKÓW\n";
            $content .= "PARTII POLITYCZNEJ NOWA NADZIEJA\n";
        } elseif (str_contains($definition['title'], 'zarząd okręgu')) {
            $content .= "ZARZĄDU OKRĘGU PARTII POLITYCZNEJ NOWA NADZIEJA\n";
        } elseif (str_contains($definition['title'], 'zarząd krajowy')) {
            $content .= "ZARZĄDU KRAJOWEGO PARTII POLITYCZNEJ NOWA NADZIEJA\n";
        } elseif (str_contains($definition['title'], 'Prezesa Partii')) {
            $content .= "PREZESA PARTII POLITYCZNEJ NOWA NADZIEJA\n";
        } else {
            $content .= "ZARZĄDU OKRĘGU PARTII POLITYCZNEJ NOWA NADZIEJA\n";
        }

        $content .= 'z dnia '.date('d.m.Y')."\n";
        $content .= 'w sprawie '.strtolower($definition['description'])."\n\n";

        $content .= "§ 1\n";

        // Dodaj szczegóły na podstawie typu dokumentu i danych
        if (isset($data['kandydat'])) {
            $content .= 'Przyjmuje się do partii kandydata: '.$data['kandydat']->getFullName();
            if ($data['kandydat']->getEmail()) {
                $content .= ' (email: '.$data['kandydat']->getEmail().')';
            }
            $content .= ".\n\n";
        } elseif (str_contains($definition['title'], 'powołania')) {
            if (isset($data['czlonek'])) {
                $content .= 'Powołuje się członka partii: '.$data['czlonek']->getFullName();
                if (str_contains($definition['title'], 'Sekretarza Partii')) {
                    $content .= " na stanowisko Sekretarza Partii.\n\n";
                } else {
                    $content .= " na stanowisko Pełnomocnika ds. Struktur.\n\n";
                }
            }
        } elseif (str_contains($definition['title'], 'odwołania')) {
            if (isset($data['czlonek'])) {
                $content .= 'Odwołuje się członka partii: '.$data['czlonek']->getFullName();
                if (str_contains($definition['title'], 'Sekretarza Partii')) {
                    $content .= " ze stanowiska Sekretarza Partii.\n\n";
                } else {
                    $content .= " ze stanowiska Pełnomocnika ds. Struktur.\n\n";
                }
            }
        }

        if (isset($data['uzasadnienie']) && !empty($data['uzasadnienie'])) {
            $content .= 'Uzasadnienie: '.$data['uzasadnienie']."\n\n";
        }

        if (isset($data['powod']) && !empty($data['powod'])) {
            $content .= 'Powód odwołania: '.$data['powod']."\n\n";
        }

        $content .= "§ 2\n";
        if (isset($data['data_wejscia_w_zycie'])) {
            $dataWejscia = $data['data_wejscia_w_zycie'];
            if ($dataWejscia instanceof \DateTime) {
                $content .= 'Uchwała wchodzi w życie z dniem '.$dataWejscia->format('d.m.Y').".\n\n";
            } else {
                $content .= "Uchwała wchodzi w życie z dniem podpisania.\n\n";
            }
        } else {
            $content .= "Uchwała wchodzi w życie z dniem podpisania.\n\n";
        }

        return $content;
    }

    /**
     * Dodaje podpisujących do dokumentu po ustawieniu relacji (publiczna metoda).
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $data
     */
    public function addSignersAfterRelation(Dokument $dokument, array $definition, array $data, User $creator): void
    {
        $this->addSignersToDocument($dokument, $definition, $data, $creator);
    }

    /**
     * Dodaje podpisujących do dokumentu na podstawie definicji.
     *
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $data
     */
    private function addSignersToDocument(Dokument $dokument, array $definition, array $data, User $creator): void
    {
        $signers = $definition['signers'] ?? [];
        $kolejnosc = 1; // Licznik kolejności podpisów

        foreach ($signers as $signerType => $signerValue) {
            switch ($signerType) {
                case 'creator':
                    if (true === $signerValue) {
                        $this->addSigner($dokument, $creator, $kolejnosc++);
                    }
                    break;

                case 'drugi_podpisujacy':
                    if (isset($data['drugi_podpisujacy']) && $data['drugi_podpisujacy'] instanceof User) {
                        $this->addSigner($dokument, $data['drugi_podpisujacy'], $kolejnosc++);
                    }
                    break;

                case 'second_signer':
                    if ('auto' === $signerValue) {
                        // Automatycznie wybierz drugiego członka zarządu z tego samego okręgu
                        $boardMembers = $this->getUserChoices('board_members', $creator);
                        if (!empty($boardMembers)) {
                            // Wybierz pierwszego dostępnego członka zarządu
                            $secondSigner = reset($boardMembers);
                            $this->addSigner($dokument, $secondSigner, $kolejnosc++);
                        }
                    } elseif (isset($data['drugi_podpisujacy']) && $data['drugi_podpisujacy'] instanceof User) {
                        $this->addSigner($dokument, $data['drugi_podpisujacy'], $kolejnosc++);
                    }
                    break;

                case 'board_members':
                    // Dodaj określoną liczbę członków zarządu
                    // Na razie implementacja podstawowa
                    break;

                case 'district_board_member':
                    // Dla dokumentu utworzenia oddziału - dodaj drugi podpis członka zarządu okręgu
                    if (true === $signerValue && isset($data['drugi_podpisujacy']) && $data['drugi_podpisujacy'] instanceof User) {
                        $this->addSigner($dokument, $data['drugi_podpisujacy'], $kolejnosc++);
                    }
                    break;

                case 'meeting_protokolant':
                    // Protokolant zebrania - pobiera się z relacji ZebranieOddzialu
                    if (true === $signerValue && $dokument->getZebranieOddzialu()) {
                        $protokolant = $dokument->getZebranieOddzialu()->getProtokolant();
                        if ($protokolant) {
                            $this->addSigner($dokument, $protokolant, $kolejnosc++);
                        }
                    }
                    break;

                case 'meeting_prowadzacy':
                    // Prowadzący zebranie - pobiera się z relacji ZebranieOddzialu
                    if (true === $signerValue && $dokument->getZebranieOddzialu()) {
                        $prowadzacy = $dokument->getZebranieOddzialu()->getProwadzacy();
                        if ($prowadzacy) {
                            $this->addSigner($dokument, $prowadzacy, $kolejnosc++);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Dodaje podpisującego do dokumentu.
     */
    private function addSigner(Dokument $dokument, User $signer, int $kolejnosc): void
    {
        $podpis = new PodpisDokumentu();
        $podpis->setDokument($dokument);
        $podpis->setPodpisujacy($signer);
        $podpis->setStatus(PodpisDokumentu::STATUS_OCZEKUJE);
        $podpis->setKolejnosc($kolejnosc);
        // dataUtworzenia jest ustawiana automatycznie w konstruktorze

        $dokument->addPodpis($podpis);
        $this->entityManager->persist($podpis);
    }

    /**
     * Podpisuje dokument.
     */
    public function signDocument(Dokument $dokument, User $user, ?string $komentarz = null, ?string $podpisElektroniczny = null): void
    {
        $podpis = $dokument->getUserSignature($user);
        if (!$podpis) {
            throw new AccessDeniedException('Nie możesz podpisać tego dokumentu');
        }

        if ($podpis->isSigned()) {
            throw new \RuntimeException('Dokument został już przez Ciebie podpisany');
        }

        // Sprawdź walidacje biznesowe przed podpisaniem
        $this->validateDocumentBeforeSigning($dokument);

        $podpis->setStatus(PodpisDokumentu::STATUS_PODPISANY);
        $podpis->setDataPodpisania(new \DateTime());

        if ($komentarz) {
            $podpis->setKomentarz($komentarz);
        }

        if ($podpisElektroniczny) {
            $podpis->setPodpisElektroniczny($podpisElektroniczny);
        }

        // Sprawdź czy wszystkie podpisy zostały złożone
        if ($dokument->isFullySigned()) {
            $dokument->setStatus(Dokument::STATUS_PODPISANY);
            $dokument->setDataPodpisania(new \DateTime());

            // Automatyczne wykonanie akcji związanych z dokumentem (przyznanie/odebranie roli)
            $this->executeDocumentAction($dokument);
        } else {
            $dokument->setStatus(Dokument::STATUS_CZEKA_NA_PODPIS);
        }

        $this->entityManager->flush();
    }

    /**
     * Wykonuje akcje związane z dokumentem po jego podpisaniu.
     */
    private function executeDocumentAction(Dokument $dokument): void
    {
        $this->logger->info('Executing document action', [
            'document_type' => $dokument->getTyp(),
            'document_id' => $dokument->getId(),
        ]);

        switch ($dokument->getTyp()) {
            case Dokument::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK:
            case Dokument::TYP_PRZYJECIE_CZLONKA_OKREG:
            case Dokument::TYP_PRZYJECIE_CZLONKA_KRAJOWY:
                $this->handleMemberAcceptance($dokument);
                break;

            case Dokument::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR:
                $this->handleAppointmentPelnomocnikStruktur($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR:
                $this->handleDismissalPelnomocnikStruktur($dokument);
                break;

            case Dokument::TYP_POWOLANIE_SEKRETARZ_PARTII:
                $this->handleAppointmentSekretarzPartii($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_SEKRETARZ_PARTII:
                $this->handleDismissalSekretarzPartii($dokument);
                break;

            case Dokument::TYP_POWOLANIE_SKARBNIK_PARTII:
                $this->handleAppointmentSkarbnikPartii($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_SKARBNIK_PARTII:
                $this->handleDismissalSkarbnikPartii($dokument);
                break;

            case Dokument::TYP_POWOLANIE_WICEPREZES_PARTII:
                $this->handleAppointmentWiceprezesPartii($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_WICEPREZES_PARTII:
                $this->handleDismissalWiceprezesPartii($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_PREZES_OKREGU:
                $this->handleDismissalPrezesOkregu($dokument);
                break;

            case Dokument::TYP_POWOLANIE_PO_PREZES_OKREGU:
                $this->handleAppointmentPoOkregu($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_PO_PREZES_OKREGU:
                $this->handleDismissalPoOkregu($dokument);
                break;

            case Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU:
                $this->handleAppointmentSekretarzOkregu($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_SEKRETARZ_OKREGU:
                $this->handleDismissalSekretarzOkregu($dokument);
                break;

            case Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU:
                $this->handleAppointmentSkarbnikOkregu($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_SKARBNIK_OKREGU:
                $this->handleDismissalSkarbnikOkregu($dokument);
                break;

            case Dokument::TYP_UTWORZENIE_ODDZIALU:
                $this->handleCreateOddzial($dokument);
                break;

                // District position appointments
            case Dokument::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU:
                $this->handleAppointmentPrzevodniczacyOddzialu($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU:
                $this->handleDismissalPrzevodniczacyOddzialu($dokument);
                break;

            case Dokument::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO:
                $this->handleAppointmentZastepca($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO:
                $this->handleDismissalZastepca($dokument);
                break;

            case Dokument::TYP_POWOLANIE_SEKRETARZA_ODDZIALU:
                $this->handleAppointmentSekretarzOddzialu($dokument);
                break;

            case Dokument::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU:
                $this->handleDismissalSekretarzOddzialu($dokument);
                break;

                // Meeting-related appointments
            case Dokument::TYP_WYZNACZENIE_OBSERWATORA:
                $this->handleAppointmentObserwator($dokument);
                break;

            case Dokument::TYP_WYZNACZENIE_PROTOKOLANTA:
                $this->handleAppointmentProtokolant($dokument);
                break;

            case Dokument::TYP_WYZNACZENIE_PROWADZACEGO:
                $this->handleAppointmentProwadzacy($dokument);
                break;
        }
    }

    /**
     * Waliduje dokument przed podpisaniem.
     */
    private function validateDocumentBeforeSigning(Dokument $dokument): void
    {
        switch ($dokument->getTyp()) {
            case Dokument::TYP_POWOLANIE_SEKRETARZ_OKREGU:
                $this->validateAppointmentSekretarzOkregu($dokument);
                break;

            case Dokument::TYP_POWOLANIE_SKARBNIK_OKREGU:
                $this->validateAppointmentSkarbnikOkregu($dokument);
                break;

            case Dokument::TYP_UTWORZENIE_ODDZIALU:
                $this->validateCreateOddzial($dokument);
                break;
        }
    }

    /**
     * Waliduje powołanie Sekretarza Okręgu.
     */
    private function validateAppointmentSekretarzOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek || !$czlonek->getOkreg()) {
            return;
        }

        // Sprawdź czy w okręgu nie ma już Sekretarza
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT u.imie, u.nazwisko FROM "user" u 
                WHERE u.status = :status 
                AND u.roles::jsonb @> :role
                AND u.okreg_id = :okreg_id
                AND u.id != :current_user_id';

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery([
            'status' => 'aktywny',
            'role' => '["ROLE_SEKRETARZ_OKREGU"]',
            'okreg_id' => $czlonek->getOkreg()->getId(),
            'current_user_id' => $czlonek->getId(),
        ]);

        $existingSecretary = $result->fetchAssociative();
        if ($existingSecretary) {
            throw new \RuntimeException(sprintf('Nie można powołać na Sekretarza Okręgu. W okręgu %s już pełni tę funkcję: %s %s. Najpierw należy odwołać aktualnego Sekretarza Okręgu.', $czlonek->getOkreg()->getNazwa(), $existingSecretary['imie'], $existingSecretary['nazwisko']));
        }
    }

    /**
     * Waliduje powołanie Skarbnika Okręgu.
     */
    private function validateAppointmentSkarbnikOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek || !$czlonek->getOkreg()) {
            return;
        }

        // Sprawdź czy w okręgu nie ma już Skarbnika
        $connection = $this->entityManager->getConnection();
        $sql = 'SELECT u.imie, u.nazwisko FROM "user" u 
                WHERE u.status = :status 
                AND u.roles::jsonb @> :role
                AND u.okreg_id = :okreg_id
                AND u.id != :current_user_id';

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery([
            'status' => 'aktywny',
            'role' => '["ROLE_SKARBNIK_OKREGU"]',
            'okreg_id' => $czlonek->getOkreg()->getId(),
            'current_user_id' => $czlonek->getId(),
        ]);

        $existingTreasurer = $result->fetchAssociative();
        if ($existingTreasurer) {
            throw new \RuntimeException(sprintf('Nie można powołać na Skarbnika Okręgu. W okręgu %s już pełni tę funkcję: %s %s. Najpierw należy odwołać aktualnego Skarbnika Okręgu.', $czlonek->getOkreg()->getNazwa(), $existingTreasurer['imie'], $existingTreasurer['nazwisko']));
        }
    }

    /**
     * Waliduje utworzenie oddziału.
     */
    private function validateCreateOddzial(Dokument $dokument): void
    {
        $tworca = $dokument->getTworca();
        if (!$tworca->getOkreg()) {
            throw new \RuntimeException('Błąd: Brak informacji o okręgu twórcy dokumentu.');
        }

        $daneDodatkowe = $dokument->getDaneDodatkowe();
        if (!$daneDodatkowe || !isset($daneDodatkowe['nazwa_oddzialu']) || !isset($daneDodatkowe['czlonkowie_oddzialu'])) {
            throw new \RuntimeException('Błąd: Brak wymaganych danych do utworzenia oddziału.');
        }

        $nazwaOddzialu = $daneDodatkowe['nazwa_oddzialu'];
        $czlonkowieIds = $daneDodatkowe['czlonkowie_oddzialu'];

        // Walidacja minimum 2 członków
        if (count($czlonkowieIds) < 2) {
            throw new \RuntimeException('Oddział musi mieć minimum 2 członków.');
        }

        // Sprawdź czy nazwa oddziału nie jest już zajęta w okręgu
        $oddzialRepository = $this->entityManager->getRepository(Oddzial::class);
        $existingOddzial = $oddzialRepository->findOneBy([
            'nazwa' => $nazwaOddzialu,
            'okreg' => $tworca->getOkreg(),
        ]);

        if ($existingOddzial) {
            throw new \RuntimeException(sprintf('Oddział o nazwie "%s" już istnieje w okręgu %s. Wybierz inną nazwę.', $nazwaOddzialu, $tworca->getOkreg()->getNazwa()));
        }

        // Sprawdź czy wybrani członkowie nie są już przypisani do innych oddziałów
        $userRepository = $this->entityManager->getRepository(User::class);
        $zajęciCzłonkowie = [];

        foreach ($czlonkowieIds as $czlonekId) {
            $czlonek = $userRepository->find($czlonekId);
            if ($czlonek && $czlonek->getOddzial()) {
                $zajęciCzłonkowie[] = $czlonek->getFullName().' (przypisany do: '.$czlonek->getOddzial()->getNazwa().')';
            }
        }

        if (!empty($zajęciCzłonkowie)) {
            throw new \RuntimeException(sprintf('Następujący członkowie są już przypisani do innych oddziałów: %s', implode(', ', $zajęciCzłonkowie)));
        }
    }

    /**
     * Obsługuje przyjęcie członka.
     */
    private function handleMemberAcceptance(Dokument $dokument): void
    {
        $kandydat = $dokument->getKandydat();
        if (!$kandydat) {
            return;
        }

        // Sprawdź czy kandydat nie jest już członkiem
        if ('czlonek' === $kandydat->getTypUzytkownika()) {
            return;
        }

        // Zmień typ użytkownika z kandydata na członka
        $kandydat->setTypUzytkownika('czlonek');

        // Dodaj rolę członka partii
        $currentRoles = $kandydat->getRoles();
        if (!in_array('ROLE_CZLONEK_PARTII', $currentRoles)) {
            $currentRoles[] = 'ROLE_CZLONEK_PARTII';
            $kandydat->setRoles($currentRoles);
        }

        // Ustaw datę przyjęcia do partii
        $kandydat->setDataPrzyjeciaDoPartii($dokument->getDataPodpisania() ?? new \DateTime());

        // Zapisz zmiany
        $this->entityManager->persist($kandydat);

    }

    /**
     * Obsługuje powołanie Pełnomocnika ds. Struktur.
     */
    private function handleAppointmentPelnomocnikStruktur(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Dodaj rolę Pełnomocnika ds. Struktur
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_PELNOMOCNIK_STRUKTUR', $currentRoles)) {
            $currentRoles[] = 'ROLE_PELNOMOCNIK_STRUKTUR';
            $czlonek->setRoles($currentRoles);
        }

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje odwołanie Pełnomocnika ds. Struktur.
     */
    private function handleDismissalPelnomocnikStruktur(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Pełnomocnika ds. Struktur (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_PELNOMOCNIK_STRUKTUR' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje powołanie Sekretarza Partii.
     */
    private function handleAppointmentSekretarzPartii(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Dodaj rolę Sekretarza Partii
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_SEKRETARZ_PARTII', $currentRoles)) {
            $currentRoles[] = 'ROLE_SEKRETARZ_PARTII';
            $czlonek->setRoles($currentRoles);
        }

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje odwołanie Sekretarza Partii.
     */
    private function handleDismissalSekretarzPartii(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Sekretarza Partii (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_SEKRETARZ_PARTII' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje powołanie Skarbnika Partii.
     */
    private function handleAppointmentSkarbnikPartii(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Dodaj rolę Skarbnika Partii
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_SKARBNIK_PARTII', $currentRoles)) {
            $currentRoles[] = 'ROLE_SKARBNIK_PARTII';
            $czlonek->setRoles($currentRoles);

            // Zapisz zmiany
            $this->entityManager->persist($czlonek);

        }
    }

    /**
     * Obsługuje odwołanie Skarbnika Partii.
     */
    private function handleDismissalSkarbnikPartii(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Skarbnika Partii (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_SKARBNIK_PARTII' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje powołanie Wiceprezesa Partii.
     */
    private function handleAppointmentWiceprezesPartii(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Dodaj rolę Wiceprezesa Partii
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_WICEPREZES_PARTII', $currentRoles)) {
            $currentRoles[] = 'ROLE_WICEPREZES_PARTII';
            $czlonek->setRoles($currentRoles);

            // Zapisz zmiany
            $this->entityManager->persist($czlonek);

        }
    }

    /**
     * Obsługuje odwołanie Wiceprezesa Partii.
     */
    private function handleDismissalWiceprezesPartii(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Wiceprezesa Partii (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_WICEPREZES_PARTII' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje odwołanie Prezesa Okręgu.
     */
    private function handleDismissalPrezesOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Prezesa Okręgu (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_PREZES_OKREGU' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje powołanie p.o. Prezesa Okręgu.
     */
    private function handleAppointmentPoOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        $okreg = $dokument->getOkreg();
        if (!$czlonek || !$okreg) {
            return;
        }

        // Dodaj rolę p.o. Prezesa Okręgu
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_PO_PREZES_OKREGU', $currentRoles)) {
            $currentRoles[] = 'ROLE_PO_PREZES_OKREGU';
            $czlonek->setRoles($currentRoles);
        }

        // Ustaw okręg użytkownika na wskazany w dokumencie
        $czlonek->setOkreg($okreg);

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje odwołanie p.o. Prezesa Okręgu.
     */
    private function handleDismissalPoOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę p.o. Prezesa Okręgu (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_PO_PREZES_OKREGU' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje powołanie Sekretarza Okręgu.
     */
    private function handleAppointmentSekretarzOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Dodaj rolę Sekretarza Okręgu
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_SEKRETARZ_OKREGU', $currentRoles)) {
            $currentRoles[] = 'ROLE_SEKRETARZ_OKREGU';
            $czlonek->setRoles($currentRoles);

            // Zapisz zmiany
            $this->entityManager->persist($czlonek);

        }
    }

    /**
     * Obsługuje odwołanie Sekretarza Okręgu.
     */
    private function handleDismissalSekretarzOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Sekretarza Okręgu (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_SEKRETARZ_OKREGU' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje powołanie Skarbnika Okręgu.
     */
    private function handleAppointmentSkarbnikOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Dodaj rolę Skarbnika Okręgu
        $currentRoles = $czlonek->getRoles();
        if (!in_array('ROLE_SKARBNIK_OKREGU', $currentRoles)) {
            $currentRoles[] = 'ROLE_SKARBNIK_OKREGU';
            $czlonek->setRoles($currentRoles);

            // Zapisz zmiany
            $this->entityManager->persist($czlonek);

        }
    }

    /**
     * Obsługuje odwołanie Skarbnika Okręgu.
     */
    private function handleDismissalSkarbnikOkregu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        // Usuń rolę Skarbnika Okręgu (zachowaj inne role)
        $currentRoles = $czlonek->getRoles();
        $currentRoles = array_filter($currentRoles, function ($role) {
            return 'ROLE_SKARBNIK_OKREGU' !== $role;
        });
        $czlonek->setRoles(array_values($currentRoles)); // Reindeksuj tablicę

        // Zapisz zmiany
        $this->entityManager->persist($czlonek);

    }

    /**
     * Obsługuje utworzenie oddziału.
     */
    private function handleCreateOddzial(Dokument $dokument): void
    {
        $tworca = $dokument->getTworca();
        if (!$tworca->getOkreg()) {
            return;
        }

        $daneDodatkowe = $dokument->getDaneDodatkowe();
        if (!$daneDodatkowe || !isset($daneDodatkowe['nazwa_oddzialu']) || !isset($daneDodatkowe['czlonkowie_oddzialu'])) {
            return;
        }

        $nazwaOddzialu = $daneDodatkowe['nazwa_oddzialu'];
        $czlonkowieIds = $daneDodatkowe['czlonkowie_oddzialu'];

        // Walidacja minimum 2 członków
        if (count($czlonkowieIds) < 2) {
            return;
        }

        // Sprawdź czy nazwa oddziału nie jest już zajęta w okręgu
        $oddzialRepository = $this->entityManager->getRepository(Oddzial::class);
        $existingOddzial = $oddzialRepository->findOneBy([
            'nazwa' => $nazwaOddzialu,
            'okreg' => $tworca->getOkreg(),
        ]);

        if ($existingOddzial) {
            $this->logger->warning('District with this name already exists', [
                'nazwa_oddzialu' => $nazwaOddzialu,
                'okreg' => $tworca->getOkreg()->getNazwa(),
            ]);

            return;
        }

        // Utwórz nowy oddział
        $oddzial = new Oddzial();
        $oddzial->setNazwa($nazwaOddzialu);
        $oddzial->setOkreg($tworca->getOkreg());

        $this->entityManager->persist($oddzial);
        $this->entityManager->flush(); // Zapisz oddział, żeby uzyskać ID

        // Przypisz członków do oddziału
        $userRepository = $this->entityManager->getRepository(User::class);
        $przypisaniCzlonkowie = [];

        foreach ($czlonkowieIds as $czlonekId) {
            $czlonek = $userRepository->find($czlonekId);
            if ($czlonek && $czlonek->getOkreg() === $tworca->getOkreg() && !$czlonek->getOddzial()) {
                $czlonek->setOddzial($oddzial);
                $this->entityManager->persist($czlonek);
                $przypisaniCzlonkowie[] = $czlonek->getFullName();
            }
        }

        $this->entityManager->flush();

    }

    /**
     * Odrzuca dokument.
     */
    public function rejectDocument(Dokument $dokument, User $user, string $powod): void
    {
        $podpis = $dokument->getUserSignature($user);
        if (!$podpis) {
            throw new AccessDeniedException('Nie możesz odrzucić tego dokumentu');
        }

        $podpis->setStatus(PodpisDokumentu::STATUS_ODRZUCONY);
        $podpis->setDataPodpisania(new \DateTime());
        $podpis->setKomentarz($powod);

        // Dokument zostaje anulowany przy pierwszym odrzuceniu
        $dokument->setStatus(Dokument::STATUS_ANULOWANY);

        $this->entityManager->flush();
    }

    /**
     * Zwraca statystyki dokumentów dla użytkownika.
     */
    /**
     * @return array<string, mixed>
     */
    public function getDocumentStats(User $user): array
    {
        return $this->dokumentRepository->getDocumentStats($user);
    }

    /**
     * Legacy methods for backward compatibility.
     */
    /**
     * @return array<string, mixed>
     */
    public function getCandidatesReadyForAcceptance(User $user): array
    {
        return $this->getUserChoices('candidates_ready', $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDistrictBoardMembers(User $user): array
    {
        return $this->getUserChoices('board_members', $user);
    }

    public function canCreateMemberAcceptanceDocument(User $creator, User $candidate): bool
    {
        $userRoles = $creator->getRoles();

        // Sprawdź czy ma uprawnienia do któregokolwiek typu przyjęcia członka
        return in_array('ROLE_PELNOMOCNIK_PRZYJMOWANIA', $userRoles)
               || in_array('ROLE_PREZES_OKREGU', $userRoles)
               || in_array('ROLE_PREZES_PARTII', $userRoles)
               || in_array('ROLE_SEKRETARZ_PARTII', $userRoles)
               || in_array('ROLE_ADMIN', $userRoles);
    }

    /**
     * Handles district chairman appointment.
     */
    private function handleAppointmentPrzevodniczacyOddzialu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        $roles = $czlonek->getRoles();
        if (!in_array('ROLE_PRZEWODNICZACY_ODDZIALU', $roles)) {
            $roles[] = 'ROLE_PRZEWODNICZACY_ODDZIALU';
            $czlonek->setRoles($roles);
            $this->entityManager->flush();

        }
    }

    /**
     * Handles district chairman dismissal.
     */
    private function handleDismissalPrzevodniczacyOddzialu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        $roles = $czlonek->getRoles();
        $roles = array_filter($roles, fn ($role) => 'ROLE_PRZEWODNICZACY_ODDZIALU' !== $role);
        $czlonek->setRoles(array_values($roles));
        $this->entityManager->flush();

    }

    /**
     * Handles deputy chairman appointment.
     */
    private function handleAppointmentZastepca(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        $roles = $czlonek->getRoles();
        if (!in_array('ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', $roles)) {
            $roles[] = 'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU';
            $czlonek->setRoles($roles);
            $this->entityManager->flush();

        }
    }

    /**
     * Handles deputy chairman dismissal.
     */
    private function handleDismissalZastepca(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        $roles = $czlonek->getRoles();
        $roles = array_filter($roles, fn ($role) => 'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU' !== $role);
        $czlonek->setRoles(array_values($roles));
        $this->entityManager->flush();

    }

    /**
     * Handles district secretary appointment.
     */
    private function handleAppointmentSekretarzOddzialu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        $roles = $czlonek->getRoles();
        if (!in_array('ROLE_SEKRETARZ_ODDZIALU', $roles)) {
            $roles[] = 'ROLE_SEKRETARZ_ODDZIALU';
            $czlonek->setRoles($roles);
            $this->entityManager->flush();

        }
    }

    /**
     * Handles district secretary dismissal.
     */
    private function handleDismissalSekretarzOddzialu(Dokument $dokument): void
    {
        $czlonek = $dokument->getCzlonek();
        if (!$czlonek) {
            return;
        }

        $roles = $czlonek->getRoles();
        $roles = array_filter($roles, fn ($role) => 'ROLE_SEKRETARZ_ODDZIALU' !== $role);
        $czlonek->setRoles(array_values($roles));
        $this->entityManager->flush();

    }

    /**
     * Handles meeting observer appointment.
     */
    private function handleAppointmentObserwator(Dokument $dokument): void
    {
        $obserwator = $dokument->getCzlonek();
        if (!$obserwator) {
            $this->logger->error('Observer appointment processing error', [
                'error' => 'Missing observer in document',
                'document_type' => $dokument->getTyp(),
                'additional_data' => $dokument->getDaneDodatkowe(),
            ]);

            return;
        }

        $this->logger->info('Processing observer appointment', [
            'observer_name' => $obserwator->getFullName(),
            'observer_email' => $obserwator->getEmail(),
        ]);

        $roles = $obserwator->getRoles();
        if (!in_array('ROLE_OBSERWATOR_ZEBRANIA', $roles)) {
            $roles[] = 'ROLE_OBSERWATOR_ZEBRANIA';
            $obserwator->setRoles($roles);
            $this->entityManager->flush();

            // Odśwież token bezpieczeństwa jeśli to aktualnie zalogowany użytkownik
            $this->refreshUserTokenIfNeeded($obserwator);

        } else {
        }
    }

    /**
     * Handles meeting secretary appointment.
     */
    private function handleAppointmentProtokolant(Dokument $dokument): void
    {
        $protokolant = $dokument->getCzlonek();
        if (!$protokolant) {
            return;
        }

        $roles = $protokolant->getRoles();
        if (!in_array('ROLE_PROTOKOLANT_ZEBRANIA', $roles)) {
            $roles[] = 'ROLE_PROTOKOLANT_ZEBRANIA';
            $protokolant->setRoles($roles);
            $this->entityManager->flush();

        }
    }

    /**
     * Handles meeting chair appointment.
     */
    private function handleAppointmentProwadzacy(Dokument $dokument): void
    {
        $prowadzacy = $dokument->getCzlonek();
        if (!$prowadzacy) {
            return;
        }

        $roles = $prowadzacy->getRoles();
        if (!in_array('ROLE_PROWADZACY_ZEBRANIA', $roles)) {
            $roles[] = 'ROLE_PROWADZACY_ZEBRANIA';
            $prowadzacy->setRoles($roles);
            $this->entityManager->flush();

        }
    }

    /**
     * Odświeża token bezpieczeństwa użytkownika jeśli to aktualnie zalogowany użytkownik.
     */
    private function refreshUserTokenIfNeeded(User $user): void
    {
        if (!$this->tokenStorage) {
            return; // Brak tokenu storage (np. w console commands)
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return; // Brak aktywnego tokenu
        }

        $currentUser = $token->getUser();
        if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
            return; // To nie jest aktualnie zalogowany użytkownik
        }

        // Utwórz nowy token z odświeżonymi rolami
        $newToken = new UsernamePasswordToken(
            $user,
            'main', // nazwa firewalla
            $user->getRoles()
        );

        $this->tokenStorage->setToken($newToken);

        $this->logger->info('Security token refreshed', [
            'user_name' => $user->getFullName(),
            'new_roles' => $user->getRoles(),
        ]);
    }
}
