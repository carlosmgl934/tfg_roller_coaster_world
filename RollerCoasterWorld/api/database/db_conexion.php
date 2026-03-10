<?php

/**
 * Carga variables de entorno desde .env (ruta ajustada para tu estructura)
 * .env está en RollerCoasterWorld/.env
 */
function loadEnv()
{
    // Desde /api/database/ → subimos 2 niveles hasta RollerCoasterWorld
    $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

    if (!file_exists($envPath)) {
        die("Archivo .env no encontrado en: " . $envPath . "<br>" .
            "Ruta esperada: C:\\xampp\\htdocs\\tfg\\tfg_roller_coaster_world\\RollerCoasterWorld\\.env");
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#'))
            continue;
        if (!str_contains($line, '='))
            continue;

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Quitar comillas si existen
        $value = trim($value, '"\'');

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

// Cargar solo una vez
if (!isset($_ENV['DB_HOST'])) {
    loadEnv();
}

class DBConexion
{
    private ?PDO $conn = null;

    public function __construct()
    {
        $host = $_ENV['DB_HOST'] ?? die('Falta DB_HOST en .env');
        $port = $_ENV['DB_PORT'] ?? '5432';
        $dbname = $_ENV['DB_NAME'] ?? 'postgres';
        $user = $_ENV['DB_USER'] ?? 'postgres';
        $password = $_ENV['DB_PASS'] ?? die('Falta DB_PASS en .env');

        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

        try {
            $this->conn = new PDO($dsn, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'utf8'");
            $this->conn->exec("SET TIME ZONE 'Europe/Madrid'");
        } catch (PDOException $e) {
            die("Error al conectar a Supabase (PostgreSQL): " . $e->getMessage());
        }
    }

    public function query($sql)
    {
        try {
            return $this->conn->query($sql);
        } catch (PDOException $e) {
            die("Error en query: " . $e->getMessage());
        }
    }

    public function prepare($sql)
    {
        try {
            return $this->conn->prepare($sql);
        } catch (PDOException $e) {
            die("Error al preparar: " . $e->getMessage());
        }
    }

    public function execute($stmt, $params = [])
    {
        try {
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Error al ejecutar: " . $e->getMessage());
        }
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollBack()
    {
        return $this->conn->rollBack();
    }

    public function close()
    {
        $this->conn = null;
    }
}