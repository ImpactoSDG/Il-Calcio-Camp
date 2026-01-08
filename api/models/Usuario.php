<?php

class Usuario
{
    private PDO $conn;
    public string $table = 'usuario';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getAll(): array
    {
        $sql = "SELECT u.id, u.nombre, u.email, u.id_rol, r.nombre AS rol_nombre, r.descripcion AS rol_descripcion
                FROM {$this->table} u
                LEFT JOIN rol r ON u.id_rol = r.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT id, nombre, email, id_rol FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }


    public function getByNameOrEmail(string $identificador): ?array
    {
        $sql = "SELECT u.id, u.nombre, u.email, u.contrasena, u.id_rol, r.nombre AS rol_nombre
                FROM {$this->table} u
                LEFT JOIN rol r ON r.id = u.id_rol
                WHERE u.nombre = :identificador OR u.email = :identificador
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':identificador', $identificador);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    

    public function getByEmail(string $email): ?array
    {
        $sql = "SELECT id, nombre, email, contrasena, id_rol FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null; 
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        if ($excludeId) {
            $stmt->bindValue(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }


    public function getModulos(int $idUsuario): array
    {
        $sql = "SELECT 
                    m.id, 
                    m.nombre, 
                    m.ruta, 
                    m.id_padre, 
                    m.orden_visualizacion, 
                    m.categoria
                FROM usuario_modulo um
                INNER JOIN modulo m ON m.id = um.id_modulo
                WHERE um.id_usuario = :id
                ORDER BY m.categoria, m.orden_visualizacion ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function register(string $nombre, string $email, string $contrasena_hash, int $idRol): int|false
    {
        $sql = "INSERT INTO {$this->table} (nombre, email, contrasena, id_rol)
                VALUES (:nombre, :email, :contrasena, :id_rol)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':contrasena', $contrasena_hash);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function update(int $id, string $nombre, string $email, int $idRol): bool
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre, email = :email, id_rol = :id_rol
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updatePassword(int $id, string $nueva_contrasena_hash): bool
    {
        $sql = "UPDATE {$this->table} SET contrasena = :contrasena WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':contrasena', $nueva_contrasena_hash);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function togglePermiso(int $idUsuario, int $idModulo, bool $estado): bool
    {
        if ($estado) {
            $sql = "INSERT IGNORE INTO usuario_modulo (id_usuario, id_modulo) VALUES (:idu, :idm)";
        } else {
            $sql = "DELETE FROM usuario_modulo WHERE id_usuario = :idu AND id_modulo = :idm";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':idu', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':idm', $idModulo, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getListaModulosCompleta(): array
    {
        $sql = "SELECT id, nombre, id_padre, orden_visualizacion, categoria 
                FROM modulo 
                ORDER BY categoria ASC, COALESCE(id_padre, id), id_padre IS NOT NULL, orden_visualizacion ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMapaPermisos(): array
    {
        $sql = "SELECT CONCAT(id_usuario, '_', id_modulo) as llave FROM usuario_modulo";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}