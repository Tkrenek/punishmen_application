<?php

/**
 * Import dat z source.txt do MySQL databáze Punishment Application.
 *
 * Spuštění: php db/import/import_source.php
 *
 * Pravidla:
 * - Rok start: září 2023. Rok +1 při zpětném / velkém skoku měsíce.
 * - Závorky (X Kč) → fund_transactions, type=withdrawal
 * - Kladné nestandardní částky (≠ 20Kč) s nestandardním popisem → fund_transactions, type=bonus
 * - "- Kč" → 20.00 Kč
 * - Prázdné zaplaceno → is_paid = 0
 * - Sjednocení: "neomluvená účast na daily" + "neomluvený pozdní příchod" → "neomluvená absence"
 * - Iniciála TEAM nebo prázdná → fund_transaction bez user_id
 * - Iniciála "-" (počáteční stav) → fund_transaction bonus
 */

declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$sourceFile = $rootDir . '/source.txt';
$neonFile   = $rootDir . '/config/local.neon';

// --- Načtení DB konfigurace z local.neon ---
if (!file_exists($neonFile)) {
    die("ERROR: Chybí config/local.neon. Zkopírujte config/local.neon.example\n");
}

$neonContent = file_get_contents($neonFile);
preg_match("/dsn:\s+'([^']+)'/", $neonContent, $dsnMatch);
preg_match("/user:\s+(\S+)/", $neonContent, $userMatch);
preg_match("/password:\s+'?([^'\n]*)'?/", $neonContent, $passMatch);

$dsn      = $dsnMatch[1]  ?? null;
$dbUser   = $userMatch[1]  ?? null;
$dbPass   = trim($passMatch[1] ?? '');

if (!$dsn || !$dbUser) {
    die("ERROR: Nepodařilo se načíst DB konfiguraci z local.neon\n");
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]);
} catch (PDOException $e) {
    die("ERROR: Připojení k DB selhalo: " . $e->getMessage() . "\n");
}

echo "✓ Připojeno k databázi\n";

// --- Načtení seed dat z DB ---
$usersMap       = loadMap($pdo, 'users', 'initials', 'id');
$penaltyTypesMap = loadMap($pdo, 'penalty_types', 'name', 'id');

// --- Typy pokut a jejich normalizace ---
$penaltyTypeNormalization = [
    'chybějící výkazy vzhledem k docházce při kontrole' => 'chybějící výkazy vzhledem k docházce při kontrole',
    'nepřeplánovaný červený sloupec při kontrole'       => 'nepřeplánovaný červený sloupec při kontrole',
    'nepřeplánovaný červený sloupec'                    => 'nepřeplánovaný červený sloupec při kontrole',
    'neaktualizovaná planning tabulka při kontrole'     => 'neaktualizovaná planning tabulka při kontrole',
    'neomluvená účast na daily'                         => 'neomluvená absence',
    'neomluvený pozdní příchod'                         => 'neomluvená absence',
];

// --- Zpracování source.txt ---
$handle = fopen($sourceFile, 'r');
if (!$handle) {
    die("ERROR: Nelze otevřít $sourceFile\n");
}

$lineNum         = 0;
$currentYear     = 2023;
$prevMonth       = null;
$importedPenalties = 0;
$importedFund      = 0;
$skipped           = 0;

while (($line = fgets($handle)) !== false) {
    $lineNum++;

    // Přeskočit záhlaví
    if ($lineNum === 1) {
        continue;
    }

    $line   = rtrim($line, "\r\n");
    $cols   = explode("\t", $line);

    if (count($cols) < 4) {
        $skipped++;
        continue;
    }

    [$rawDate, $rawInitials, $rawAmount, $rawPaid] = $cols;
    $rawNote = trim($cols[4] ?? '');

    $rawDate     = trim($rawDate);
    $rawInitials = trim($rawInitials);
    $rawAmount   = trim($rawAmount);
    $rawPaid     = trim($rawPaid);

    if ($rawDate === '' || !preg_match('/^\d{1,2}\.\d{1,2}$/', $rawDate)) {
        $skipped++;
        continue;
    }

    // --- Parsování data + rok ---
    [$day, $month] = explode('.', $rawDate);
    $day   = (int) $day;
    $month = (int) $month;

    if ($prevMonth !== null && $month < $prevMonth - 3) {
        $currentYear++;
    }
    $prevMonth = $month;

    $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $month, $day);

    // --- Parsování částky ---
    $isWithdrawal = false;
    $amount       = null;

    if (preg_match('/^\((.+)\)$/', $rawAmount, $m)) {
        // Závorka = výdaj
        $isWithdrawal = true;
        $amount       = parseAmount($m[1]);
    } elseif ($rawAmount === '- Kč' || $rawAmount === '-' || trim($rawAmount) === '-') {
        $amount = 20.00;
    } else {
        $amount = parseAmount($rawAmount);
    }

    if ($amount === null) {
        $skipped++;
        continue;
    }

    // --- Zaplaceno ---
    $isPaid = ($rawPaid === '1') ? 1 : 0;

    // --- Iniciála ---
    $initials = strtoupper($rawInitials);

    // ============================================================
    // Rozhodnutí: fund_transaction nebo penalty
    // ============================================================

    $isFundTransaction = false;

    // 1. Výdaj (závorky)
    if ($isWithdrawal) {
        $isFundTransaction = true;
        $txType = 'withdrawal';
    }
    // 2. Inicála "-", TEAM nebo prázdná → fond
    elseif ($initials === '-' || $initials === '' || $initials === 'TEAM') {
        $isFundTransaction = true;
        $txType = 'bonus'; // počáteční stav a podobné
    }
    // 3. Standardní typ pokuty?
    else {
        $normalizedNote = normalizeNote($rawNote, $penaltyTypeNormalization);
        if ($normalizedNote === null) {
            // Není to standardní typ pokuty → bonus
            $isFundTransaction = true;
            $txType = 'bonus';
        }
    }

    // ============================================================
    // Vložení do DB
    // ============================================================

    if ($isFundTransaction) {
        // Uživatel (může být null)
        $userId = null;
        if ($initials !== '-' && $initials !== '' && $initials !== 'TEAM') {
            $userId = ensureUser($pdo, $initials, $usersMap);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO fund_transactions (user_id, entry_date, amount, description, transaction_type)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $dateStr, $amount, $rawNote !== '' ? $rawNote : 'bez popisu', $txType]);
        $importedFund++;
    } else {
        // Standardní pokuta
        $normalizedNote = normalizeNote($rawNote, $penaltyTypeNormalization);
        $penaltyTypeName = $normalizedNote;

        // Zajistit existenci uživatele
        $userId = ensureUser($pdo, $initials, $usersMap);

        // Zajistit existenci typu pokuty
        $penaltyTypeId = ensurePenaltyType($pdo, $penaltyTypeName, $penaltyTypesMap);

        $stmt = $pdo->prepare(
            'INSERT INTO penalties (user_id, penalty_type_id, amount, penalty_date, is_paid, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $penaltyTypeId, $amount, $dateStr, $isPaid, $rawNote !== '' ? $rawNote : null]);
        $importedPenalties++;
    }
}

fclose($handle);

echo "✓ Import dokončen:\n";
echo "  Pokuty:          $importedPenalties\n";
echo "  Fond transakce:  $importedFund\n";
echo "  Přeskočeno:      $skipped\n";

// ============================================================
// Pomocné funkce
// ============================================================

function parseAmount(string $raw): ?float
{
    // Odstranit " Kč", mezery jako oddělovač tisíců, nahradit čárku tečkou
    $raw = preg_replace('/\s*Kč\s*$/', '', trim($raw));
    $raw = preg_replace('/\s+/', '', $raw);  // odstranit mezery (oddělovač tisíců)
    $raw = str_replace(',', '.', $raw);

    if (!is_numeric($raw)) {
        return null;
    }

    return abs((float) $raw);
}

function normalizeNote(string $note, array $normMap): ?string
{
    $lower = mb_strtolower(trim($note), 'UTF-8');

    // Přesná shoda (case insensitive)
    foreach ($normMap as $key => $normalized) {
        if (mb_strtolower($key, 'UTF-8') === $lower) {
            return $normalized;
        }
    }

    // Částečná shoda (začíná hodnotou v mapě)
    foreach ($normMap as $key => $normalized) {
        if (str_contains($lower, mb_strtolower($key, 'UTF-8'))) {
            return $normalized;
        }
    }

    return null;
}

function ensureUser(PDO $pdo, string $initials, array &$usersMap): int
{
    if (!isset($usersMap[$initials])) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO users (initials) VALUES (?)');
        $stmt->execute([$initials]);
        $usersMap[$initials] = (int) $pdo->lastInsertId();

        if ($usersMap[$initials] === 0) {
            // Uživatel již existoval, načíst ID
            $row = $pdo->prepare('SELECT id FROM users WHERE initials = ?');
            $row->execute([$initials]);
            $usersMap[$initials] = (int) $row->fetchColumn();
        }
        echo "  + Nový uživatel: $initials\n";
    }
    return $usersMap[$initials];
}

function ensurePenaltyType(PDO $pdo, string $name, array &$typesMap): int
{
    if (!isset($typesMap[$name])) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO penalty_types (name, default_amount) VALUES (?, 20.00)');
        $stmt->execute([$name]);
        $typesMap[$name] = (int) $pdo->lastInsertId();

        if ($typesMap[$name] === 0) {
            $row = $pdo->prepare('SELECT id FROM penalty_types WHERE name = ?');
            $row->execute([$name]);
            $typesMap[$name] = (int) $row->fetchColumn();
        }
        echo "  + Nový typ pokuty: $name\n";
    }
    return $typesMap[$name];
}

function loadMap(PDO $pdo, string $table, string $keyCol, string $valCol): array
{
    $stmt = $pdo->query("SELECT `$keyCol`, `$valCol` FROM `$table`");
    $map  = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[$row[$keyCol]] = (int) $row[$valCol];
    }
    return $map;
}
