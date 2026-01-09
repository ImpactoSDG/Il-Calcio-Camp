<?php

declare(strict_types=1);

class Modulo
{
    private PDO $conn;
    private string $table = 'modulo';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getListaModulosCompleta(): array
    {
        // Usamos un LEFT JOIN para que el hijo sepa cuál es la categoría de su padre
        $sql = "SELECT m.id, m.nombre, m.id_padre, m.orden_visualizacion, m.categoria 
                FROM modulo m
                LEFT JOIN modulo p ON m.id_padre = p.id
                ORDER BY 
                    COALESCE(p.categoria, m.categoria) ASC, 
                    COALESCE(m.id_padre, m.id) ASC, 
                    m.id_padre IS NOT NULL, 
                    m.orden_visualizacion ASC";
                    
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //actualizar categoría y orden
    public function updateProperties(int $id, ?string $categoria, ?int $orden): bool
    {
        $sql = "UPDATE {$this->table} 
                SET categoria = :categoria, 
                    orden_visualizacion = :orden 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':categoria', $categoria, PDO::PARAM_STR);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}