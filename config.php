<?php
// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_PORT', '3306'); // <-- ADICIONE ESTA LINHA PARA A PORTA
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'escola_enfermagem_estoque');

// Habilitar exibição de erros para depuração
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Conexão com o banco de dados
try {
    // MODIFIQUE A LINHA ABAIXO para incluir a porta na string de conexão
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERRO: Não foi possível conectar ao banco de dados. " . $e->getMessage());
}

/**
 * Registra uma ação no log de auditoria.
 * @param PDO $pdo Objeto de conexão com o banco.
 * @param int $id_usuario ID do usuário que realizou a ação.
 * @param string $acao Descrição da ação.
 * @param string $detalhes Detalhes adicionais.
 */
function registrar_log($pdo, $id_usuario, $acao, $detalhes = '')
{
    try {
        $sql = "INSERT INTO logs_auditoria (id_usuario, acao, detalhes) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario, $acao, $detalhes]);
    } catch (PDOException $e) {
        error_log('Erro ao registrar log de auditoria: ' . $e->getMessage());
    }
}

// Função de verificação de login
function check_login($required_profile) {
    if (!isset($_SESSION['id_usuario']) || $_SESSION['perfil'] !== $required_profile) {
        // Registrar tentativa de acesso indevido
        if (isset($_SESSION['id_usuario'])) {
            global $pdo;
            registrar_log($pdo, $_SESSION['id_usuario'], 'Acesso Negado', 'Tentativa de acesso à área: ' . $required_profile);
        }
        header("Location: /sistema_estoque/auth/login.php");
        exit();
    }
}
?>