<?php
require_once '../templates/header.php';
check_login('admin');

try {
    // Contagem de solicitações pendentes
    $stmt_pendentes = $pdo->query("SELECT COUNT(*) FROM solicitacoes WHERE status = 'pendente'");
    $solicitacoes_pendentes = $stmt_pendentes->fetchColumn();

    // Contagem de materiais com estoque baixo
    $stmt_estoque_baixo = $pdo->query("SELECT COUNT(*) FROM materiais WHERE quantidade < quantidade_min AND status = 1");
    $estoque_baixo = $stmt_estoque_baixo->fetchColumn();

    // Contagem de usuários ativos
    $stmt_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE status = 1");
    $usuarios_ativos = $stmt_usuarios->fetchColumn();

} catch (PDOException $e) {
    echo "<p class='error'>Erro ao carregar dados do painel: " . htmlspecialchars($e->getMessage()) . "</p>";
    // Definir valores padrão em caso de erro
    $solicitacoes_pendentes = $estoque_baixo = $usuarios_ativos = 'N/A';
}
?>

<h2>Painel do Administrador</h2>
<p>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome']); ?>!</p>

<div class="dashboard-widgets">
    <div class="widget">
        <h3>Solicitações Pendentes</h3>
        <p class="widget-value"><?php echo $solicitacoes_pendentes; ?></p>
        <a href="gerenciar_solicitacoes.php" class="widget-link">Ver Solicitações</a>
    </div>
    <div class="widget">
        <h3>Itens com Estoque Baixo</h3>
        <p class="widget-value"><?php echo $estoque_baixo; ?></p>
        <a href="gerenciar_materiais.php" class="widget-link">Ver Materiais</a>
    </div>
    <div class="widget">
        <h3>Usuários Ativos</h3>
        <p class="widget-value"><?php echo $usuarios_ativos; ?></p>
        <a href="gerenciar_usuarios.php" class="widget-link">Ver Usuários</a>
    </div>
</div>

<style>
.dashboard-widgets {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
}
.widget {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    flex-grow: 1;
    min-width: 250px;
    text-align: center;
}
.widget h3 {
    margin-top: 0;
}
.widget-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #007bff;
    margin: 10px 0;
}
.widget-link {
    text-decoration: none;
    color: #007bff;
    font-weight: bold;
}
</style>

<?php require_once '../templates/footer.php'; ?>