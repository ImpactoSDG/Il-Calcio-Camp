<?php

declare(strict_types=1);

class UsuarioWeb
{
    private PDO $conn;
    public string $table = 'usuario_web';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT id, email, password_hash, activo, ultimo_acceso, creado_en
                FROM {$this->table}
                WHERE email = :email
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, email, activo, ultimo_acceso, creado_en
                FROM {$this->table}
                WHERE id = :id
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(string $email, string $passwordHash): int|false
    {
        $sql = "INSERT INTO {$this->table} (email, password_hash) VALUES (:email, :password_hash)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password_hash', $passwordHash);
        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    public function vincularJugador(int $idUsuarioWeb, int $idJugador): bool
    {
        $sql = "INSERT IGNORE INTO usuario_web_jugador (id_usuario_web, id_jugador)
                VALUES (:id_usuario_web, :id_jugador)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_usuario_web', $idUsuarioWeb, PDO::PARAM_INT);
        $stmt->bindValue(':id_jugador', $idJugador, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
