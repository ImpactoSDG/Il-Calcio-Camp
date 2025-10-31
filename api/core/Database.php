<?php
class Database
{
    private $host = '67.225.220.9';
    private $db_name = 'impactos_proyecto_base';
    private $username = 'impactos_dev';
    private $password = '1IO!WprsC2)XpL3I';
    public $conn;

    public function connect()
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
