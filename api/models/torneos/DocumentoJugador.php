<?php

declare(strict_types=1);

class DocumentoJugador
{
    private PDO $conn;
    public string $table = 'documento_jugador';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function crear(int $idJugador, string $nombreArchivo): int|false
    {
        $sql = "INSERT INTO {$this->table} (id_jugador, url) VALUES (:id_jugador, :url)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->bindValue(':url', $nombreArchivo);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function existeParaJugador(int $idJugador, string $nombreArchivo): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id_jugador = :id_jugador AND url = :url LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        $stmt->bindValue(':url', $nombreArchivo);
        $stmt->execute();
        return (bool)$stmt->fetch();
    }
}
