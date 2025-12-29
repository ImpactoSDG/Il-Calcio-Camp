<?php
class Database
{
    private string $host = '67.225.220.9';
    private string $db_name = 'impactos_proyecto_base';
    private string $username = 'impactos_dev';
    private string $password = '1IO!WprsC2)XpL3I';
    public ?PDO $conn;

    public function connect(): PDO
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'message' => 'Error de Conexión a la Base de Datos',
                'error' => $e->getMessage()
            ]);
            exit;
        }

        return $this->conn;
    }
}
