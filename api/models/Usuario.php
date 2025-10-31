<?php

class Usuario
{
    private $conn;
    public $table = 'usuario';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAll()
    {
        $sql = "SELECT u.id, u.nombre, u.email, r.nombre AS rol_nombre, r.descripcion AS rol_descripcion
                FROM {$this->table} u
                LEFT JOIN rol r ON u.id_rol = r.id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getByNameOrEmail($identificador)
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
    

    public function getByEmail($email)
    {
        $sql = "SELECT id, nombre, email, contrasena, id_rol FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function getModulos($idUsuario)
    {
        $sql = "SELECT m.id, m.nombre, m.ruta
                FROM usuario_modulo um
                INNER JOIN modulo m ON m.id = um.id_modulo
                WHERE um.id_usuario = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function register($nombre, $email, $contrasena_hash, $idRol)
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

    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function update($id, $nombre, $idRol)
    {
        $sql = "UPDATE {$this->table}
                SET nombre = :nombre, id_rol = :id_rol
                WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':id_rol', $idRol, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}