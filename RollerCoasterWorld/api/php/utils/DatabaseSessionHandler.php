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
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = :id");
        $this->db->execute($stmt, [':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row['data'];
        }
        return '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO sessions (id, data, last_accessed)
            VALUES (:id, :data, CURRENT_TIMESTAMP)
            ON CONFLICT (id) DO UPDATE SET
            data = EXCLUDED.data,
            last_accessed = CURRENT_TIMESTAMP
        ");

        $this->db->execute($stmt, [
            ':id' => $id,
            ':data' => $data
        ]);

        return true;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = :id");
        $this->db->execute($stmt, [':id' => $id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE last_accessed < (CURRENT_TIMESTAMP - :lifetime * interval '1 second')");
        $this->db->execute($stmt, [':lifetime' => $max_lifetime]);
        return $stmt->rowCount();
    }
}
