<?php
require_once '../templates/header.php';
check_login('admin');

$success_message = '';
$error_message = '';
$edit_material = null;

// Lógica para ADICIONAR ou EDITAR material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submit'])) {
    // Sanitização e validação
    $id_material = filter_input(INPUT_POST, 'id_material', FILTER_VALIDATE_INT);
    $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
    $codigo_interno = trim(filter_input(INPUT_POST, 'codigo_interno', FILTER_SANITIZE_STRING));
    $categoria = trim(filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_STRING));
    $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_STRING);
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
    $quantidade_min = filter_input(INPUT_POST, 'quantidade_min', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
    
    try {
        if ($id_material) { // ATUALIZAÇÃO
            $sql = "UPDATE materiais SET nome=?, codigo_interno=?, categoria=?, tipo=?, quantidade=?, quantidade_min=?, status=? WHERE id_material=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $codigo_interno, $categoria, $tipo, $quantidade, $quantidade_min, $status, $id_material]);
            registrar_log($pdo, $_SESSION['id_usuario'], 'Atualização de Material', "ID: {$id_material}");
            $success_message = 'Material atualizado com sucesso!';
        } else { // CRIAÇÃO
            $sql = "INSERT INTO materiais (nome, codigo_interno, categoria, tipo, quantidade, quantidade_min, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $codigo_interno, $categoria, $tipo, $quantidade, $quantidade_min, $status]);
            $new_id = $pdo->lastInsertId();
            registrar_log($pdo, $_SESSION['id_usuario'], 'Criação de Material', "ID: {$new_id}");
            $success_message = 'Material criado com sucesso!';
        }
    } catch (PDOException $e) {
        $error_message = 'Erro ao salvar material: ' . $e->getMessage();
    }
}

// Carregar dados para edição
if (isset($_GET['edit'])) {
    $id_edit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("SELECT * FROM materiais WHERE id_material = ?");
    $stmt->execute([$id_edit]);
    $edit_material = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar todos os materiais
$materiais = $pdo->query("SELECT * FROM materiais ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gerenciar Materiais</h2>

<?php if ($success_message): ?><p class="success"><?php echo htmlspecialchars($success_message); ?></p><?php endif; ?>
<?php if ($error_message): ?><p class="error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>

<div class="form-container">
    <h3><?php echo $edit_material ? 'Editar Material' : 'Adicionar Novo Material'; ?></h3>
    <form action="gerenciar_materiais.php" method="POST">
        <input type="hidden" name="form_submit" value="1">
        <input type="hidden" name="id_material" value="<?php echo $edit_material['id_material'] ?? ''; ?>">
        
        <div class="form-group">
            <label for="nome">Nome do Material</label>
            <input type="text" name="nome" value="<?php echo htmlspecialchars($edit_material['nome'] ?? ''); ?>" required>
        </div>
        <!-- Outros campos do formulário (código, categoria, etc.) -->
        <div class="form-group">
            <label>Código Interno</label>
            <input type="text" name="codigo_interno" value="<?php echo htmlspecialchars($edit_material['codigo_interno'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Categoria</label>
            <input type="text" name="categoria" value="<?php echo htmlspecialchars($edit_material['categoria'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Tipo</label>
            <select name="tipo" required>
                <option value="consumivel" <?php echo (isset($edit_material['tipo']) && $edit_material['tipo'] == 'consumivel') ? 'selected' : ''; ?>>Consumível</option>
                <option value="reutilizavel" <?php echo (isset($edit_material['tipo']) && $edit_material['tipo'] == 'reutilizavel') ? 'selected' : ''; ?>>Reutilizável</option>
            </select>
        </div>
        <div class="form-group">
            <label>Quantidade em Estoque</label>
            <input type="number" name="quantidade" value="<?php echo $edit_material['quantidade'] ?? 0; ?>" required>
        </div>
        <div class="form-group">
            <label>Estoque Mínimo</label>
            <input type="number" name="quantidade_min" value="<?php echo $edit_material['quantidade_min'] ?? 0; ?>" required>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <option value="1" <?php echo (!isset($edit_material['status']) || $edit_material['status'] == 1) ? 'selected' : ''; ?>>Ativo</option>
                <option value="0" <?php echo (isset($edit_material['status']) && $edit_material['status'] == 0) ? 'selected' : ''; ?>>Inativo</option>
            </select>
        </div>
        
        <button type="submit"><?php echo $edit_material ? 'Atualizar Material' : 'Salvar Material'; ?></button>
        <?php if ($edit_material): ?>
            <a href="gerenciar_materiais.php" class="btn btn-secondary">Cancelar Edição</a>
        <?php endif; ?>
    </form>
</div>

<h3>Materiais em Estoque</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Categoria</th>
            <th>Tipo</th>
            <th>Qtd.</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($materiais as $material): ?>
        <tr>
            <td><?php echo htmlspecialchars($material['nome']); ?></td>
            <td><?php echo htmlspecialchars($material['categoria']); ?></td>
            <td><?php echo htmlspecialchars(ucfirst($material['tipo'])); ?></td>
            <td><?php echo $material['quantidade']; ?></td>
            <td><?php echo $material['status'] ? 'Ativo' : 'Inativo'; ?></td>
            <td><a href="gerenciar_materiais.php?edit=<?php echo $material['id_material']; ?>" class="btn">Editar</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../templates/footer.php'; ?>