<?php

class Modulo
{
    private PDO $conn;
    public string $table = 'modulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getModulosEstructurados(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY COALESCE(id_padre, id), id_padre IS NOT NULL, orden_visualizacion";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $modulos;
    }
}