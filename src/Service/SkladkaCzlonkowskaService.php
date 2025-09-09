<?php

namespace App\Service;

use App\Entity\SkladkaCzlonkowska;
use App\Entity\User;
use App\Repository\SkladkaCzlonkowskaRepository;
use Doctrine\ORM\EntityManagerInterface;

class SkladkaCzlonkowskaService
{
    private EntityManagerInterface $entityManager;
    private SkladkaCzlonkowskaRepository $skladkaRepository;
    
    // Stawki składek historyczne
    private const STAWKI_SKLADEK = [
        '2022-06-06' => 20.00, // Od 6 czerwca 2022 - 20 PLN
        '1970-01-01' => 10.00  // Do 5 czerwca 2022 - 10 PLN (data początku epoki jako domyślna)
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        SkladkaCzlonkowskaRepository $skladkaRepository
    ) {
        $this->entityManager = $entityManager;
        $this->skladkaRepository = $skladkaRepository;
    }

    /**
     * Oblicz stawkę składki dla danego okresu
     */
    public function getStawkaSkladki(\DateTime $data): float
    {
        foreach (self::STAWKI_SKLADEK as $dataZmiany => $stawka) {
            $dataZmianyObj = new \DateTime($dataZmiany);
            if ($data >= $dataZmianyObj) {
                return $stawka;
            }
        }
        
        // Domyślnie 10 PLN (najstarsza stawka)
        return 10.00;
    }

    /**
     * Oblicz stawkę składki dla danego roku i miesiąca
     */
    public function getStawkaSkladkiDlaOkresu(int $rok, int $miesiac): float
    {
        $data = new \DateTime("$rok-$miesiac-01");
        return $this->getStawkaSkladki($data);
    }

    public function utworzSkladke(User $czlonek, int $rok, int $miesiac, ?string $kwota = null, ?User $zarejestrowanePrzez = null): SkladkaCzlonkowska
    {
        $istniejacaSkladka = $this->skladkaRepository->findByCzlonekAndOkres($czlonek, $rok, $miesiac);
        
        if ($istniejacaSkladka) {
            throw new \InvalidArgumentException(sprintf(
                'Składka za %s %d dla członka %s już istnieje.',
                $this->getNazwaMiesiaca($miesiac),
                $rok,
                $czlonek->getImieNazwisko()
            ));
        }

        // Jeśli kwota nie została podana, użyj stawki historycznej
        if ($kwota === null) {
            $kwota = number_format($this->getStawkaSkladkiDlaOkresu($rok, $miesiac), 2, '.', '');
        }

        $skladka = new SkladkaCzlonkowska();
        $skladka->setCzlonek($czlonek);
        $skladka->setRok($rok);
        $skladka->setMiesiac($miesiac);
        $skladka->setKwota($kwota);
        
        if ($zarejestrowanePrzez) {
            $skladka->setZarejestrowanePrzez($zarejestrowanePrzez);
        }

        $this->entityManager->persist($skladka);
        $this->entityManager->flush();

        return $skladka;
    }

    public function oznaczJakoOplacona(SkladkaCzlonkowska $skladka, ?\DateTime $dataPlatnosci = null, ?string $numerPlatnosci = null, ?string $sposobPlatnosci = null): void
    {
        $skladka->setStatus('oplacona');
        $skladka->setDataPlatnosci($dataPlatnosci ?? new \DateTime());
        
        if ($numerPlatnosci) {
            $skladka->setNumerPlatnosci($numerPlatnosci);
        }
        
        if ($sposobPlatnosci) {
            $skladka->setSposobPlatnosci($sposobPlatnosci);
        }

        // Ustaw datę ważności składki - uwzględnij nadpłaty
        $dataWaznosci = new \DateTime();
        $dataWaznosci->setDate($skladka->getRok(), $skladka->getMiesiac(), 1);
        $dataWaznosci->setTime(23, 59, 59);
        $dataWaznosci->modify('last day of this month');
        
        // Sprawdź czy to nadpłata i przedłuż ważność
        $kwotaSkladki = (float) $skladka->getKwota();
        $oczekiwanaKwota = $this->getStawkaSkladkiDlaOkresu($skladka->getRok(), $skladka->getMiesiac());
        
        if ($kwotaSkladki > $oczekiwanaKwota) {
            $iloscMiesiecy = max(1, (int)floor($kwotaSkladki / $oczekiwanaKwota));
            if ($iloscMiesiecy > 1) {
                $dataWaznosci->modify('+' . ($iloscMiesiecy - 1) . ' months');
                $dataWaznosci->modify('last day of this month');
            }
        }
        
        $skladka->setDataWaznosciSkladki($dataWaznosci);

        $this->entityManager->flush();
        
        // Zaktualizuj status składki w tabeli User
        $this->aktualizujStatusSkladkiWUser($skladka->getCzlonek());
    }

    public function anulujSkladke(SkladkaCzlonkowska $skladka, string $uwagi = null): void
    {
        $skladka->setStatus('anulowana');
        
        if ($uwagi) {
            $skladka->setUwagi($uwagi);
        }

        $this->entityManager->flush();
        
        // Zaktualizuj status składki w tabeli User
        $this->aktualizujStatusSkladkiWUser($skladka->getCzlonek());
    }

    public function zwolnijZeSkladki(SkladkaCzlonkowska $skladka, string $uwagi = null): void
    {
        $skladka->setStatus('zwolniona');
        
        if ($uwagi) {
            $skladka->setUwagi($uwagi);
        }

        // Ustaw datę ważności na koniec okresu składki
        $dataWaznosci = new \DateTime();
        $dataWaznosci->setDate($skladka->getRok(), $skladka->getMiesiac(), 1);
        $dataWaznosci->setTime(23, 59, 59);
        $dataWaznosci->modify('last day of this month');
        
        $skladka->setDataWaznosciSkladki($dataWaznosci);

        $this->entityManager->flush();
        
        // Zaktualizuj status składki w tabeli User
        $this->aktualizujStatusSkladkiWUser($skladka->getCzlonek());
    }

    public function generujSkladkiDlaWszystkichCzlonkow(int $rok, int $miesiac, ?string $domyslnaKwota = null): int
    {
        $czlonkowie = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.typUzytkownika IN (:typy)')
            ->andWhere('u.status = :status')
            ->setParameter('typy', ['czlonek', 'kandydat'])
            ->setParameter('status', 'aktywny')
            ->getQuery()
            ->getResult();

        // Jeśli nie podano kwoty, użyj stawki historycznej dla danego okresu
        if ($domyslnaKwota === null) {
            $domyslnaKwota = number_format($this->getStawkaSkladkiDlaOkresu($rok, $miesiac), 2, '.', '');
        }

        $liczbaUtworzonych = 0;

        foreach ($czlonkowie as $czlonek) {
            $istniejacaSkladka = $this->skladkaRepository->findByCzlonekAndOkres($czlonek, $rok, $miesiac);
            
            if (!$istniejacaSkladka) {
                $this->utworzSkladke($czlonek, $rok, $miesiac, $domyslnaKwota);
                $liczbaUtworzonych++;
            }
        }

        return $liczbaUtworzonych;
    }

    public function aktualizujStatusSkladkiWUser(User $czlonek): void
    {
        $aktualnaSkladka = $this->skladkaRepository->getAktualnaSkladka($czlonek);
        
        if ($aktualnaSkladka && $aktualnaSkladka->isOplacona() && $aktualnaSkladka->isWazna()) {
            $czlonek->setSkladkaOplacona(true);
            $czlonek->setDataOplaceniaSkladki($aktualnaSkladka->getDataPlatnosci());
            $czlonek->setKwotaSkladki($aktualnaSkladka->getKwota());
            $czlonek->setDataWaznosciSkladki($aktualnaSkladka->getDataWaznosciSkladki());
        } else {
            $czlonek->setSkladkaOplacona(false);
            // Zachowaj historyczne dane ostatniej opłaconej składki
        }
        
        $this->entityManager->flush();
    }

    public function przeniesZPlatnosciDoSkladek(): int
    {
        $platnosciSkladki = $this->entityManager->getRepository(\App\Entity\Platnosc::class)
            ->createQueryBuilder('p')
            ->where('p.typWplaty = :typ OR p.opisWplaty LIKE :opis')
            ->setParameter('typ', 'skladka')
            ->setParameter('opis', '%składka%')
            ->getQuery()
            ->getResult();

        $liczbaPrzeniesionych = 0;

        foreach ($platnosciSkladki as $platnosc) {
            // Spróbuj wyodrębnić rok i miesiąc z opisu płatności
            $rok = null;
            $miesiac = null;
            
            if ($platnosc->getDataPlatnosci()) {
                $dataWplaty = $platnosc->getDataPlatnosci();
                $rok = (int) $dataWplaty->format('Y');
                $miesiac = (int) $dataWplaty->format('n');
            } elseif ($platnosc->getDataIGodzina()) {
                $dataWplaty = $platnosc->getDataIGodzina();
                $rok = (int) $dataWplaty->format('Y');
                $miesiac = (int) $dataWplaty->format('n');
            }
            
            if (!$rok || !$miesiac) {
                continue; // Pomiń jeśli nie można określić okresu
            }

            // Sprawdź czy składka już istnieje
            $istniejacaSkladka = $this->skladkaRepository->findByCzlonekAndOkres(
                $platnosc->getDarczyca(),
                $rok,
                $miesiac
            );

            if (!$istniejacaSkladka) {
                $skladka = new SkladkaCzlonkowska();
                $skladka->setCzlonek($platnosc->getDarczyca());
                $skladka->setRok($rok);
                $skladka->setMiesiac($miesiac);
                $skladka->setKwota($platnosc->getKwota());
                $skladka->setNumerPlatnosci($platnosc->getNumerPlatnosci());
                $skladka->setImportPlatnosci($platnosc->getImportPlatnosci());
                
                if ($platnosc->getStatusPlatnosci() === 'potwierdzona') {
                    $skladka->setStatus('oplacona');
                    $skladka->setDataPlatnosci($platnosc->getDataKsiegowania() ?? $platnosc->getDataIGodzina());
                    
                    // Ustaw datę ważności
                    $dataWaznosci = new \DateTime();
                    $dataWaznosci->setDate($rok, $miesiac, 1);
                    $dataWaznosci->setTime(23, 59, 59);
                    $dataWaznosci->modify('last day of this month');
                    $skladka->setDataWaznosciSkladki($dataWaznosci);
                } else {
                    $skladka->setStatus($platnosc->getStatusPlatnosci());
                }
                
                $this->entityManager->persist($skladka);
                $liczbaPrzeniesionych++;
            }
        }

        $this->entityManager->flush();
        
        return $liczbaPrzeniesionych;
    }

    private function getNazwaMiesiaca(int $miesiac): string
    {
        $miesiace = [
            1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień',
            5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień',
            9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień'
        ];
        
        return $miesiace[$miesiac] ?? 'Nieznany';
    }

    public function getStatystykiSkladek(): array
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        
        $stats = [];
        
        // Aktualna składka
        $aktualneOplacone = $this->skladkaRepository->countOplaconeByOkres($currentYear, $currentMonth);
        $aktualnaSuma = $this->skladkaRepository->getSumaKwotByOkres($currentYear, $currentMonth);
        
        $stats['aktualna'] = [
            'okres' => $this->getNazwaMiesiaca($currentMonth) . ' ' . $currentYear,
            'oplacone' => $aktualneOplacone,
            'suma' => $aktualnaSuma
        ];
        
        // Poprzedni miesiąc
        $poprzedniMiesiac = $currentMonth - 1;
        $poprzedniRok = $currentYear;
        
        if ($poprzedniMiesiac < 1) {
            $poprzedniMiesiac = 12;
            $poprzedniRok--;
        }
        
        $poprzednieOplacone = $this->skladkaRepository->countOplaconeByOkres($poprzedniRok, $poprzedniMiesiac);
        $poprzedniaSuma = $this->skladkaRepository->getSumaKwotByOkres($poprzedniRok, $poprzedniMiesiac);
        
        $stats['poprzednia'] = [
            'okres' => $this->getNazwaMiesiaca($poprzedniMiesiac) . ' ' . $poprzedniRok,
            'oplacone' => $poprzednieOplacone,
            'suma' => $poprzedniaSuma
        ];
        
        // Statystyki roczne
        $stats['roczne'] = $this->skladkaRepository->getStatystykiRoczne($currentYear);
        
        return $stats;
    }
}