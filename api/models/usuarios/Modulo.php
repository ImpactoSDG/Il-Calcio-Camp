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
        $sql = "SELECT m.id, m.nombre, m.id_padre, m.orden_visualizacion, m.categoria, m.icon, m.bg
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

    //actualizar categoría, orden, icono y color
    public function updateProperties(int $id, ?string $categoria, ?int $orden, ?string $icon, ?string $bg): bool
    {
        $sql = "UPDATE {$this->table} 
                SET categoria = :categoria, 
                    orden_visualizacion = :orden,
                    icon = :icon,
                    bg = :bg
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':categoria', $categoria, PDO::PARAM_STR);
        $stmt->bindValue(':orden', $orden, PDO::PARAM_INT);
        $stmt->bindValue(':icon', $icon, PDO::PARAM_STR);
        $stmt->bindValue(':bg', $bg, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}