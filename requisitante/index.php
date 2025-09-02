<?php
require_once '../templates/header.php';
check_login('requisitante');
?>

<h2>Painel do Requisitante</h2>
<p>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome']); ?>!</p>
<p>Use os links abaixo para gerenciar suas solicitações de materiais.</p>

<div class="dashboard-actions">
    <a href="criar_solicitacao.php" class="action-card">
        <h3>Criar Nova Solicitação</h3>
        <p>Peça os materiais necessários para suas atividades.</p>
    </a>
    <a href="historico.php" class="action-card">
        <h3>Minhas Solicitações</h3>
        <p>Consulte o status e o histórico de todos os seus pedidos.</p>
    </a>
</div>

<style>
.dashboard-actions {
    margin-top: 30px;
    display: flex;
    gap: 20px;
}
.action-card {
    background-color: #e9f7ff;
    border: 1px solid #b3e0ff;
    border-radius: 8px;
    padding: 20px;
    text-decoration: none;
    color: #333;
    flex: 1;
    transition: transform 0.2s, box-shadow 0.2s;
}
.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.action-card h3 {
    margin-top: 0;
    color: #0056b3;
}
</style>

<?php require_once '../templates/footer.php'; ?>