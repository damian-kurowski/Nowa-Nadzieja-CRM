<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:move-to-former-members',
    description: 'Przenosi użytkowników z prefiksem del_ do tabeli byly_czlonek i usuwa z user',
)]
class MoveToFormerMembersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Tylko pokaż co zostanie zrobione');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('Tryb DRY-RUN - żadne zmiany nie zostaną zapisane!');
        }

        $io->title('Przenoszenie użytkowników del_ do byłych członków');

        $userRepository = $this->entityManager->getRepository(User::class);

        // Znajdź użytkowników z del_ (członkowie i młodzieżówka)
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.email LIKE :prefix')
            ->andWhere('u.typUzytkownika IN (:types)')
            ->setParameter('prefix', 'del\_%')
            ->setParameter('types', ['czlonek', 'mlodziezowka', 'byly_czlonek'])
            ->getQuery()
            ->getResult();

        $io->text(sprintf('Znaleziono %d użytkowników z prefiksem "del_"', count($users)));
        $io->newLine();

        $moved = 0;

        foreach ($users as $user) {
            $email = $user->getEmail();
            $imie = $user->getImie();
            $nazwisko = $user->getNazwisko();

            // Usuń prefix "del_" z danych
            $cleanImie = str_starts_with($imie, 'del_') ? substr($imie, 4) : $imie;
            $cleanNazwisko = str_starts_with($nazwisko, 'del_') ? substr($nazwisko, 4) : $nazwisko;
            $cleanEmail = str_starts_with($email, 'del_') ? substr($email, 4) : $email;

            $drugieImie = $user->getDrugieImie();
            $cleanDrugieImie = null;
            if ($drugieImie && str_starts_with($drugieImie, 'del_')) {
                $cleanDrugieImie = substr($drugieImie, 4);
            } else {
                $cleanDrugieImie = $drugieImie;
            }

            $telefon = $user->getTelefon();
            $cleanTelefon = null;
            if ($telefon) {
                if (str_starts_with($telefon, 'del_48')) {
                    $cleanTelefon = substr($telefon, 6);
                } elseif (str_starts_with($telefon, 'del_')) {
                    $cleanTelefon = substr($telefon, 4);
                } else {
                    $cleanTelefon = $telefon;
                }
            }

            $pesel = $user->getPesel();
            $cleanPesel = null;
            if ($pesel && str_starts_with($pesel, 'del_')) {
                $cleanPesel = substr($pesel, 4);
            } else {
                $cleanPesel = $pesel;
            }

            $io->text(sprintf(
                '👤 %s %s (%s) → Były członek (usuwam prefix)',
                $cleanImie,
                $cleanNazwisko,
                $cleanEmail
            ));

            if (!$dryRun) {
                // Wstaw do tabeli byly_czlonek
                $this->connection->insert('byly_czlonek', [
                    'okreg_id' => $user->getOkreg()?->getId(),
                    'oddzial_id' => $user->getOddzial()?->getId(),
                    'imie' => $cleanImie,
                    'drugie_imie' => $cleanDrugieImie,
                    'nazwisko' => $cleanNazwisko,
                    'pesel' => $cleanPesel,
                    'data_urodzenia' => $user->getDataUrodzenia()?->format('Y-m-d'),
                    'plec' => $user->getPlec(),
                    'email' => $cleanEmail,
                    'telefon' => $cleanTelefon,
                    'adres_zamieszkania' => $user->getAdresZamieszkania(),
                    'social_media' => $user->getSocialMedia(),
                    'informacje_omnie' => $user->getInformacjeOmnie(),
                    'zdjecie' => $user->getZdjecie(),
                    'cv' => $user->getCv(),
                    'rodzaj_czlonkostwa' => $user->getTypUzytkownika() === 'mlodziezowka' ? 'mlodziezowka' : 'czlonek',
                    'data_przyjecia' => $user->getDataPrzyjeciaDoPartii()?->format('Y-m-d H:i:s'),
                    'data_zlozenia_deklaracji' => $user->getDataZlozeniaDeklaracji()?->format('Y-m-d H:i:s'),
                    'numer_wpartii' => $user->getNumerWPartii(),
                    'dodatkowe_informacje' => $user->getDodatkoweInformacje(),
                    'notatka_wewnetrzna' => $user->getNotatkaWewnetrzna(),
                    'oryginalny_id_czlonka' => $user->getId(),
                    'powod_zakonczenia_czlonkostwa' => 'Oznaczony jako usunięty (del_)',
                    'data_zakonczenia_czlonkostwa' => (new \DateTime())->format('Y-m-d'),
                ]);

                // Usuń powiązane rekordy
                $postepKandydata = $user->getPostepKandydataEntity();
                if ($postepKandydata) {
                    $this->entityManager->remove($postepKandydata);
                }

                foreach ($user->getSkladkiCzlonkowskie() as $skladka) {
                    $this->entityManager->remove($skladka);
                }

                foreach ($user->getOpinie() as $opinia) {
                    $this->entityManager->remove($opinia);
                }

                foreach ($user->getPlatnosci() as $platnosc) {
                    $this->entityManager->remove($platnosc);
                }

                foreach ($user->getFunkcje() as $funkcja) {
                    $this->entityManager->remove($funkcja);
                }

                // Usuń użytkownika z tabeli user
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            }

            $moved++;
        }

        if ($dryRun) {
            $io->info(sprintf(
                'Tryb DRY-RUN - znaleziono %d użytkowników do przeniesienia. Uruchom bez --dry-run aby zapisać.',
                $moved
            ));
        } else {
            $io->success(sprintf('Przeniesiono %d użytkowników do byłych członków!', $moved));
        }

        return Command::SUCCESS;
    }
}
