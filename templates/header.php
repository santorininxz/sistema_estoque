<?php require_once __DIR__ . '/../config.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Estoque</title>
    <link rel="stylesheet" href="/sistema_estoque/assets/css/style.css">
</head>
<body>
    <header>
        <div class="header-container">
            <h1><a href="/sistema_estoque/">Sistema de Estoque</a></h1>
            
            <!-- Botão Hamburger para Mobile -->
            <button class="menu-toggle" aria-label="Abrir menu">☰</button>

            <!-- Adicionado ID "main-nav" para o JS -->
            <nav id="main-nav">
                <ul>
                    <?php if (isset($_SESSION['perfil'])): ?>
                        <?php if ($_SESSION['perfil'] === 'admin'): ?>
                            <li><a href="/sistema_estoque/admin/">Painel</a></li>
                            <li><a href="/sistema_estoque/admin/gerenciar_solicitacoes.php">Solicitações</a></li>
                            <li><a href="/sistema_estoque/admin/gerenciar_materiais.php">Materiais</a></li>
                            <li><a href="/sistema_estoque/admin/gerenciar_usuarios.php">Usuários</a></li>
                            <li><a href="/sistema_estoque/admin/relatorios.php">Relatórios</a></li>
                        <?php else: ?>
                            <li><a href="/sistema_estoque/requisitante/">Painel</a></li>
                            <li><a href="/sistema_estoque/requisitante/criar_solicitacao.php">Nova Solicitação</a></li>
                            <li><a href="/sistema_estoque/requisitante/historico.php">Minhas Solicitações</a></li>
                        <?php endif; ?>
                        <li><a href="/sistema_estoque/auth/logout.php">Sair</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="container">