<?php
require_once 'config.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: auth/login.php");
    exit();
}

// Redireciona com base no perfil do usuário
if ($_SESSION['perfil'] === 'admin') {
    header("Location: admin/index.php");
    exit();
} elseif ($_SESSION['perfil'] === 'requisitante') {
    header("Location: requisitante/index.php");
    exit();
} else {
    // Se o perfil for desconhecido, faz logout por segurança
    header("Location: auth/logout.php");
    exit();
}
?>