<?php

namespace App\Enum;

/**
 * Enum dla statusów zebraD (oddziaBu i okrgu)
 */
enum ZebranieStatusEnum: string
{
    // Statusy zebraD oddziaBu
    case OCZEKUJE_NA_PROTOKOLANTA = 'oczekuje_na_protokolanta';
    case OCZEKUJE_NA_PROWADZACEGO = 'oczekuje_na_prowadzacego';
    case WYBOR_PRZEWODNICZACEGO = 'wybor_przewodniczacego';
    case WYBOR_ZASTEPCOW = 'wybor_zastepcow';
    case WYBOR_ZARZADU = 'wybor_zarzadu';
    case OCZEKUJE_NA_PODPISY = 'oczekuje_na_podpisy';

    // Statusy zebraD okrgu
    case ROZPOCZETE = 'rozpoczete';
    case WYZNACZANIE_PROTOKOLANTA = 'wyznaczanie_protokolanta';
    case WYZNACZANIE_PROWADZACEGO = 'wyznaczanie_prowadzacego';
    case WYBOR_PREZESA = 'wybor_prezesa';
    case WYBOR_WICEPREZESOW = 'wybor_wiceprezesow';
    case SKLADANIE_PODPISOW = 'skladanie_podpisow';
    case OCZEKUJE_NA_AKCEPTACJE = 'oczekuje_na_akceptacje';

    // Wspólne statusy koDcowe
    case ZAKONCZONE = 'zakonczone';
    case ANULOWANE = 'anulowane';

    /**
     * Zwraca czyteln nazw statusu w jzyku polskim
     */
    public function getDisplayName(): string
    {
        return match($this) {
            // Statusy zebraD oddziaBu
            self::OCZEKUJE_NA_PROTOKOLANTA => 'Oczekuje na protokolanta',
            self::OCZEKUJE_NA_PROWADZACEGO => 'Oczekuje na prowadzcego',
            self::WYBOR_PRZEWODNICZACEGO => 'Wybór przewodniczcego',
            self::WYBOR_ZASTEPCOW => 'Wybór zastpców',
            self::WYBOR_ZARZADU => 'Wybór zarzdu',
            self::OCZEKUJE_NA_PODPISY => 'Oczekuje na podpisy',

            // Statusy zebraD okrgu
            self::ROZPOCZETE => 'Rozpoczte',
            self::WYZNACZANIE_PROTOKOLANTA => 'Wyznaczanie protokolanta',
            self::WYZNACZANIE_PROWADZACEGO => 'Wyznaczanie prowadzcego',
            self::WYBOR_PREZESA => 'Wybór prezesa',
            self::WYBOR_WICEPREZESOW => 'Wybór wiceprezesów',
            self::SKLADANIE_PODPISOW => 'SkBadanie podpisów',
            self::OCZEKUJE_NA_AKCEPTACJE => 'Oczekuje na akceptacj',

            // Wspólne statusy koDcowe
            self::ZAKONCZONE => 'ZakoDczone',
            self::ANULOWANE => 'Anulowane',
        };
    }

    /**
     * Zwraca klas CSS dla statusu
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::ZAKONCZONE => 'badge bg-success',
            self::ANULOWANE => 'badge bg-danger',
            self::OCZEKUJE_NA_PROTOKOLANTA,
            self::OCZEKUJE_NA_PROWADZACEGO,
            self::OCZEKUJE_NA_PODPISY,
            self::OCZEKUJE_NA_AKCEPTACJE => 'badge bg-warning',
            default => 'badge bg-primary',
        };
    }

    /**
     * Zwraca ikon Font Awesome dla statusu
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ZAKONCZONE => 'fas fa-check-circle',
            self::ANULOWANE => 'fas fa-times-circle',
            self::OCZEKUJE_NA_PROTOKOLANTA,
            self::WYZNACZANIE_PROTOKOLANTA => 'fas fa-user-edit',
            self::OCZEKUJE_NA_PROWADZACEGO,
            self::WYZNACZANIE_PROWADZACEGO => 'fas fa-user-tie',
            self::WYBOR_PRZEWODNICZACEGO,
            self::WYBOR_PREZESA => 'fas fa-crown',
            self::WYBOR_ZASTEPCOW,
            self::WYBOR_WICEPREZESOW => 'fas fa-users',
            self::WYBOR_ZARZADU => 'fas fa-sitemap',
            self::OCZEKUJE_NA_PODPISY,
            self::SKLADANIE_PODPISOW => 'fas fa-signature',
            self::OCZEKUJE_NA_AKCEPTACJE => 'fas fa-thumbs-up',
            default => 'fas fa-clock',
        };
    }

    /**
     * Sprawdza czy status oznacza zebranie w toku
     */
    public function isInProgress(): bool
    {
        return !in_array($this, [self::ZAKONCZONE, self::ANULOWANE]);
    }

    /**
     * Sprawdza czy status oznacza zebranie zakoDczone
     */
    public function isCompleted(): bool
    {
        return $this === self::ZAKONCZONE;
    }

    /**
     * Sprawdza czy status oznacza zebranie anulowane
     */
    public function isCancelled(): bool
    {
        return $this === self::ANULOWANE;
    }

    /**
     * Zwraca wszystkie statusy dla zebraD oddziaBu
     *
     * @return array<self>
     */
    public static function getOddzialStatuses(): array
    {
        return [
            self::OCZEKUJE_NA_PROTOKOLANTA,
            self::OCZEKUJE_NA_PROWADZACEGO,
            self::WYBOR_PRZEWODNICZACEGO,
            self::WYBOR_ZASTEPCOW,
            self::WYBOR_ZARZADU,
            self::OCZEKUJE_NA_PODPISY,
            self::ZAKONCZONE,
            self::ANULOWANE,
        ];
    }

    /**
     * Zwraca wszystkie statusy dla zebraD okrgu
     *
     * @return array<self>
     */
    public static function getOkregStatuses(): array
    {
        return [
            self::ROZPOCZETE,
            self::WYZNACZANIE_PROTOKOLANTA,
            self::WYZNACZANIE_PROWADZACEGO,
            self::WYBOR_PREZESA,
            self::WYBOR_WICEPREZESOW,
            self::SKLADANIE_PODPISOW,
            self::OCZEKUJE_NA_AKCEPTACJE,
            self::ZAKONCZONE,
            self::ANULOWANE,
        ];
    }

    /**
     * Zwraca nastpny mo|liwy status w workflow
     */
    public function getNextStatus(): ?self
    {
        return match($this) {
            // Workflow zebraD oddziaBu
            self::OCZEKUJE_NA_PROTOKOLANTA => self::OCZEKUJE_NA_PROWADZACEGO,
            self::OCZEKUJE_NA_PROWADZACEGO => self::WYBOR_PRZEWODNICZACEGO,
            self::WYBOR_PRZEWODNICZACEGO => self::WYBOR_ZASTEPCOW,
            self::WYBOR_ZASTEPCOW => self::WYBOR_ZARZADU,
            self::WYBOR_ZARZADU => self::OCZEKUJE_NA_PODPISY,
            self::OCZEKUJE_NA_PODPISY => self::ZAKONCZONE,

            // Workflow zebraD okrgu
            self::ROZPOCZETE => self::WYZNACZANIE_PROTOKOLANTA,
            self::WYZNACZANIE_PROTOKOLANTA => self::WYZNACZANIE_PROWADZACEGO,
            self::WYZNACZANIE_PROWADZACEGO => self::WYBOR_PREZESA,
            self::WYBOR_PREZESA => self::WYBOR_WICEPREZESOW,
            self::WYBOR_WICEPREZESOW => self::SKLADANIE_PODPISOW,
            self::SKLADANIE_PODPISOW => self::OCZEKUJE_NA_AKCEPTACJE,
            self::OCZEKUJE_NA_AKCEPTACJE => self::ZAKONCZONE,

            // Statusy koDcowe
            self::ZAKONCZONE, self::ANULOWANE => null,
        };
    }

    /**
     * Sprawdza czy mo|na przej[ do nastpnego kroku
     */
    public function canProceed(): bool
    {
        return $this->getNextStatus() !== null;
    }

    /**
     * Zwraca wszystkie mo|liwe statusy
     *
     * @return array<self>
     */
    public static function getAllStatuses(): array
    {
        return self::cases();
    }

    /**
     * Tworzy enum z warto[ci string (bezpieczne)
     */
    public static function tryFromValue(string $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Sprawdza czy warto[ jest prawidBowym statusem
     */
    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }

    /**
     * Zwraca opis tego co dzieje si w danym statusie
     */
    public function getDescription(): string
    {
        return match($this) {
            self::OCZEKUJE_NA_PROTOKOLANTA => 'Obserwator musi wyznaczy protokolanta zebrania',
            self::OCZEKUJE_NA_PROWADZACEGO => 'Obserwator musi wyznaczy prowadzcego zebranie',
            self::WYBOR_PRZEWODNICZACEGO => 'Protokolant i prowadzcy wybieraj przewodniczcego oddziaBu',
            self::WYBOR_ZASTEPCOW => 'Przewodniczcy wybiera swoich zastpców',
            self::WYBOR_ZARZADU => 'Protokolant i prowadzcy wybieraj reszt zarzdu',
            self::OCZEKUJE_NA_PODPISY => 'Wszyscy uczestnicy musz podpisa dokumenty zebrania',

            self::ROZPOCZETE => 'Zebranie okrgu zostaBo rozpoczte',
            self::WYZNACZANIE_PROTOKOLANTA => 'Obserwator wyznacza protokolanta',
            self::WYZNACZANIE_PROWADZACEGO => 'Obserwator wyznacza prowadzcego',
            self::WYBOR_PREZESA => 'Protokolant i prowadzcy wybieraj prezesa okrgu',
            self::WYBOR_WICEPREZESOW => 'Protokolant i prowadzcy wybieraj wiceprezesów',
            self::SKLADANIE_PODPISOW => 'Uczestnicy skBadaj podpisy elektroniczne',
            self::OCZEKUJE_NA_AKCEPTACJE => 'Wszyscy uczestnicy musz zaakceptowa wyniki',

            self::ZAKONCZONE => 'Zebranie zostaBo pomy[lnie zakoDczone',
            self::ANULOWANE => 'Zebranie zostaBo anulowane',
        };
    }
}