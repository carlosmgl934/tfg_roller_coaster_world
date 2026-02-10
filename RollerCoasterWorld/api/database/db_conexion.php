<?php

class DBConexion
{

    private PDO $conn;
    public function __construct()
    {

        $env = parse_ini_file(__DIR__ . '/../../.env');

        $host = $env['DB_HOST'];
        $user = $env['DB_USER'];
        $password = $env['DB_PASS'];
        $dbname = $env['DB_NAME'];
        $destino = "mysql:host=$host;port={$env['DB_PORT']};dbname=$dbname;charset=utf8mb4";



        try {
            //Crear conexión PDO
            $this->conn = new PDO($destino, $user, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Error al conectar a la base de datos: " . $e->getMessage();
        }
    }

    public function getConexion(): PDO
    {
        return $this->conn;
    }

    public function query($sql)
    {
        try {
            $result = $this->conn->query($sql);
            return $result;
        } catch (PDOException $e) {
            echo "Error al ejecutar la consulta: " . $e->getMessage();
        }
    }

    public function prepare($sql)
    {
        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt;
        } catch (PDOException $e) {
            echo "Error al preparar la consulta: " . $e->getMessage();
        }
    }

    public function execute($stmt, $params = [])
    {
        try {
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            echo "Error al ejecutar la consulta: " . $e->getMessage();
        }
    }
}

?>