<?php

/**
 * Classe Database (Singleton)
 * Garantit une seule connexion PDO à la base SQLite pour toute l'application.
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    // Le constructeur est privé : impossible de faire "new Database()" depuis l'extérieur
    private function __construct(string $sqlitePath)
    {
        try {
            $this->pdo = new PDO('sqlite:' . $sqlitePath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->ensureSchema();
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    /**
     * Si le fichier SQLite est neuf ou vide (aucune table), on rejoue le
     * schéma complet pour que le site ne plante jamais faute de tables
     * (ex: premier déploiement sur un serveur avec un fichier vide).
     */
    private function ensureSchema(): void
    {
        $table = $this->pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'cv_versions'")->fetch();

        if ($table) {
            return;
        }

        $schemaPath = __DIR__ . '/../migrations/000_schema.sql';
        if (!is_file($schemaPath)) {
            return;
        }

        $this->pdo->exec(file_get_contents($schemaPath));
    }

    // Empêche le clonage de l'instance
    private function __clone() {}

    // Empêche la désérialisation (qui créerait une 2e instance)
    public function __wakeup()
    {
        throw new \Exception("Impossible de désérialiser un singleton.");
    }

    /**
     * Point d'accès unique à l'instance.
     * Le chemin n'est utilisé que lors de la toute première création.
     */
    public static function getInstance(string $cheminSqlite = __DIR__ . '/ma_base.sqlite'): Database
    {
        if (self::$instance === null) {
            self::$instance = new self($cheminSqlite);
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Raccourci : exécute une requête préparée et retourne le PDOStatement.
     * Usage : $db->query("SELECT * FROM t WHERE id = :id", ['id' => 5])->fetchAll();
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}