<?php
require_once '../config/auth.php';

// Verificar se o usuário está logado
verificaLogin();

// Inicializar variáveis
$erro = '';
$sucesso = '';
$csrf_token = gerarTokenCSRF();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'])) {
        $erro = 'Erro de segurança. Por favor, tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';

        // Adicionar cliente
        if ($acao === 'adicionar') {
            $nome = sanitizar($_POST['nome']);
            $email = sanitizar($_POST['email'] ?? '');
            $telefone = sanitizar($_POST['telefone'] ?? '');
            $empresa = sanitizar($_POST['empresa'] ?? '');
            $endereco = sanitizar($_POST['endereco'] ?? '');
            $observacoes = sanitizar($_POST['observacoes'] ?? '');

            // Validar email se fornecido
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Email inválido';
            } else {
                $sql = "INSERT INTO clientes (nome, email, telefone, empresa, endereco, observacoes, usuario_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $nome, $email, $telefone, $empresa, $endereco, $observacoes, $_SESSION['usuario_id']);

                if ($stmt->execute()) {
                    $cliente_id = $stmt->insert_id;
                    registrarLog($_SESSION['usuario_id'], 'CLIENTE_ADICIONADO', "Cliente ID: $cliente_id adicionado");
                    $sucesso = 'Cliente adicionado com sucesso!';
                } else {
                    $erro = 'Erro ao adicionar cliente: ' . $conn->error;
                }
            }
        }

        // Editar cliente
        elseif ($acao === 'editar') {
            $cliente_id = (int)$_POST['cliente_id'];
            $nome = sanitizar($_POST['nome']);
            $email = sanitizar($_POST['email'] ?? '');
            $telefone = sanitizar($_POST['telefone'] ?? '');
            $empresa = sanitizar($_POST['empresa'] ?? '');
            $endereco = sanitizar($_POST['endereco'] ?? '');
            $observacoes = sanitizar($_POST['observacoes'] ?? '');

            // Validar email se fornecido
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Email inválido';
            } else {
                $sql = "UPDATE clientes 
                        SET nome = ?, email = ?, telefone = ?, empresa = ?, endereco = ?, observacoes = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $nome, $email, $telefone, $empresa, $endereco, $observacoes, $cliente_id);

                if ($stmt->execute()) {
                    registrarLog($_SESSION['usuario_id'], 'CLIENTE_EDITADO', "Cliente ID: $cliente_id editado");
                    $sucesso = 'Cliente atualizado com sucesso!';
                } else {
                    $erro = 'Erro ao atualizar cliente: ' . $conn->error;
                }
            }
        }

        // Excluir cliente
        elseif ($acao === 'excluir') {
            $cliente_id = (int)$_POST['cliente_id'];

            // Verificar se há tarefas associadas
            $sql_verifica = "SELECT COUNT(*) as total FROM tarefas WHERE cliente_id = ?";
            $stmt_verifica = $conn->prepare($sql_verifica);
            $stmt_verifica->bind_param("i", $cliente_id);
            $stmt_verifica->execute();
            $resultado = $stmt_verifica->get_result()->fetch_assoc();

            if ($resultado['total'] > 0) {
                $erro = 'Não é possível excluir este cliente pois há tarefas associadas a ele.';
            } else {
                $sql = "DELETE FROM clientes WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $cliente_id);

                if ($stmt->execute()) {
                    registrarLog($_SESSION['usuario_id'], 'CLIENTE_EXCLUIDO', "Cliente ID: $cliente_id excluído");
                    $sucesso = 'Cliente excluído com sucesso!';
                } else {
                    $erro = 'Erro ao excluir cliente: ' . $conn->error;
                }
            }
        }
    }
}

// Filtro de busca
$filtro_busca = isset($_GET['busca']) ? sanitizar($_GET['busca']) : '';
$where_clause = "1=1";

if ($filtro_busca) {
    $where_clause .= " AND (nome LIKE '%$filtro_busca%' OR email LIKE '%$filtro_busca%' OR empresa LIKE '%$filtro_busca%')";
}

// Obter lista de clientes
$sql_clientes = "SELECT * FROM clientes WHERE $where_clause ORDER BY nome";
$result_clientes = $conn->query($sql_clientes);
$clientes = [];

while ($cliente = $result_clientes->fetch_assoc()) {
    $clientes[] = $cliente;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php include '../includes/sidebar.php'; ?>
            
            <main>
                <h2>Gerenciar Clientes</h2>
                
                <?php if (!empty($erro)) : ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($sucesso)) : ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarCliente">
                        <i class="bi bi-plus-circle"></i> Novo Cliente
                    </button>
                    <a href="tarefas.php" class="btn btn-info">
                        <i class="bi bi-list-task"></i> Gerenciar Tarefas
                    </a>
                </div>
                
                <div class="panel-section">
                    <h3>Buscar Clientes</h3>
                    <form action="" method="get" class="row g-3">
                        <div class="col-md-8 col-sm-8">
                            <input type="text" name="busca" id="busca" class="form-control" value="<?php echo $filtro_busca; ?>" placeholder="Nome, email ou empresa">
                        </div>
                        <div class="col-md-4 col-sm-4">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            <a href="clientes.php" class="btn btn-secondary">Limpar</a>
                        </div>
                    </form>
                </div>
                
                <div class="panel-section">
                    <h3>Lista de Clientes</h3>
                    
                    <?php if (empty($clientes)) : ?>
                        <div class="alert alert-info">Nenhum cliente encontrado. Adicione um novo cliente.</div>
                    <?php else : ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover data-table">
                                <thead>
                                    <tr>
                                        <!--<th>Nome</th>-->
                                        <th>Empresa</th>
                                        <!--<th>Email</th>-->
                                        <th>Telefone</th>
                                        <!--<th>Data de Cadastro</th>-->
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientes as $cliente) : ?>
                                    <tr>
                                        <!--<td data-label="Nome:"><?php echo $cliente['nome']; ?></td>-->
                                        <td data-label="Empresa:"><?php echo $cliente['empresa'] ?: '-'; ?></td>
                                        <!--<td data-label="Email:"><?php echo $cliente['email'] ?: '-'; ?></td>-->
                                        <td data-label="Telefone:">        
                                            <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $cliente['telefone']); ?>" 
                                               target="_blank" 
                                               class="whatsapp-link" 
                                               title="Abrir WhatsApp">
                                                <i class="bi bi-whatsapp text-success me-1"></i>
                                                <?php echo $cliente['telefone']; ?>
                                            </a>
                                        </td>
                                        <!--<td data-label="Data de Cadastro:"><?php echo date('d/m/Y', strtotime($cliente['data_cadastro'])); ?></td>-->
                                        <td data-label="Ações:" class="actions">
                                            <!-- Botão Ver Detalhes -->
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalVerCliente"
                                                    data-id="<?php echo $cliente['id']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>"
                                                    data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                                    data-telefone="<?php echo htmlspecialchars($cliente['telefone']); ?>"
                                                    data-empresa="<?php echo htmlspecialchars($cliente['empresa']); ?>"
                                                    data-endereco="<?php echo htmlspecialchars($cliente['endereco']); ?>"
                                                    data-observacoes="<?php echo htmlspecialchars($cliente['observacoes']); ?>"
                                                    data-datacadastro="<?php echo date('d/m/Y', strtotime($cliente['data_cadastro'])); ?>">
                                                <i class="bi bi-eye"></i> Ver
                                            </button>
                                            
                                            <!-- Botão Editar -->
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditarCliente"
                                                    data-id="<?php echo $cliente['id']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>"
                                                    data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                                    data-telefone="<?php echo htmlspecialchars($cliente['telefone']); ?>"
                                                    data-empresa="<?php echo htmlspecialchars($cliente['empresa']); ?>"
                                                    data-endereco="<?php echo htmlspecialchars($cliente['endereco']); ?>"
                                                    data-observacoes="<?php echo htmlspecialchars($cliente['observacoes']); ?>">
                                                <i class="bi bi-pencil"></i> Editar
                                            </button>
                                            
                                            <!-- Botão Excluir -->
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalExcluirCliente"
                                                    data-id="<?php echo $cliente['id']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($cliente['nome']); ?>">
                                                <i class="bi bi-trash"></i> Excluir
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <!-- Modal Adicionar Cliente -->
    <div class="modal fade" id="modalAdicionarCliente" tabindex="-1" aria-labelledby="modalAdicionarClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdicionarClienteLabel">Adicionar Novo Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formAdicionarCliente">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="acao" value="adicionar">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="nome" class="form-label">Nome*</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa" name="empresa">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="endereco" class="form-label">Endereço</label>
                                <textarea class="form-control" id="endereco" name="endereco" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Cliente</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Cliente -->
    <div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-labelledby="modalEditarClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarClienteLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formEditarCliente">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="cliente_id" id="editar_cliente_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editar_nome" class="form-label">Nome*</label>
                                <input type="text" class="form-control" id="editar_nome" name="nome" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editar_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editar_email" name="email">
                            </div>
                            <div class="col-md-6">
                                <label for="editar_telefone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="editar_telefone" name="telefone">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editar_empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="editar_empresa" name="empresa">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editar_endereco" class="form-label">Endereço</label>
                                <textarea class="form-control" id="editar_endereco" name="endereco" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editar_observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="editar_observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Ver Cliente -->
    <div class="modal fade" id="modalVerCliente" tabindex="-1" aria-labelledby="modalVerClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerClienteLabel">Detalhes do Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h4 id="ver_nome"></h4>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <span id="ver_email"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Telefone:</strong> <span id="ver_telefone"></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Empresa:</strong> <span id="ver_empresa"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Data de Cadastro:</strong> <span id="ver_datacadastro"></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <p><strong>Endereço:</strong></p>
                            <p id="ver_endereco"></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <p><strong>Observações:</strong></p>
                            <p id="ver_observacoes"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Excluir Cliente -->
    <div class="modal fade" id="modalExcluirCliente" tabindex="-1" aria-labelledby="modalExcluirClienteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExcluirClienteLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir o cliente <strong id="excluir_cliente_nome"></strong>?</p>
                    <p class="text-danger">Esta ação não pode ser desfeita! Se houver tarefas associadas a este cliente, a exclusão não será permitida.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="cliente_id" id="excluir_cliente_id">
                        
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para inicializar modais e gerenciar dados
        document.addEventListener('DOMContentLoaded', function() {
            // Modal de Edição
            const modalEditarCliente = document.getElementById('modalEditarCliente');
            if (modalEditarCliente) {
                modalEditarCliente.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extrair dados dos atributos data do botão
                    const id = button.getAttribute('data-id');
                    const nome = button.getAttribute('data-nome');
                    const email = button.getAttribute('data-email');
                    const telefone = button.getAttribute('data-telefone');
                    const empresa = button.getAttribute('data-empresa');
                    const endereco = button.getAttribute('data-endereco');
                    const observacoes = button.getAttribute('data-observacoes');
                    
                    // Atualizar campos do formulário
                    document.getElementById('editar_cliente_id').value = id;
                    document.getElementById('editar_nome').value = nome;
                    document.getElementById('editar_email').value = email || '';
                    document.getElementById('editar_telefone').value = telefone || '';
                    document.getElementById('editar_empresa').value = empresa || '';
                    document.getElementById('editar_endereco').value = endereco || '';
                    document.getElementById('editar_observacoes').value = observacoes || '';
                });
            }
            
            // Modal de Visualização
            const modalVerCliente = document.getElementById('modalVerCliente');
            if (modalVerCliente) {
                modalVerCliente.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extrair dados dos atributos data do botão
                    const nome = button.getAttribute('data-nome');
                    const email = button.getAttribute('data-email');
                    const telefone = button.getAttribute('data-telefone');
                    const empresa = button.getAttribute('data-empresa');
                    const endereco = button.getAttribute('data-endereco');
                    const observacoes = button.getAttribute('data-observacoes');
                    const datacadastro = button.getAttribute('data-datacadastro');
                    
                    // Atualizar campos da visualização
                    document.getElementById('ver_nome').textContent = nome;
                    document.getElementById('ver_email').textContent = email || '-';
                    document.getElementById('ver_telefone').textContent = telefone || '-';
                    document.getElementById('ver_empresa').textContent = empresa || '-';
                    document.getElementById('ver_endereco').textContent = endereco || '-';
                    document.getElementById('ver_observacoes').textContent = observacoes || '-';
                    document.getElementById('ver_datacadastro').textContent = datacadastro;
                });
            }
            
            // Modal de Exclusão
            const modalExcluirCliente = document.getElementById('modalExcluirCliente');
            if (modalExcluirCliente) {
                modalExcluirCliente.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extrair informações do botão
                    const id = button.getAttribute('data-id');
                    const nome = button.getAttribute('data-nome');
                    
                    // Atualizar campos do modal
                    document.getElementById('excluir_cliente_id').value = id;
                    document.getElementById('excluir_cliente_nome').textContent = nome;
                });
            }
        });
    </script>
</body>
</html>
