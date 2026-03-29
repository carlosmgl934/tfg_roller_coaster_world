<?php

/**
 * Carga variables de entorno desde .env (ruta ajustada para tu estructura)
 * .env está en RollerCoasterWorld/.env
 */
function loadEnv()
{
    // Intentar encontrar el .env subiendo niveles desde /api/database/
    $envPath = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . '.env';
    
    // Si no está en RollerCoasterWorld, probar un nivel más arriba (tfg_roller_coaster_world)
    if (!file_exists($envPath)) {
        $envPath = dirname(__FILE__, 4) . DIRECTORY_SEPARATOR . '.env';
    }

    if (!file_exists($envPath)) {
        die("Archivo .env no encontrado. Buscado en: " . dirname(__FILE__, 3) . " y " . dirname(__FILE__, 4));
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
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => "Error al conectar a Supabase: " . $e->getMessage()]);
            exit;
        }
    }

    public function query($sql)
    {
        return $this->conn->query($sql);
    }

    public function prepare($sql)
    {
        return $this->conn->prepare($sql);
    }

    public function execute($stmt, $params = [])
    {
        $stmt->execute($params);
        return $stmt;
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

    public function inTransaction()
    {
        return $this->conn->inTransaction();
    }

    public function close()
    {
        $this->conn = null;
    }
}