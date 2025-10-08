<?php

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

// Load environment
(new Dotenv())->bootEnv(__DIR__ . '/.env');

// Boot Symfony kernel
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get services
$entityManager = $container->get('doctrine')->getManager();
$userRepository = $entityManager->getRepository(\App\Entity\User::class);
$oddzialRepository = $entityManager->getRepository(\App\Entity\Oddzial::class);

$csvFile = __DIR__ . '/osoby.csv';

if (!file_exists($csvFile)) {
    die("❌ Plik osoby.csv nie istnieje!\n");
}

echo "📋 Dodawanie ról zarządu oddziałów\n";
echo "=========================================\n\n";

$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("❌ Nie można otworzyć pliku osoby.csv\n");
}

// Skip header
fgetcsv($handle, 0, ';');

// Position to role mapping
$roleMapping = [
    4 => 'ROLE_PRZEWODNICZACY_ODDZIALU',        // Przewodniczący
    5 => 'ROLE_SEKRETARZ_ODDZIALU',              // Sekretarz
    6 => 'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU', // Zastępca przewodniczącego
];

$positionNames = [
    4 => 'Przewodniczący',
    5 => 'Sekretarz',
    6 => 'Zastępca Przewodniczącego',
];

// Statistics
$stats = [
    'total_rows' => 0,
    'total_positions' => 0,
    'updated' => 0,
    'already_has' => 0,
    'not_found' => 0,
    'empty_position' => 0,
];

$errors = [];
$successes = [];

/**
 * Parse name from CSV (handles "First Second Last" format)
 * Returns: ['first' => 'First', 'last' => 'Last']
 */
function parseName(string $fullName): ?array
{
    $fullName = trim($fullName);
    if (empty($fullName)) {
        return null;
    }

    // Remove quotes if present
    $fullName = trim($fullName, '"');

    // Split by whitespace
    $parts = preg_split('/\s+/', $fullName);

    if (count($parts) < 2) {
        // Need at least first + last name
        return null;
    }

    // First word = first name, Last word = last name
    return [
        'first' => $parts[0],
        'last' => $parts[count($parts) - 1],
    ];
}

echo "Przetwarzanie pliku CSV...\n\n";

while (($data = fgetcsv($handle, 0, ';')) !== false) {
    if (count($data) < 7) {
        continue;
    }

    $stats['total_rows']++;

    $region = trim($data[0] ?? '');
    $oddzialNazwa = trim($data[1] ?? '');
    $okreg = trim($data[2] ?? '');
    $oldId = trim($data[3] ?? '');

    // Skip if no oddział name
    if (empty($oddzialNazwa)) {
        continue;
    }

    // Find oddział in database
    $oddzial = $oddzialRepository->findOneBy(['nazwa' => $oddzialNazwa]);

    if (!$oddzial) {
        $errors[] = sprintf(
            '❌ Oddział "%s" nie istnieje w bazie danych (wiersz %d)',
            $oddzialNazwa,
            $stats['total_rows'] + 1
        );
        continue;
    }

    echo "🏛️  Oddział: {$oddzialNazwa} (Region: {$region})\n";

    // Process each position (columns 4, 5, 6)
    foreach ($roleMapping as $columnIndex => $roleName) {
        $fullName = trim($data[$columnIndex] ?? '');
        $positionName = $positionNames[$columnIndex];

        $stats['total_positions']++;

        if (empty($fullName)) {
            $stats['empty_position']++;
            echo "   ⚪ {$positionName}: (brak)\n";
            continue;
        }

        // Parse name
        $parsedName = parseName($fullName);

        if (!$parsedName) {
            $stats['not_found']++;
            $errors[] = sprintf(
                '❌ Nieprawidłowy format imienia dla "%s" w oddziale %s',
                $fullName,
                $oddzialNazwa
            );
            echo "   ❌ {$positionName}: {$fullName} - nieprawidłowy format\n";
            continue;
        }

        $imie = $parsedName['first'];
        $nazwisko = $parsedName['last'];

        // Find user by first name, last name, and oddział
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.imie = :imie')
            ->andWhere('u.nazwisko = :nazwisko')
            ->andWhere('u.oddzial = :oddzial')
            ->setParameter('imie', $imie)
            ->setParameter('nazwisko', $nazwisko)
            ->setParameter('oddzial', $oddzial)
            ->setMaxResults(1);

        $user = $qb->getQuery()->getOneOrNullResult();

        if (!$user) {
            $stats['not_found']++;
            $errors[] = sprintf(
                '❌ Nie znaleziono: %s %s w oddziale %s (pełne imię z CSV: "%s")',
                $imie,
                $nazwisko,
                $oddzialNazwa,
                $fullName
            );
            echo "   ❌ {$positionName}: {$fullName} - nie znaleziono w bazie\n";
            continue;
        }

        // Check if user already has this role
        if (in_array($roleName, $user->getRoles(), true)) {
            $stats['already_has']++;
            echo "   ⚠️  {$positionName}: {$user->getImie()} {$user->getNazwisko()} - już ma rolę\n";
            continue;
        }

        // Add role
        $roles = $user->getRoles();
        $roles[] = $roleName;
        $user->setRoles(array_unique($roles));

        $entityManager->persist($user);
        $stats['updated']++;

        $successes[] = sprintf(
            '✓ %s: %s %s (%s) w oddziale %s',
            $positionName,
            $user->getImie(),
            $user->getNazwisko(),
            $user->getEmail(),
            $oddzialNazwa
        );
        echo "   ✅ {$positionName}: {$user->getImie()} {$user->getNazwisko()} ({$user->getEmail()})\n";
    }

    echo "\n";
}

fclose($handle);

// Save changes
if ($stats['updated'] > 0) {
    echo "💾 Zapisywanie zmian do bazy danych...\n";
    $entityManager->flush();
    echo "✅ Zmiany zapisane!\n\n";
}

// Display statistics
echo "=========================================\n";
echo "📊 STATYSTYKI\n";
echo "=========================================\n";
echo sprintf("Przetworzono wierszy CSV: %d\n", $stats['total_rows']);
echo sprintf("Przetworzono pozycji: %d\n", $stats['total_positions']);
echo sprintf("Puste pozycje: %d\n", $stats['empty_position']);
echo sprintf("✅ Zaktualizowano (dodano role): %d\n", $stats['updated']);
echo sprintf("⚠️  Już posiadali rolę: %d\n", $stats['already_has']);
echo sprintf("❌ Nie znaleziono w bazie: %d\n", $stats['not_found']);
echo "\n";

// Display detailed results
if (!empty($successes)) {
    echo "=========================================\n";
    echo "✅ POMYŚLNIE DODANO ROLE:\n";
    echo "=========================================\n";
    foreach ($successes as $success) {
        echo "   {$success}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "=========================================\n";
    echo "⚠️  BŁĘDY I OSTRZEŻENIA:\n";
    echo "=========================================\n";
    foreach ($errors as $error) {
        echo "   {$error}\n";
    }
    echo "\n";
}

echo "✅ Zakończono!\n";
