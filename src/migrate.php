<?php
function runMigrations(PDO $pdo, string $baseDir = null): void
{
    $baseDir  = $baseDir ?? __DIR__;
    $jsonFile = $baseDir . '/migrations.json';

    if (!file_exists($jsonFile)) {
        file_put_contents($jsonFile, json_encode(['migrations' => []], JSON_PRETTY_PRINT));
    }

    $data     = json_decode(file_get_contents($jsonFile), true);
    $executed = array_column($data['migrations'], 'id');

    $files = glob($baseDir . '/migrations/*.sql');
    sort($files);

    foreach ($files as $file) {
        $migrationId = pathinfo($file, PATHINFO_FILENAME);

        if (!in_array($migrationId, $executed)) {
            $sql = file_get_contents($file);
            try {
                $pdo->exec($sql);
                $data['migrations'][] = [
                    'id'          => $migrationId,
                    'executed_at' => date('Y-m-d H:i:s'),
                ];
                echo "Migration appliquée : $migrationId" . PHP_EOL;
            } catch (PDOException $e) {
                echo "Échec migration $migrationId : " . $e->getMessage() . PHP_EOL;
                break;
            }
        }
    }

    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
}