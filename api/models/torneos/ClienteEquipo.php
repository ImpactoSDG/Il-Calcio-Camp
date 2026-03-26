<?php

declare(strict_types=1);

class ClienteEquipo
{
    private PDO $conn;
    public string $table = 'cliente_equipo';
    private ?bool $hasCapitanColumn = null;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    /**
     * Obtiene todas las relaciones cliente-equipo
     */
    public function getAll(): array
    {
        $capitanSelect = $this->hasCapitanColumn()
            ? ', COALESCE(ce.capitan, 0) AS capitan'
            : ', 0 AS capitan';

        $sql = "SELECT ce.id_cliente_equipo, ce.id_cliente, ce.id_equipo,
                       c.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre, e.disciplina AS equipo_disciplina
                       {$capitanSelect}
                FROM {$this->table} ce
                INNER JOIN cliente c ON ce.id_cliente = c.id
                INNER JOIN equipo e ON ce.id_equipo = e.id
                ORDER BY c.nombre_cliente ASC, e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una relación por ID
     */
    public function getById(int $id): ?array
    {
        $capitanSelect = $this->hasCapitanColumn()
            ? ', COALESCE(ce.capitan, 0) AS capitan'
            : ', 0 AS capitan';

        $sql = "SELECT ce.id_cliente_equipo, ce.id_cliente, ce.id_equipo,
                       c.nombre_cliente AS cliente_nombre,
                       e.nombre AS equipo_nombre, e.disciplina AS equipo_disciplina
                       {$capitanSelect}
                FROM {$this->table} ce
                INNER JOIN cliente c ON ce.id_cliente = c.id
                INNER JOIN equipo e ON ce.id_equipo = e.id
                WHERE ce.id_cliente_equipo = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Obtiene equipos de un cliente
     */
    public function getByCliente(int $idCliente): array
    {
        $capitanSelect = $this->hasCapitanColumn()
            ? ', COALESCE(ce.capitan, 0) AS capitan'
            : ', 0 AS capitan';

        $sql = "SELECT ce.id_cliente_equipo, ce.id_equipo,
                       e.nombre AS equipo_nombre, e.disciplina AS equipo_disciplina, e.activo
                       {$capitanSelect}
                FROM {$this->table} ce
                INNER JOIN equipo e ON ce.id_equipo = e.id
                WHERE ce.id_cliente = :id_cliente
                ORDER BY e.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene clientes de un equipo
     */
    public function getByEquipo(int $idEquipo): array
    {
        $hasCapitan = $this->hasCapitanColumn();
        $capitanSelect = $hasCapitan
            ? ', COALESCE(ce.capitan, 0) AS capitan'
            : ', 0 AS capitan';
        $orderBy = $hasCapitan
            ? 'ORDER BY COALESCE(ce.capitan, 0) DESC, c.nombre_cliente ASC'
            : 'ORDER BY c.nombre_cliente ASC';

        $sql = "SELECT ce.id_cliente_equipo, ce.id_cliente,
                       c.nombre_cliente AS cliente_nombre
                       {$capitanSelect}
                FROM {$this->table} ce
                INNER JOIN cliente c ON ce.id_cliente = c.id
                WHERE ce.id_equipo = :id_equipo
                {$orderBy}";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva relación cliente-equipo
     */
    public function create(?int $id, int $idCliente, int $idEquipo, bool $capitan = false): bool
    {
        $hasCapitan = $this->hasCapitanColumn();

        if ($id) {
            if ($hasCapitan) {
                $sql = "INSERT INTO {$this->table} (id_cliente_equipo, id_cliente, id_equipo, capitan) 
                        VALUES (:id, :id_cliente, :id_equipo, :capitan)";
            } else {
                $sql = "INSERT INTO {$this->table} (id_cliente_equipo, id_cliente, id_equipo) 
                        VALUES (:id, :id_cliente, :id_equipo)";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            if ($hasCapitan) {
                $sql = "INSERT INTO {$this->table} (id_cliente, id_equipo, capitan) 
                        VALUES (:id_cliente, :id_equipo, :capitan)";
            } else {
                $sql = "INSERT INTO {$this->table} (id_cliente, id_equipo) 
                        VALUES (:id_cliente, :id_equipo)";
            }

            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->bindValue(':id_cliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindValue(':id_equipo', $idEquipo, PDO::PARAM_INT);
        if ($hasCapitan) {
            $stmt->bindValue(':capitan', $capitan ? 1 : 0, PDO::PARAM_INT);
        }
        return $stmt->execute();
    }

    private function hasCapitanColumn(): bool
    {
        if ($this->hasCapitanColumn !== null) {
            return $this->hasCapitanColumn;
        }

        try {
            $stmt = $this->conn->query("SHOW COLUMNS FROM {$this->table} LIKE 'capitan'");
            $this->hasCapitanColumn = $stmt !== false && (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            $this->hasCapitanColumn = false;
        }

        return $this->hasCapitanColumn;
    }

    /**
     * Elimina una relación cliente-equipo
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id_cliente_equipo = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
