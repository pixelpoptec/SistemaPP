<?php

session_start();
require_once '../config/auth.php';
require_once '../config/functions_excel.php';

verificaLogin();

// Inicializar variáveis
$erro = '';
$sucesso = '';
$csrf_token = gerarTokenCSRF();

// Modo de visualização (padrão = tabela, alternativa = cards)
$modo_visualizacao = isset($_GET['modo']) ? sanitizar($_GET['modo']) : (isset($_SESSION['modo_visualizacao']) ? $_SESSION['modo_visualizacao'] : 'tabela');
// Salvar a preferência do usuário na sessão
$_SESSION['modo_visualizacao'] = $modo_visualizacao;

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'])) {
        $erro = 'Erro de segurança. Por favor, tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';

        // Adicionar tarefa
        if ($acao === 'adicionar') {
            $nome = sanitizar($_POST['nome']);
            $detalhes = sanitizar($_POST['detalhes']);
            $status = sanitizar($_POST['status']);
            $previsao_termino = sanitizar($_POST['previsao_termino']);
            $prioridade = sanitizar($_POST['prioridade']);
            $cliente_id = (int)$_POST['cliente_id'];
            $tempo_horas = isset($_POST['tempo_horas']) ? (int)$_POST['tempo_horas'] : 0;
            $tempo_minutos = isset($_POST['tempo_minutos']) ? (int)$_POST['tempo_minutos'] : 0;

            $sql = "INSERT INTO tarefas (nome, detalhes, status, previsao_termino, prioridade, 
                    cliente_id, usuario_id, tempo_horas, tempo_minutos) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssiiii",
                $nome,
                $detalhes,
                $status,
                $previsao_termino,
                $prioridade,
                $cliente_id,
                $_SESSION['usuario_id'],
                $tempo_horas,
                $tempo_minutos
            );

            if ($stmt->execute()) {
                $tarefa_id = $stmt->insert_id;
                registrarLog($_SESSION['usuario_id'], 'TAREFA_ADICIONADA', "Tarefa ID: $tarefa_id adicionada");
                $sucesso = 'Tarefa adicionada com sucesso!';
            } else {
                $erro = 'Erro ao adicionar tarefa: ' . $conn->error;
            }
        }

        // Editar tarefa
        elseif ($acao === 'editar') {
            $tarefa_id = (int)$_POST['tarefa_id'];
            $nome = sanitizar($_POST['nome']);
            $detalhes = sanitizar($_POST['detalhes']);
            $status = sanitizar($_POST['status']);
            $previsao_termino = sanitizar($_POST['previsao_termino']);
            $prioridade = sanitizar($_POST['prioridade']);
            $cliente_id = (int)$_POST['cliente_id'];
            $tempo_horas = isset($_POST['tempo_horas']) ? (int)$_POST['tempo_horas'] : 0;
            $tempo_minutos = isset($_POST['tempo_minutos']) ? (int)$_POST['tempo_minutos'] : 0;

            // Se o status for concluído e não tiver data de término, adicionar data atual
            $termino_set = "";
            if ($status === 'concluido') {
                // Verificar se a tarefa não estava concluída antes
                $sql_check = "SELECT status FROM tarefas WHERE id = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("i", $tarefa_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $tarefa_atual = $result_check->fetch_assoc();

                if ($tarefa_atual['status'] !== 'concluido') {
                    $termino_set = ", termino_efetivo = NOW()";
                }
            }

            $sql = "UPDATE tarefas SET nome = ?, detalhes = ?, status = ?, 
                    previsao_termino = ?, prioridade = ?, cliente_id = ?,
                    tempo_horas = ?, tempo_minutos = ? $termino_set
                    WHERE id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssiiii",
                $nome,
                $detalhes,
                $status,
                $previsao_termino,
                $prioridade,
                $cliente_id,
                $tempo_horas,
                $tempo_minutos,
                $tarefa_id
            );

            if ($stmt->execute()) {
                registrarLog($_SESSION['usuario_id'], 'TAREFA_EDITADA', "Tarefa ID: $tarefa_id editada");
                $sucesso = 'Tarefa atualizada com sucesso!';
            } else {
                $erro = 'Erro ao atualizar tarefa: ' . $conn->error;
            }
        }

        // Excluir tarefa
        elseif ($acao === 'excluir') {
            $tarefa_id = (int)$_POST['tarefa_id'];

            $sql = "DELETE FROM tarefas WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $tarefa_id);

            if ($stmt->execute()) {
                registrarLog($_SESSION['usuario_id'], 'TAREFA_EXCLUIDA', "Tarefa ID: $tarefa_id excluída");
                $sucesso = 'Tarefa excluída com sucesso!';
            } else {
                $erro = 'Erro ao excluir tarefa: ' . $conn->error;
            }
        }

        // Atualizar status da tarefa
        elseif ($acao === 'atualizar_status') {
            $tarefa_id = (int)$_POST['tarefa_id'];
            $novo_status = sanitizar($_POST['novo_status']);

            // Se o status for concluído, adicionar data de término
            $termino_set = "";
            if ($novo_status === 'concluido') {
                $termino_set = ", termino_efetivo = NOW()";
            } else {
                $termino_set = ", termino_efetivo = null";
            }

            $sql = "UPDATE tarefas SET status = ? $termino_set WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $novo_status, $tarefa_id);

            if ($stmt->execute()) {
                registrarLog($_SESSION['usuario_id'], 'TAREFA_STATUS_ALTERADO', "Tarefa ID: $tarefa_id, Novo status: $novo_status");
                $sucesso = 'Status da tarefa atualizado com sucesso!';
            } else {
                $erro = 'Erro ao atualizar status da tarefa: ' . $conn->error;
            }
        }
    }
}

// Filtros de pesquisa
$filtro_status = isset($_GET['status']) ? sanitizar($_GET['status']) : '';
$filtro_prioridade = isset($_GET['prioridade']) ? sanitizar($_GET['prioridade']) : '';
$filtro_cliente = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
$filtro_busca = isset($_GET['busca']) ? sanitizar($_GET['busca']) : '';

$_SESSION['filtro_status'] = $filtro_status;
$_SESSION['filtro_prioridade'] = $filtro_prioridade;
$_SESSION['filtro_cliente'] = (int) $filtro_cliente;
$_SESSION['filtro_busca'] = $filtro_busca;

// Construir cláusula WHERE para filtros
$where_clauses = ["1=1"]; // Sempre verdadeiro para iniciar

//Quando for a visualização por cards
//Deve exibir as tarefas que não sejam concluídas
//Na visualização por tabelas, exibe tudo
//Jaime Pimenta - 16/06
if ($modo_visualizacao === 'cards') {
    if ($filtro_status) {
        $where_clauses[] = "t.status = '$filtro_status'";
    } else {
        $where_clauses[] = "t.status != 'concluido'";
    }
} else {
    if ($filtro_status) {
        $where_clauses[] = "t.status = '$filtro_status'";
    }
}

if ($filtro_prioridade) {
    $where_clauses[] = "t.prioridade = '$filtro_prioridade'";
}

if ($filtro_cliente > 0) {
    $where_clauses[] = "t.cliente_id = $filtro_cliente";
}

if ($filtro_busca) {
    $where_clauses[] = "(t.nome LIKE '%$filtro_busca%' OR t.detalhes LIKE '%$filtro_busca%')";
}

$where_clause = implode(" AND ", $where_clauses);

// Obter lista de tarefas com filtros aplicados
$sql_tarefas = "SELECT t.*, c.nome as cliente_nome 
                FROM tarefas t
                LEFT JOIN clientes c ON t.cliente_id = c.id
                WHERE $where_clause AND t.data_abertura > '2026-01-01'
                ORDER BY 
                    CASE WHEN t.status = 'concluido' THEN 1 ELSE 0 END,
                    CASE 
                        WHEN t.prioridade = 'urgente' THEN 1
                        WHEN t.prioridade = 'alta' THEN 2
                        WHEN t.prioridade = 'media' THEN 3
                        WHEN t.prioridade = 'baixa' THEN 4
                    END,
                    t.previsao_termino ASC";

$result_tarefas = $conn->query($sql_tarefas);
$tarefas = [];

while ($tarefa = $result_tarefas->fetch_assoc()) {
    $tarefas[] = $tarefa;
}

// Obter lista de clientes para o select
$sql_clientes = "SELECT id, nome FROM clientes ORDER BY nome";
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
    <title>Controle de Tarefas - Sistema de Acesso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style_tarefas.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include '../includes/header.php'; ?>
        
        <div class="content">
            <?php include '../includes/sidebar.php'; ?>
            
            <main>
                <h4>Controle de Tarefas</h4>
                
                <?php if (!empty($erro)) : ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($sucesso)) : ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarTarefa">
                        <i class="bi bi-plus-circle"></i> Nova Tarefa
                    </button>
                    <a href="clientes.php" class="btn btn-info">
                        <i class="bi bi-people"></i> Gerenciar Clientes
                    </a>
                    
                    <!-- Alternância entre modos de visualização -->
                    <div class="btn-group" role="group" aria-label="Alternar visualização">
                        <a href="?modo=cards<?php echo !empty($filtro_status) ? '&status=' . $filtro_status : ''; ?><?php echo !empty($filtro_prioridade) ? '&prioridade=' . $filtro_prioridade : ''; ?><?php echo $filtro_cliente > 0 ? '&cliente_id=' . $filtro_cliente : ''; ?><?php echo !empty($filtro_busca) ? '&busca=' . $filtro_busca : ''; ?>" class="btn btn-outline-secondary <?php echo $modo_visualizacao === 'cards' ? 'active' : ''; ?>">
                            <i class="bi bi-grid"></i> Cards
                        </a>
                        <a href="?modo=tabela<?php echo !empty($filtro_status) ? '&status=' . $filtro_status : ''; ?><?php echo !empty($filtro_prioridade) ? '&prioridade=' . $filtro_prioridade : ''; ?><?php echo $filtro_cliente > 0 ? '&cliente_id=' . $filtro_cliente : ''; ?><?php echo !empty($filtro_busca) ? '&busca=' . $filtro_busca : ''; ?>" class="btn btn-outline-secondary <?php echo $modo_visualizacao === 'tabela' ? 'active' : ''; ?>">
                            <i class="bi bi-table"></i> Tabela
                        </a>
                    </div>
                    
                    <!--<button type="button" class="btn btn-success" id="btnIniciarRastreador"><i class="bi bi-stopwatch"></i> Rastreador de Tempo</button>-->
                    <button class="btn btn-primary" onclick="abrirRastreadorTempo()">
                        <i class="fas fa-clock"></i> Rastrear Tempo
                    </button>                   
                </div>
                
                <div class="panel-section filters">
                    <!--<h3>Filtros</h3>-->
                    <form action="" method="get" class="row g-3">
                        <input type="hidden" name="modo" value="<?php echo $modo_visualizacao; ?>">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Todos</option>
                                <option value="aberta" <?php echo $filtro_status === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                                <option value="fazendo" <?php echo $filtro_status === 'fazendo' ? 'selected' : ''; ?>>Fazendo</option>
                                <option value="esperando" <?php echo $filtro_status === 'esperando' ? 'selected' : ''; ?>>Esperando</option>
                                <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="prioridade" class="form-label">Prioridade</label>
                            <select name="prioridade" id="prioridade" class="form-select">
                                <option value="">Todas</option>
                                <option value="baixa" <?php echo $filtro_prioridade === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                                <option value="media" <?php echo $filtro_prioridade === 'media' ? 'selected' : ''; ?>>Média</option>
                                <option value="alta" <?php echo $filtro_prioridade === 'alta' ? 'selected' : ''; ?>>Alta</option>
                                <option value="urgente" <?php echo $filtro_prioridade === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="cliente_id" class="form-label">Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($clientes as $cliente) : ?>
                                <option value="<?php echo $cliente['id']; ?>" <?php echo $filtro_cliente === $cliente['id'] ? 'selected' : ''; ?>>
                                    <?php echo $cliente['nome']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="busca" class="form-label">Busca</label>
                            <input type="text" name="busca" id="busca" class="form-control" value="<?php echo $filtro_busca; ?>" placeholder="Nome ou detalhes">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                            <a href="tarefas.php" class="btn btn-secondary">Limpar</a>
                            <?php if (!empty($tarefas)) : ?>
                                <form method="post" action="">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="acao" value="excel">
                                    <input type="hidden" name="tarefa" value="<?php echo $tarefa; ?>">
                                    <button type="submit" name="exportar" value="1" class="btn btn-info">Exportar Excel</button>
                                </form>
                            <?php endif; ?>                         
                        </div>
                    </form>
                </div>
                

                
                <div class="panel-section">
                    <h5>Minhas Tarefas | <strong><?php echo count($tarefas); ?></strong></h5>
                    
                    <?php if (empty($tarefas)) : ?>
                        <div class="alert alert-info">Nenhuma tarefa encontrada. Adicione uma nova tarefa.</div>
                    <?php else : ?>
                        <?php if ($modo_visualizacao === 'cards') : ?>
                            <!-- Visualização em Cards -->
                            <div class="row">
                                <?php foreach ($tarefas as $tarefa) : ?>
                                    <?php
                                    $prioridade_class = 'prioridade-' . $tarefa['prioridade'];
                                    $status_class = 'status-' . $tarefa['status'];
                                    $status_text = '';

                                    switch ($tarefa['status']) {
                                        case 'aberta':
                                            $status_text = 'Aberta';
                                            break;
                                        case 'fazendo':
                                            $status_text = 'Fazendo';
                                            break;
                                        case 'esperando':
                                            $status_text = 'Esperando';
                                            break;
                                        case 'concluido':
                                            $status_text = 'Concluído';
                                            break;
                                    }

                                    $data_atual = new DateTime();
                                    $data_previsao = new DateTime($tarefa['previsao_termino']);
                                    $vencida = ($data_previsao < $data_atual) && $tarefa['status'] !== 'concluido';

                                    $prioridade_text = '';
                                    switch ($tarefa['prioridade']) {
                                        case 'baixa':
                                            $prioridade_text = 'Baixa';
                                            break;
                                        case 'media':
                                            $prioridade_text = 'Média';
                                            break;
                                        case 'alta':
                                            $prioridade_text = 'Alta';
                                            break;
                                        case 'urgente':
                                            $prioridade_text = 'Urgente';
                                            break;
                                    }
                                    ?>
                                    
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card tarefa-card <?php echo $prioridade_class; ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0"><?php echo $tarefa['id'] . " - " . $tarefa['nome']; ?></h5>
                                                <span class="badge status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text"><?php echo nl2br($tarefa['detalhes']); ?></p>
                                                
                                                <div class="info-group mb-2">
                                                    <small class="text-muted">Cliente: <strong><?php echo $tarefa['cliente_nome'] ?? 'Nenhum'; ?></strong></small>
                                                </div>
                                                
                                                <div class="info-group mb-2">
                                                    <small class="text-muted">Prioridade: <strong><?php echo $prioridade_text; ?></strong></small>
                                                </div>
                                                
                                                <div class="info-group mb-2">
                                                    <small class="text-muted">Aberta em: <strong><?php echo date('d/m/Y', strtotime($tarefa['data_abertura'])); ?></strong></small>
                                                </div>
                                                
                                                <div class="info-group mb-2">
                                                    <small class="text-muted">Previsão: 
                                                        <strong class="<?php echo $vencida ? 'data-vencida' : ''; ?>">
                                                            <?php echo date('d/m/Y', strtotime($tarefa['previsao_termino'])); ?>
                                                            <?php echo $vencida ? ' (Vencida)' : ''; ?>
                                                        </strong>
                                                    </small>
                                                </div>
                                                
                                                <?php if ($tarefa['termino_efetivo']) : ?>
                                                <div class="info-group mb-2">
                                                    <small class="text-muted">Concluída em: <strong><?php echo date('d/m/Y', strtotime($tarefa['termino_efetivo'])); ?></strong></small>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <div class="info-group mb-3">
                                                    <small class="text-muted">Tempo registrado: <strong><?php echo $tarefa['tempo_horas']; ?>h <?php echo $tarefa['tempo_minutos']; ?>min</strong></small>
                                                </div>
                                                
                                                <div class="task-actions">
                                                    <!-- Botão Editar -->
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalEditarTarefa"
                                                            data-id="<?php echo $tarefa['id']; ?>"
                                                            data-nome="<?php echo htmlspecialchars($tarefa['nome']); ?>"
                                                            data-detalhes="<?php echo htmlspecialchars($tarefa['detalhes']); ?>"
                                                            data-status="<?php echo $tarefa['status']; ?>"
                                                            data-previsao="<?php echo $tarefa['previsao_termino']; ?>"
                                                            data-prioridade="<?php echo $tarefa['prioridade']; ?>"
                                                            data-cliente="<?php echo $tarefa['cliente_id']; ?>"
                                                            data-horas="<?php echo $tarefa['tempo_horas']; ?>"
                                                            data-minutos="<?php echo $tarefa['tempo_minutos']; ?>">
                                                        <i class="bi bi-pencil"></i> Editar
                                                    </button>
                                                    
                                                    <!-- Menu suspenso para alterar status -->
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-info dropdown-toggle" type="button" id="dropdownStatusButton<?php echo $tarefa['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-arrow-repeat"></i> Status
                                                        </button>
                                                        <ul class="dropdown-menu" aria-labelledby="dropdownStatusButton<?php echo $tarefa['id']; ?>">
                                                            <li>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                                    <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                                                    <input type="hidden" name="novo_status" value="aberta">
                                                                    <button type="submit" class="dropdown-item">Aberta</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                                    <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                                                    <input type="hidden" name="novo_status" value="fazendo">
                                                                    <button type="submit" class="dropdown-item">Fazendo</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                                    <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                                                    <input type="hidden" name="novo_status" value="esperando">
                                                                    <button type="submit" class="dropdown-item">Esperando</button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="post" action="">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                                    <input type="hidden" name="acao" value="atualizar_status">
                                                                    <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                                                    <input type="hidden" name="novo_status" value="concluido">
                                                                    <button type="submit" class="dropdown-item">Concluído</button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    
                                                    <!-- Botão Excluir -->
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalExcluirTarefa"
                                                            data-id="<?php echo $tarefa['id']; ?>"
                                                            data-nome="<?php echo htmlspecialchars($tarefa['nome']); ?>">
                                                        <i class="bi bi-trash"></i> Excluir
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <!-- Visualização em Tabela -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover data-table table-tarefas">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <!--<th>Descrição</th>-->
                                            <th>Cliente</th>
                                            <th>Abertura</th>
                                            <th>Previsão</th>
                                            <th>Conclusão</th>
                                            <th>Tempo</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tarefas as $tarefa) : ?>
                                            <?php
                                            $status_dot_class = 'status-' . $tarefa['status'] . '-dot';
                                            $status_text = '';
                                            $rowClass = '';

                                            switch ($tarefa['status']) {
                                                case 'aberta':
                                                    $status_text = 'Aberta';
                                                    $rowClass = 'status-row-aberto';
                                                    break;
                                                case 'fazendo':
                                                    $status_text = 'Fazendo';
                                                    break;
                                                case 'esperando':
                                                    $status_text = 'Esperando';
                                                    break;
                                                case 'concluido':
                                                    $status_text = 'Concluído';
                                                    break;
                                            }

                                            $data_atual = new DateTime();
                                            $data_previsao = new DateTime($tarefa['previsao_termino']);
                                            $vencida = ($data_previsao < $data_atual) && $tarefa['status'] !== 'concluido';

                                            // Limitar descrição a 20 caracteres
                                            $descricao_curta = strlen($tarefa['detalhes']) > 20
                                                ? substr($tarefa['detalhes'], 0, 20) . '...'
                                                : $tarefa['detalhes'];
                                            ?>
                                            <tr class="<?php echo $rowClass; ?>">
                                                <td data-label="ID:"><?php echo $tarefa['id']; ?></td>
                                                <!--<td data-label="Nome:"><span class="truncate-text"><?php echo $tarefa['nome']; ?></span></td>-->
                                                <td data-label="Descrição:" class="table-cell-tooltip" data-tooltip="<?php echo htmlspecialchars($tarefa['detalhes']); ?>">
                                                    <span class="truncate-text"><?php echo $tarefa['nome']; ?></span>
                                                </td>                                               
                                                <!--<td data-label="Descrição:"><span class="truncate-text" title="<?php echo htmlspecialchars($tarefa['detalhes']); ?>"><?php echo $descricao_curta; ?></span></td>-->
                                                <td data-label="Cliente:"><?php echo $tarefa['cliente_nome'] ?? 'Nenhum'; ?></td>
                                                <td data-label="Abertura:"><?php echo date('d/m/Y', strtotime($tarefa['data_abertura'])); ?></td>
                                                <td data-label="Previsão:" class="<?php echo $vencida ? 'data-vencida' : ''; ?>">
                                                    <?php echo date('d/m/Y', strtotime($tarefa['previsao_termino'])); ?>
                                                    <?php echo $vencida ? ' (!)' : ''; ?>
                                                </td>
                                                <td data-label="Conclusão:"><?php echo $tarefa['termino_efetivo'] ? date('d/m/Y', strtotime($tarefa['termino_efetivo'])) : ''; ?></td>
                                                <td data-label="Hora:"><?php echo sprintf("%02d", $tarefa['tempo_horas']) . 'h' . sprintf("%02d", $tarefa['tempo_minutos']) . 'm'; ?></td>
                                                <td data-label="Status:">
                                                    <div><span class="status-indicator <?php echo $status_dot_class; ?>"></span> <?php echo $status_text; ?></div>
                                                </td>
                                                <td data-label="Ações:">
                                                    <!-- Botão Detalhes -->
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalDetalhesTarefa"
                                                            data-id="<?php echo $tarefa['id']; ?>"
                                                            data-nome="<?php echo htmlspecialchars($tarefa['nome']); ?>"
                                                            data-detalhes="<?php echo htmlspecialchars($tarefa['detalhes']); ?>"
                                                            data-status="<?php echo $tarefa['status']; ?>"
                                                            data-status-text="<?php echo $status_text; ?>"
                                                            data-previsao="<?php echo date('d/m/Y', strtotime($tarefa['previsao_termino'])); ?>"
                                                            data-prioridade="<?php echo $tarefa['prioridade']; ?>"
                                                            data-cliente="<?php echo $tarefa['cliente_nome'] ?? 'Nenhum'; ?>"
                                                            data-abertura="<?php echo date('d/m/Y', strtotime($tarefa['data_abertura'])); ?>"
                                                            data-termino="<?php echo $tarefa['termino_efetivo'] ? date('d/m/Y', strtotime($tarefa['termino_efetivo'])) : ''; ?>"
                                                            data-horas="<?php echo $tarefa['tempo_horas']; ?>"
                                                            data-minutos="<?php echo $tarefa['tempo_minutos']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Botão Editar -->
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalEditarTarefa"
                                                            data-id="<?php echo $tarefa['id']; ?>"
                                                            data-nome="<?php echo htmlspecialchars($tarefa['nome']); ?>"
                                                            data-detalhes="<?php echo htmlspecialchars($tarefa['detalhes']); ?>"
                                                            data-status="<?php echo $tarefa['status']; ?>"
                                                            data-previsao="<?php echo $tarefa['previsao_termino']; ?>"
                                                            data-prioridade="<?php echo $tarefa['prioridade']; ?>"
                                                            data-cliente="<?php echo $tarefa['cliente_id']; ?>"
                                                            data-horas="<?php echo $tarefa['tempo_horas']; ?>"
                                                            data-minutos="<?php echo $tarefa['tempo_minutos']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    
                                                    <!-- Botão Excluir -->
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#modalExcluirTarefa"
                                                            data-id="<?php echo $tarefa['id']; ?>"
                                                            data-nome="<?php echo htmlspecialchars($tarefa['nome']); ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <!-- Modal Adicionar Tarefa -->
    <div class="modal fade" id="modalAdicionarTarefa" tabindex="-1" aria-labelledby="modalAdicionarTarefaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAdicionarTarefaLabel">Adicionar Nova Tarefa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formAdicionarTarefa">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="acao" value="adicionar">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="nome" class="form-label">Nome da Tarefa*</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="detalhes" class="form-label">Detalhes*</label>
                                <textarea class="form-control" id="detalhes" name="detalhes" rows="4" required></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status*</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="aberta">Aberta</option>
                                    <option value="fazendo">Fazendo</option>
                                    <option value="esperando">Esperando</option>
                                    <option value="concluido">Concluído</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="prioridade" class="form-label">Prioridade*</label>
                                <select class="form-select" id="prioridade" name="prioridade" required>
                                    <option value="baixa">Baixa</option>
                                    <option value="media" selected>Média</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="previsao_termino" class="form-label">Previsão de Término*</label>
                                <input type="date" class="form-control" id="previsao_termino" name="previsao_termino" required>
                            </div>
                            <div class="col-md-6">
                                <label for="cliente_id" class="form-label">Cliente</label>
                                <select class="form-select" id="cliente_id" name="cliente_id">
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $cliente) : ?>
                                        <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tempo_horas" class="form-label">Tempo Gasto (Horas)</label>
                                <input type="number" class="form-control" id="tempo_horas" name="tempo_horas" min="0" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="tempo_minutos" class="form-label">Tempo Gasto (Minutos)</label>
                                <input type="number" class="form-control" id="tempo_minutos" name="tempo_minutos" min="0" max="59" value="0">
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar Tarefa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar Tarefa -->
    <div class="modal fade" id="modalEditarTarefa" tabindex="-1" aria-labelledby="modalEditarTarefaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarTarefaLabel">Editar Tarefa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="" id="formEditarTarefa">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="acao" value="editar">
                        <input type="hidden" name="tarefa_id" id="editar_tarefa_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editar_nome" class="form-label">Nome da Tarefa*</label>
                                <input type="text" class="form-control" id="editar_nome" name="nome" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="editar_detalhes" class="form-label">Detalhes*</label>
                                <textarea class="form-control" id="editar_detalhes" name="detalhes" rows="4" required></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editar_status" class="form-label">Status*</label>
                                <select class="form-select" id="editar_status" name="status" required>
                                    <option value="aberta">Aberta</option>
                                    <option value="fazendo">Fazendo</option>
                                    <option value="esperando">Esperando</option>
                                    <option value="concluido">Concluído</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editar_prioridade" class="form-label">Prioridade*</label>
                                <select class="form-select" id="editar_prioridade" name="prioridade" required>
                                    <option value="baixa">Baixa</option>
                                    <option value="media">Média</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editar_previsao_termino" class="form-label">Previsão de Término*</label>
                                <input type="date" class="form-control" id="editar_previsao_termino" name="previsao_termino" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editar_cliente_id" class="form-label">Cliente</label>
                                <select class="form-select" id="editar_cliente_id" name="cliente_id">
                                    <option value="">Selecione um cliente</option>
                                    <?php foreach ($clientes as $cliente) : ?>
                                        <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editar_tempo_horas" class="form-label">Tempo Gasto (Horas)</label>
                                <input type="number" class="form-control" id="editar_tempo_horas" name="tempo_horas" min="0" value="0">
                            </div>
                            <div class="col-md-6">
                                <label for="editar_tempo_minutos" class="form-label">Tempo Gasto (Minutos)</label>
                                <input type="number" class="form-control" id="editar_tempo_minutos" name="tempo_minutos" min="0" max="59" value="0">
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
    
    <!-- Modal Detalhes da Tarefa (para visualização na tabela) -->
    <div class="modal fade" id="modalDetalhesTarefa" tabindex="-1" aria-labelledby="modalDetalhesTarefaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalhesTarefaLabel">Detalhes da Tarefa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4 id="detalhe_nome"></h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <span id="detalhe_status_badge" class="badge status-badge"></span>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    Descrição
                                </div>
                                <div class="card-body">
                                    <p id="detalhe_detalhes"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Cliente:</strong> <span id="detalhe_cliente"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Prioridade:</strong> <span id="detalhe_prioridade"></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p><strong>Data de Abertura:</strong> <span id="detalhe_data_abertura"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Previsão:</strong> <span id="detalhe_previsao"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Término Efetivo:</strong> <span id="detalhe_termino"></span></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <p><strong>Tempo Registrado:</strong> <span id="detalhe_tempo"></span></p>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" id="btn_editar_detalhes">Editar Tarefa</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Excluir Tarefa -->
    <div class="modal fade" id="modalExcluirTarefa" tabindex="-1" aria-labelledby="modalExcluirTarefaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExcluirTarefaLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja excluir a tarefa <strong id="excluir_tarefa_nome"></strong>?</p>
                    <p class="text-danger">Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="tarefa_id" id="excluir_tarefa_id">
                        
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
            // Definir a data atual como valor padrão para o campo de previsão
            const hoje = new Date().toISOString().split('T')[0];
            document.getElementById('previsao_termino').value = hoje;
            
            // Modal de Edição
            const modalEditarTarefa = document.getElementById('modalEditarTarefa');
            if (modalEditarTarefa) {
                modalEditarTarefa.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extrair dados dos atributos data do botão
                    const id = button.getAttribute('data-id');
                    const nome = button.getAttribute('data-nome');
                    const detalhes = button.getAttribute('data-detalhes');
                    const status = button.getAttribute('data-status');
                    const previsao = button.getAttribute('data-previsao');
                    const prioridade = button.getAttribute('data-prioridade');
                    const cliente = button.getAttribute('data-cliente');
                    const horas = button.getAttribute('data-horas');
                    const minutos = button.getAttribute('data-minutos');
                    
                    // Atualizar campos do formulário
                    document.getElementById('editar_tarefa_id').value = id;
                    document.getElementById('editar_nome').value = nome;
                    document.getElementById('editar_detalhes').value = detalhes;
                    document.getElementById('editar_status').value = status;
                    document.getElementById('editar_previsao_termino').value = previsao;
                    document.getElementById('editar_prioridade').value = prioridade;
                    document.getElementById('editar_cliente_id').value = cliente || '';
                    document.getElementById('editar_tempo_horas').value = horas;
                    document.getElementById('editar_tempo_minutos').value = minutos;
                });
            }
            
            // Modal de Detalhes
            const modalDetalhesTarefa = document.getElementById('modalDetalhesTarefa');
            if (modalDetalhesTarefa) {
                modalDetalhesTarefa.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extrair dados dos atributos data do botão
                    const id = button.getAttribute('data-id');
                    const nome = button.getAttribute('data-nome');
                    const detalhes = button.getAttribute('data-detalhes');
                    const status = button.getAttribute('data-status');
                    const statusText = button.getAttribute('data-status-text');
                    const previsao = button.getAttribute('data-previsao');
                    const prioridade = button.getAttribute('data-prioridade');
                    const cliente = button.getAttribute('data-cliente');
                    const abertura = button.getAttribute('data-abertura');
                    const termino = button.getAttribute('data-termino');
                    const horas = button.getAttribute('data-horas');
                    const minutos = button.getAttribute('data-minutos');
                    
                    // Atualizar conteúdo do modal
                    document.getElementById('detalhe_nome').textContent = nome;
                    document.getElementById('detalhe_detalhes').innerHTML = detalhes.replace(/\n/g, '<br>');
                    document.getElementById('detalhe_cliente').textContent = cliente;
                    
                    // Traduzir prioridade
                    let prioridadeText = '';
                    switch (prioridade) {
                        case 'baixa': prioridadeText = 'Baixa'; break;
                        case 'media': prioridadeText = 'Média'; break;
                        case 'alta': prioridadeText = 'Alta'; break;
                        case 'urgente': prioridadeText = 'Urgente'; break;
                    }
                    document.getElementById('detalhe_prioridade').textContent = prioridadeText;
                    
                    document.getElementById('detalhe_data_abertura').textContent = abertura;
                    document.getElementById('detalhe_previsao').textContent = previsao;
                    document.getElementById('detalhe_termino').textContent = termino || 'Não concluída';
                    document.getElementById('detalhe_tempo').textContent = horas + 'h ' + minutos + 'min';
                    
                    // Configurar o badge de status
                    const statusBadge = document.getElementById('detalhe_status_badge');
                    statusBadge.textContent = statusText;
                    
                    // Remover classes antigas e adicionar nova classe de status
                    statusBadge.className = 'badge status-badge status-' + status;
                    
                    // Configurar o botão de editar para abrir o modal de edição com os mesmos dados
                    const btnEditarDetalhes = document.getElementById('btn_editar_detalhes');
                    btnEditarDetalhes.setAttribute('data-id', id);
                    btnEditarDetalhes.setAttribute('data-nome', nome);
                    btnEditarDetalhes.setAttribute('data-detalhes', detalhes);
                    btnEditarDetalhes.setAttribute('data-status', status);
                    btnEditarDetalhes.setAttribute('data-previsao', previsao);
                    btnEditarDetalhes.setAttribute('data-prioridade', prioridade);
                    btnEditarDetalhes.setAttribute('data-cliente', button.getAttribute('data-cliente-id'));
                    btnEditarDetalhes.setAttribute('data-horas', horas);
                    btnEditarDetalhes.setAttribute('data-minutos', minutos);
                    
                    // Adicionar evento de clique para abrir o modal de edição
                    btnEditarDetalhes.onclick = function() {
                        // Esconder o modal de detalhes
                        const detalhesModal = bootstrap.Modal.getInstance(document.getElementById('modalDetalhesTarefa'));
                        detalhesModal.hide();
                        
                        // Esperar um pouco para evitar conflitos de modais
                        setTimeout(function() {
                            // Atualizar dados no modal de edição
                            document.getElementById('editar_tarefa_id').value = id;
                            document.getElementById('editar_nome').value = nome;
                            document.getElementById('editar_detalhes').value = detalhes;
                            document.getElementById('editar_status').value = status;
                            document.getElementById('editar_previsao_termino').value = previsao;
                            document.getElementById('editar_prioridade').value = prioridade;
                            document.getElementById('editar_cliente_id').value = button.getAttribute('data-cliente-id') || '';
                            document.getElementById('editar_tempo_horas').value = horas;
                            document.getElementById('editar_tempo_minutos').value = minutos;
                            
                            // Abrir o modal de edição
                            const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarTarefa'));
                            modalEditar.show();
                        }, 500);
                    };
                });
            }
            
            // Modal de Exclusão
            const modalExcluirTarefa = document.getElementById('modalExcluirTarefa');
            if (modalExcluirTarefa) {
                modalExcluirTarefa.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    
                    // Extrair informações do botão
                    const id = button.getAttribute('data-id');
                    const nome = button.getAttribute('data-nome');
                    
                    // Atualizar campos do modal
                    document.getElementById('excluir_tarefa_id').value = id;
                    document.getElementById('excluir_tarefa_nome').textContent = nome;
                });
            }
        
        });
        
        function abrirRastreadorTempo() {
            // Define a janela pop-up
            let popupWidth = 400;
            let popupHeight = 500;
            
            // Centralizar a janela
            let left = (screen.width - popupWidth) / 2;
            let top = (screen.height - popupHeight) / 2;
            
            // Configurações da janela
            let config = `width=${popupWidth},height=${popupHeight},top=${top},left=${left}`;
            config += ',resizable=yes,scrollbars=no,status=no,location=no,menubar=no,toolbar=no';
            
            // Abrir a janela
            window.open('rastreador_tempo.php', 'rastreadorTempo', config);
        };      
    </script>
</body>
</html>
