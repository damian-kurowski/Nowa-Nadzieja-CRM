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

$csvFile = __DIR__ . '/osoby.csv';

if (!file_exists($csvFile)) {
    die("❌ Plik osoby.csv nie istnieje!\n");
}

echo "📋 Dodawanie roli ROLE_SEKRETARZ_REGIONU\n";
echo "=========================================\n\n";

$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("❌ Nie można otworzyć pliku osoby.csv\n");
}

// Skip header
fgetcsv($handle, 0, ';');

$updated = 0;
$notFound = 0;
$alreadyHas = 0;
$errors = [];

while (($data = fgetcsv($handle, 0, ';')) !== false) {
    if (count($data) < 16) {
        continue;
    }

    $email = trim($data[9] ?? '');
    $pesel = trim($data[15] ?? '');
    $nazwisko = trim($data[2] ?? '');
    $imie = trim($data[3] ?? '');

    if (empty($email) && empty($pesel)) {
        continue;
    }

    // Szukaj po email lub PESEL
    $user = null;

    if (!empty($email)) {
        $user = $userRepository->findOneBy(['email' => $email]);
    }

    if (!$user && !empty($pesel)) {
        $user = $userRepository->findOneBy(['pesel' => $pesel]);
    }

    if (!$user) {
        $notFound++;
        $errors[] = sprintf(
            'Nie znaleziono: %s %s (email: %s, PESEL: %s)',
            $imie,
            $nazwisko,
            $email ?: 'brak',
            $pesel ?: 'brak'
        );
        continue;
    }

    // Sprawdź czy użytkownik już ma rolę
    if (in_array('ROLE_SEKRETARZ_REGIONU', $user->getRoles(), true)) {
        $alreadyHas++;
        echo "⚠️  {$user->getImie()} {$user->getNazwisko()} już ma rolę\n";
        continue;
    }

    // Dodaj rolę
    $roles = $user->getRoles();
    $roles[] = 'ROLE_SEKRETARZ_REGIONU';
    $user->setRoles(array_unique($roles));

    $entityManager->persist($user);
    $updated++;

    echo "✓ {$user->getImie()} {$user->getNazwisko()} ({$user->getEmail()})\n";
}

fclose($handle);

// Zapisz zmiany
if ($updated > 0) {
    $entityManager->flush();
}

echo "\n========================================\n";
echo "✅ Zakończono!\n";
echo "   Zaktualizowano: {$updated}\n";
echo "   Już posiadali rolę: {$alreadyHas}\n";
echo "   Nie znaleziono: {$notFound}\n\n";

if (!empty($errors)) {
    echo "⚠️  Nie znaleziono w bazie:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
}
