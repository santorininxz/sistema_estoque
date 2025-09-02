<?php
require_once '../templates/header.php';
check_login('admin');

$success_message = '';
$error_message = '';
$edit_user = null;

// Lógica para ADICIONAR ou EDITAR usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $perfil = filter_input(INPUT_POST, 'perfil', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
    $senha = $_POST['senha']; // Senha não é filtrada para permitir caracteres especiais

    try {
        if ($id_usuario) { // ATUALIZAÇÃO
            if (!empty($senha)) {
                $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, status=?, senha=? WHERE id_usuario=?");
                $stmt->execute([$nome, $email, $perfil, $status, $hash_senha, $id_usuario]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET nome=?, email=?, perfil=?, status=? WHERE id_usuario=?");
                $stmt->execute([$nome, $email, $perfil, $status, $id_usuario]);
            }
            registrar_log($pdo, $_SESSION['id_usuario'], 'Atualização de Usuário', "ID: {$id_usuario}");
            $success_message = 'Usuário atualizado com sucesso!';
        } else { // CRIAÇÃO
            if (empty($senha)) {
                throw new Exception("O campo senha é obrigatório para novos usuários.");
            }
            $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $hash_senha, $perfil, $status]);
            $new_id = $pdo->lastInsertId();
            registrar_log($pdo, $_SESSION['id_usuario'], 'Criação de Usuário', "ID: {$new_id}");
            $success_message = 'Usuário criado com sucesso!';
        }
    } catch (Exception $e) {
        $error_message = 'Erro: ' . $e->getMessage();
    }
}

// Lógica para carregar dados de um usuário para edição
if (isset($_GET['edit'])) {
    $id_edit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_edit]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar todos os usuários para listar
$usuarios = $pdo->query("SELECT id_usuario, nome, email, perfil, status FROM usuarios ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gerenciar Usuários</h2>

<?php if ($success_message): ?><p class="success"><?php echo htmlspecialchars($success_message); ?></p><?php endif; ?>
<?php if ($error_message): ?><p class="error"><?php echo htmlspecialchars($error_message); ?></p><?php endif; ?>

<!-- Formulário de Criação/Edição -->
<div class="form-container">
    <h3><?php echo $edit_user ? 'Editar Usuário' : 'Adicionar Novo Usuário'; ?></h3>
    <form action="gerenciar_usuarios.php" method="POST">
        <input type="hidden" name="id_usuario" value="<?php echo $edit_user['id_usuario'] ?? ''; ?>">
        
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($edit_user['nome'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="<?php echo $edit_user ? 'Deixe em branco para não alterar' : ''; ?>">
        </div>

        <div class="form-group">
            <label for="perfil">Perfil</label>
            <select id="perfil" name="perfil" required>
                <option value="requisitante" <?php echo (isset($edit_user['perfil']) && $edit_user['perfil'] == 'requisitante') ? 'selected' : ''; ?>>Requisitante</option>
                <option value="admin" <?php echo (isset($edit_user['perfil']) && $edit_user['perfil'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="1" <?php echo (!isset($edit_user['status']) || $edit_user['status'] == 1) ? 'selected' : ''; ?>>Ativo</option>
                <option value="0" <?php echo (isset($edit_user['status']) && $edit_user['status'] == 0) ? 'selected' : ''; ?>>Inativo</option>
            </select>
        </div>
        
        <button type="submit"><?php echo $edit_user ? 'Atualizar Usuário' : 'Salvar Usuário'; ?></button>
        <?php if ($edit_user): ?>
            <a href="gerenciar_usuarios.php" class="btn btn-secondary">Cancelar Edição</a>
        <?php endif; ?>
    </form>
</div>

<h3>Usuários Cadastrados</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Perfil</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $usuario): ?>
        <tr>
            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
            <td><?php echo htmlspecialchars(ucfirst($usuario['perfil'])); ?></td>
            <td><?php echo $usuario['status'] ? 'Ativo' : 'Inativo'; ?></td>
            <td><a href="gerenciar_usuarios.php?edit=<?php echo $usuario['id_usuario']; ?>" class="btn">Editar</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once '../templates/footer.php'; ?>