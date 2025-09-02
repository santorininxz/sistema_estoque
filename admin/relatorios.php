<?php
require_once '../config.php';
check_login('admin');

if (isset($_GET['gerar_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_materiais.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Nome', 'Categoria', 'Quantidade em Estoque', 'Status']);

    $stmt = $pdo->query("SELECT id_material, nome, categoria, quantidade, status FROM materiais ORDER BY nome");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['status'] = $row['status'] ? 'Ativo' : 'Inativo';
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

require_once '../templates/header.php';
?>

<h2>Relatórios</h2>
<p>Selecione um tipo de relatório para gerar.</p>

<div class="relatorio-item">
    <h3>Relatório de Materiais em Estoque</h3>
    <p>Gera um arquivo CSV com a lista completa de materiais e suas quantidades atuais.</p>
    <a href="relatorios.php?gerar_csv=true" class="btn">Gerar Relatório CSV</a>
</div>

<!-- Outros relatórios podem ser adicionados aqui -->

<?php require_once '../templates/footer.php'; ?>