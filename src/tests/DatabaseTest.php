<?php

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'ma_bdd';
        $user = getenv('DB_USER') ?: 'db_user';
        $pass = getenv('DB_PASS') ?: 'db_pwd';

        self::$pdo = new PDO(
            "mysql:host=$host;dbname=$db;charset=utf8mb4",
            $user, $pass
        );
        self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        require_once __DIR__ . '/../migrate.php';
        runMigrations(self::$pdo, __DIR__ . '/..');
    }

    // --- Connexion ---

    public function testConnexionReussie(): void
    {
        $this->assertInstanceOf(PDO::class, self::$pdo);
    }

    public function testConnexionEchoueAvecMauvaisMotDePasse(): void
    {
        $this->expectException(PDOException::class);
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'ma_bdd';
        new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", 'wrong_user', 'wrong_pass');
    }

    // --- Table ---

    public function testTableDbTableExiste(): void
    {
        $result = self::$pdo->query("SHOW TABLES LIKE 'db_table'")->fetchAll();
        $this->assertNotEmpty($result, "La table db_table doit exister");
    }

    public function testStructureTable(): void
    {
        $columns = self::$pdo->query("DESCRIBE db_table")->fetchAll();
        $names = array_column($columns, 'Field');

        $this->assertContains('id', $names);
        $this->assertContains('text', $names);
    }

    public function testColonneIdEstPrimaryKey(): void
    {
        $columns = self::$pdo->query("DESCRIBE db_table")->fetchAll();
        $id = array_filter($columns, fn($c) => $c['Field'] === 'id');
        $id = array_values($id)[0];

        $this->assertEquals('PRI', $id['Key']);
        $this->assertEquals('auto_increment', $id['Extra']);
    }

    public function testColonneTextNonNullable(): void
    {
        $columns = self::$pdo->query("DESCRIBE db_table")->fetchAll();
        $text = array_filter($columns, fn($c) => $c['Field'] === 'text');
        $text = array_values($text)[0];

        $this->assertEquals('NO', $text['Null']);
    }

    // --- CRUD ---

    public function testInsertionLigne(): void
    {
        $stmt = self::$pdo->prepare("INSERT INTO db_table (text) VALUES (:text)");
        $stmt->execute([':text' => 'test_phpunit']);

        $id = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, $id);

        // Nettoyage
        self::$pdo->prepare("DELETE FROM db_table WHERE id = ?")->execute([$id]);
    }

    public function testLectureApresInsertion(): void
    {
        $stmt = self::$pdo->prepare("INSERT INTO db_table (text) VALUES (:text)");
        $stmt->execute([':text' => 'lecture_test']);
        $id = self::$pdo->lastInsertId();

        $row = self::$pdo->prepare("SELECT * FROM db_table WHERE id = ?");
        $row->execute([$id]);
        $result = $row->fetch();

        $this->assertEquals('lecture_test', $result['text']);

        // Nettoyage
        self::$pdo->prepare("DELETE FROM db_table WHERE id = ?")->execute([$id]);
    }

    public function testMiseAJourLigne(): void
    {
        $stmt = self::$pdo->prepare("INSERT INTO db_table (text) VALUES (:text)");
        $stmt->execute([':text' => 'avant_update']);
        $id = self::$pdo->lastInsertId();

        self::$pdo->prepare("UPDATE db_table SET text = ? WHERE id = ?")->execute(['apres_update', $id]);

        $row = self::$pdo->prepare("SELECT text FROM db_table WHERE id = ?");
        $row->execute([$id]);
        $this->assertEquals('apres_update', $row->fetchColumn());

        // Nettoyage
        self::$pdo->prepare("DELETE FROM db_table WHERE id = ?")->execute([$id]);
    }

    public function testSuppressionLigne(): void
    {
        $stmt = self::$pdo->prepare("INSERT INTO db_table (text) VALUES (:text)");
        $stmt->execute([':text' => 'a_supprimer']);
        $id = self::$pdo->lastInsertId();

        self::$pdo->prepare("DELETE FROM db_table WHERE id = ?")->execute([$id]);

        $row = self::$pdo->prepare("SELECT * FROM db_table WHERE id = ?");
        $row->execute([$id]);
        $this->assertFalse($row->fetch());
    }

    public function testInsertionTexteVide(): void
    {
        $this->expectException(PDOException::class);
        self::$pdo->prepare("INSERT INTO db_table (text) VALUES (?)")->execute([null]);
    }

    public function testInsertionTexteTropLong(): void
    {
        $this->expectException(PDOException::class);
        $tooLong = str_repeat('a', 101); // VARCHAR(100) max
        self::$pdo->prepare("INSERT INTO db_table (text) VALUES (?)")->execute([$tooLong]);
    }

    public function testSelectRetourneTableau(): void
    {
        $rows = self::$pdo->query("SELECT id, text FROM db_table")->fetchAll();
        $this->assertIsArray($rows);
    }

    public function testDonneesInitialesPresentes(): void
    {
        $rows = self::$pdo->query("SELECT text FROM db_table")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('azerty', $rows);
        $this->assertContains('abcdef', $rows);
    }
}