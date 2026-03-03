<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../migrate.php';

class MigrationTest extends TestCase
{
    private string $tmpDir;
    private string $tmpJson;
    private PDO $pdo;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'ma_bdd';
        $user = getenv('DB_USER') ?: 'db_user';
        $pass = getenv('DB_PASS') ?: 'db_pwd';

        $this->pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user, $pass
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Dossier temporaire isolé pour chaque test
        $this->tmpDir  = sys_get_temp_dir() . '/migrations_test_' . uniqid();
        $this->tmpJson = $this->tmpDir . '/migrations.json';
        mkdir($this->tmpDir . '/migrations', 0777, true);
    }

    protected function tearDown(): void
    {
        // Nettoyage fichiers temporaires
        array_map('unlink', glob($this->tmpDir . '/migrations/*.sql'));
        @unlink($this->tmpJson);
        @rmdir($this->tmpDir . '/migrations');
        @rmdir($this->tmpDir);

        // Nettoyage table de test
        $this->pdo->exec("DROP TABLE IF EXISTS migration_test_table");
    }

    public function testCreationFichierJsonSiAbsent(): void
    {
        runMigrations($this->pdo, $this->tmpDir);
        $this->assertFileExists($this->tmpJson);
    }

    public function testJsonInitialContientTableauVide(): void
    {
        runMigrations($this->pdo, $this->tmpDir);
        $data = json_decode(file_get_contents($this->tmpJson), true);
        $this->assertArrayHasKey('migrations', $data);
        $this->assertIsArray($data['migrations']);
    }

    public function testMigrationEstExecutee(): void
    {
        file_put_contents(
            $this->tmpDir . '/migrations/001_test.sql',
            'CREATE TABLE IF NOT EXISTS migration_test_table (id INT PRIMARY KEY AUTO_INCREMENT);'
        );

        runMigrations($this->pdo, $this->tmpDir);

        $result = $this->pdo->query("SHOW TABLES LIKE 'migration_test_table'")->fetchAll();
        $this->assertNotEmpty($result);
    }

    public function testMigrationNonReexecutee(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS migration_test_table (id INT PRIMARY KEY AUTO_INCREMENT);';
        file_put_contents($this->tmpDir . '/migrations/001_test.sql', $sql);

        runMigrations($this->pdo, $this->tmpDir); // 1ère exécution
        runMigrations($this->pdo, $this->tmpDir); // 2ème — ne doit pas planter

        $data = json_decode(file_get_contents($this->tmpJson), true);
        $ids  = array_column($data['migrations'], 'id');
        $this->assertCount(1, array_keys($ids, '001_test'));
    }

    public function testMigrationEnregistreeDateExecution(): void
    {
        file_put_contents(
            $this->tmpDir . '/migrations/001_test.sql',
            'CREATE TABLE IF NOT EXISTS migration_test_table (id INT PRIMARY KEY);'
        );

        runMigrations($this->pdo, $this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpJson), true);
        $this->assertArrayHasKey('executed_at', $data['migrations'][0]);
    }

    public function testOrdreExecutionMigrations(): void
    {
        file_put_contents($this->tmpDir . '/migrations/002_b.sql', 'SET @test_b = 2;');
        file_put_contents($this->tmpDir . '/migrations/001_a.sql', 'SET @test_a = 1;');

        runMigrations($this->pdo, $this->tmpDir);

        $data = json_decode(file_get_contents($this->tmpJson), true);
        $ids  = array_column($data['migrations'], 'id');

        $this->assertEquals('001_a', $ids[0]);
        $this->assertEquals('002_b', $ids[1]);
    }
}