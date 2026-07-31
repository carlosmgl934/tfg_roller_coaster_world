<?php

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private DBConexion $db;

    public function __construct(DBConexion $db)
    {
        $this->db = $db;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = :id");
            $this->db->execute($stmt, [':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['data'] : '';
        } catch (Throwable $e) {
            // Si Supabase no está disponible, devolvemos sesión vacía de forma segura
            error_log("[SessionHandler] read() ERROR: " . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO sessions (id, data, last_accessed)
                VALUES (:id, :data, CURRENT_TIMESTAMP)
                ON CONFLICT (id) DO UPDATE SET
                data = EXCLUDED.data,
                last_accessed = CURRENT_TIMESTAMP
            ");
            $this->db->execute($stmt, [':id' => $id, ':data' => $data]);
            return true;
        } catch (Throwable $e) {
            error_log("[SessionHandler] write() ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = :id");
            $this->db->execute($stmt, [':id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log("[SessionHandler] destroy() ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM sessions WHERE last_accessed < (CURRENT_TIMESTAMP - :lifetime * interval '1 second')"
            );
            $this->db->execute($stmt, [':lifetime' => $max_lifetime]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log("[SessionHandler] gc() ERROR: " . $e->getMessage());
            return false;
        }
    }
}
