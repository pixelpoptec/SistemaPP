<?php
require_once '../config/auth.php';

// Verificar se o usuário está logado
verificaLogin();

// Processar registros de tempo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_tempo') {
    // Verificar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro de segurança. Por favor, tente novamente.']);
        exit;
    }
    
    $tarefa_id = (int)$_POST['tarefa_id'];
    $tempo_segundos = (int)$_POST['tempo_segundos'];
    $data_hora_inicio = $_POST['data_hora_inicio'];
    $data_hora_fim = $_POST['data_hora_fim'];
    $observacoes = sanitizar($_POST['observacoes'] ?? '');
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validações
    if ($tarefa_id <= 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Tarefa inválida.']);
        exit;
    }
    
    if ($tempo_segundos <= 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Tempo inválido.']);
        exit;
    }
    
    // Converter segundos para horas e minutos (sempre arredondando para cima)
    $tempo_horas = floor($tempo_segundos / 3600);
    $tempo_minutos = ceil(($tempo_segundos % 3600) / 60); // Arredondamento para cima dos minutos
    
    // Ajustar caso os minutos cheguem a 60 após arredondamento
    if ($tempo_minutos == 60) {
        $tempo_horas += 1;
        $tempo_minutos = 0;
    }
    
    // Verificar se a tarefa existe e não está concluída
    $sql_check = "SELECT id, status, tempo_horas, tempo_minutos FROM tarefas WHERE id = ? AND status != 'concluido'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $tarefa_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Tarefa não encontrada ou já concluída.']);
        exit;
    }
    
    $tarefa = $result->fetch_assoc();
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Inserir registro de tempo
        $sql_insert = "INSERT INTO tempo_rastreamento (tarefa_id, tempo_horas, tempo_minutos, data_hora_inicio, data_hora_fim, usuario_id, observacoes, tempo_segundos) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iiissssi", $tarefa_id, $tempo_horas, $tempo_minutos, $data_hora_inicio, $data_hora_fim, $usuario_id, $observacoes, $tempo_segundos);
        $stmt_insert->execute();
        
        // Atualizar tempo total da tarefa
        $novo_tempo_horas = $tarefa['tempo_horas'] + $tempo_horas;
        $novo_tempo_minutos = $tarefa['tempo_minutos'] + $tempo_minutos;
        
        // Ajustar minutos se ultrapassar 60
        if ($novo_tempo_minutos >= 60) {
            $novo_tempo_horas += floor($novo_tempo_minutos / 60);
            $novo_tempo_minutos = $novo_tempo_minutos % 60;
        }
        
        $sql_update = "UPDATE tarefas SET tempo_horas = ?, tempo_minutos = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("iii", $novo_tempo_horas, $novo_tempo_minutos, $tarefa_id);
        $stmt_update->execute();
        
        // Registrar log
        registrarLog($usuario_id, 'TEMPO_REGISTRADO', "Tempo registrado para tarefa ID: $tarefa_id ($tempo_horas h $tempo_minutos min)");
        
        // Confirmar transação
        $conn->commit();
        
        echo json_encode([
            'status' => 'sucesso', 
            'mensagem' => 'Tempo registrado com sucesso.', 
            'horas' => $novo_tempo_horas, 
            'minutos' => $novo_tempo_minutos
        ]);
        exit;
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $conn->rollback();
        echo json_encode(['status' => 'erro', 'mensagem' => 'Erro ao registrar tempo: ' . $e->getMessage()]);
        exit;
    }
}

// Buscar tarefas não concluídas para o select
$sql_tarefas = "SELECT id, nome FROM tarefas WHERE status != 'concluido' ORDER BY nome";
$result_tarefas = $conn->query($sql_tarefas);
$tarefas = [];

while ($tarefa = $result_tarefas->fetch_assoc()) {
    $tarefas[] = $tarefa;
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreador de Tempo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            padding: 0;
            margin: 0;
            background-color: transparent;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .cronometro-container {
            width: 300px;
            min-height: 400px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 15px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .cronometro-container.minimizado {
            width: 150px;
            height: 40px;
            overflow: hidden;
        }
        
        .header-rastreador {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            cursor: move;
        }
        
        .header-rastreador h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #6b705c;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-buttons button {
            border: none;
            background: none;
            font-size: 14px;
            color: #6b705c;
            cursor: pointer;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-buttons button:hover {
            color: #000;
        }
        
        .tempo-display {
            font-size: 38px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            color: #6b705c;
            font-family: 'Courier New', monospace;
        }
        
        .tempo-segundos {
            font-size: 20px;
            color: #87b7a4;
        }
        
        .btn-cronometro {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
        
        .btn-iniciar {
            background-color: #87b7a4;
            color: white;
        }
        
        .btn-iniciar:hover {
            background-color: #76a894;
        }
        
        .btn-parar {
            background-color: #c58c6d;
            color: white;
        }
        
        .btn-parar:hover {
            background-color: #b57d5e;
        }
        
        .form-container {
            margin-top: 15px;
        }
        
        .form-label {
            font-size: 14px;
            color: #6b705c;
            margin-bottom: 3px;
        }
        
        .form-select, .form-control {
            font-size: 14px;
            padding: 8px;
        }
        
        .form-group {
            margin-bottom: 10px;
        }
        
        .status-rastreamento {
            font-size: 12px;
            text-align: center;
            margin-top: 5px;
            color: #6b705c;
        }
        
        /* Elementos que ficam escondidos quando minimizado */
        .cronometro-content {
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .cronometro-container.minimizado .cronometro-content {
            opacity: 0;
        }
        
        .cronometro-minimizado {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
            background-color: #f8f9fa;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .cronometro-container.minimizado .cronometro-minimizado {
            opacity: 1;
            pointer-events: all;
        }
        
        .tempo-mini {
            font-weight: bold;
            font-size: 14px;
            color: #6b705c;
        }
        
        /* Modo de registro simplificado */
        .modo-simples .form-grupo-extra {
            display: none;
        }
        
        .modo-simples .label-tarefa {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="cronometro-container" id="rastreador">
        <!-- Header com título e botões de ação -->
        <div class="header-rastreador" id="rastreador-header">
            <h5>Rastreador de Tempo</h5>
            <div class="action-buttons">
                <button id="btn-minimizar" title="Minimizar"><i class="bi bi-dash-lg"></i></button>
                <button id="btn-fechar" title="Fechar"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        
        <!-- Conteúdo principal que será ocultado ao minimizar -->
        <div class="cronometro-content">
            <!-- Display do tempo -->
            <!--<div class="tempo-display" id="tempo-display">00:00<span class="tempo-segundos">:00</span></div>-->
            <div class="tempo-display" id="tempo-display">00:00:00</div>
			
            <!-- Botão de iniciar/parar -->
            <button id="btn-cronometro" class="btn-cronometro btn-iniciar">
                <i class="bi bi-play-fill"></i> Iniciar
            </button>
            
            <!-- Formulário de registro (visível após parar) -->
            <div class="form-container" id="form-registro" style="display: none;">
                <form id="form-tempo">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="acao" value="registrar_tempo">
                    <input type="hidden" name="tempo_segundos" id="input-segundos" value="0">
                    <input type="hidden" name="data_hora_inicio" id="input-inicio" value="">
                    <input type="hidden" name="data_hora_fim" id="input-fim" value="">
                    
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="modo-simples" checked>
                        <label class="form-check-label" for="modo-simples">
                            Modo rápido (apenas selecionar tarefa)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="tarefa_id" class="form-label label-tarefa">Selecione a tarefa:</label>
                        <select class="form-select form-select-sm" id="tarefa_id" name="tarefa_id" required>
                            <option value="">Selecione uma tarefa...</option>
                            <?php foreach ($tarefas as $tarefa): ?>
                                <option value="<?php echo $tarefa['id']; ?>"><?php echo $tarefa['nome']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group form-grupo-extra">
                        <label for="observacoes" class="form-label">Observações:</label>
                        <textarea class="form-control form-control-sm" id="observacoes" name="observacoes" rows="2" placeholder="O que você fez neste período?"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-cronometro btn-iniciar">
                        <i class="bi bi-save"></i> Salvar Tempo
                    </button>
                </form>
            </div>
            
            <!-- Status do rastreamento -->
            <div class="status-rastreamento" id="status-rastreamento"></div>
        </div>
        
        <!-- Versão minimizada -->
        <div class="cronometro-minimizado">
            <div class="tempo-mini" id="tempo-mini">00:00:00</div>
            <button id="btn-maximizar" title="Maximizar"><i class="bi bi-arrows-angle-expand"></i></button>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos DOM
            const rastreador = document.getElementById('rastreador');
            const rastreadorHeader = document.getElementById('rastreador-header');
            const btnMinimizar = document.getElementById('btn-minimizar');
            const btnMaximizar = document.getElementById('btn-maximizar');
            const btnFechar = document.getElementById('btn-fechar');
            const btnCronometro = document.getElementById('btn-cronometro');
            const tempoDisplay = document.getElementById('tempo-display');
            const tempoMini = document.getElementById('tempo-mini');
            const formRegistro = document.getElementById('form-registro');
            const formTempo = document.getElementById('form-tempo');
            const statusRastreamento = document.getElementById('status-rastreamento');
            const inputSegundos = document.getElementById('input-segundos');
            const inputInicio = document.getElementById('input-inicio');
            const inputFim = document.getElementById('input-fim');
            const modoSimples = document.getElementById('modo-simples');
            
            // Variáveis de controle do cronômetro
            let cronometroAtivo = false;
            let tempoInicio = null;
            let tempoAtual = 0;
            let intervalId = null;
            let posicaoInicial = { x: 0, y: 0 };
            let posicaoRastreador = { x: 0, y: 0 };
            
            // Função para formatar o tempo (segundos -> HH:MM:SS)
            function formatarTempo(segundos) {
                const horas = Math.floor(segundos / 3600);
                const minutos = Math.floor((segundos % 3600) / 60);
                const segs = segundos % 60;
                
                return {
                    horasMin: `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}`,
                    segundos: `:${segs.toString().padStart(2, '0')}`,
                    completo: `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`
                };
            }
            
            // Função para atualizar o display do tempo
            function atualizarTempo() {
                const agora = new Date();
                const segundosDecorridos = Math.floor((agora - tempoInicio) / 1000);
                tempoAtual = segundosDecorridos;
                
                // Atualizar input escondido
                inputSegundos.value = tempoAtual;
                
                // Atualizar displays
                const tempoFormatado = formatarTempo(tempoAtual);
				tempoDisplay.innerHTML = tempoFormatado.horasMin + tempoFormatado.segundos;
                /* tempoDisplay.innerHTML = tempoFormatado.horasMin + '<span class="tempo-segundos">' + tempoFormatado.segundos + '</span>'; */
                tempoMini.textContent = tempoFormatado.completo;
            }
            
            // Função para iniciar o cronômetro
            function iniciarCronometro() {
                tempoInicio = new Date();
                inputInicio.value = tempoInicio.toISOString().slice(0, 19).replace('T', ' ');
                
                intervalId = setInterval(atualizarTempo, 1000);
                cronometroAtivo = true;
                
                btnCronometro.classList.remove('btn-iniciar');
                btnCronometro.classList.add('btn-parar');
                btnCronometro.innerHTML = '<i class="bi bi-stop-fill"></i> Parar';
                
                statusRastreamento.textContent = 'Rastreamento em andamento...';
                formRegistro.style.display = 'none';
            }
            
            // Função para parar o cronômetro
            function pararCronometro() {
                clearInterval(intervalId);
                cronometroAtivo = false;
                
                // Registrar hora de término
                const horaFim = new Date();
                inputFim.value = horaFim.toISOString().slice(0, 19).replace('T', ' ');
                
                btnCronometro.classList.remove('btn-parar');
                btnCronometro.classList.add('btn-iniciar');
                btnCronometro.innerHTML = '<i class="bi bi-play-fill"></i> Iniciar';
                
                // Mostrar formulário para registrar o tempo
                formRegistro.style.display = 'block';
                
                // Verificar se é modo simples
                verificarModoSimples();
                
                statusRastreamento.textContent = 'Rastreamento parado. Selecione uma tarefa para registrar.';
            }
            
            // Verificação do modo simples
            function verificarModoSimples() {
                if (modoSimples.checked) {
                    formRegistro.classList.add('modo-simples');
                } else {
                    formRegistro.classList.remove('modo-simples');
                }
            }
            
            // Função para reiniciar o cronômetro
            function reiniciarCronometro() {
                tempoAtual = 0;
                const tempoFormatado = formatarTempo(tempoAtual);
                tempoDisplay.innerHTML = tempoFormatado.horasMin + tempoFormatado.segundos;
				/* tempoDisplay.innerHTML = tempoFormatado.horasMin + '<span class="tempo-segundos">' + tempoFormatado.segundos + '</span>'; */
                tempoMini.textContent = tempoFormatado.completo;
                inputSegundos.value = 0;
            }
            
            // Event listeners
            btnCronometro.addEventListener('click', function() {
                if (cronometroAtivo) {
                    pararCronometro();
                } else {
                    reiniciarCronometro();
                    iniciarCronometro();
                }
            });
            
            btnMinimizar.addEventListener('click', function() {
                rastreador.classList.add('minimizado');
            });
            
            btnMaximizar.addEventListener('click', function() {
                rastreador.classList.remove('minimizado');
            });
            
            btnFechar.addEventListener('click', function() {
                if (cronometroAtivo) {
                    if (!confirm('O cronômetro está ativo. Deseja realmente fechar o rastreador?')) {
                        return;
                    }
                    pararCronometro();
                }
                rastreador.style.display = 'none';
            });
            
            // Evento de mudança no checkbox de modo simples
            modoSimples.addEventListener('change', verificarModoSimples);
            
            // Submissão do formulário via AJAX
            formTempo.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(formTempo);
                
                // Validação
                const tarefaId = formData.get('tarefa_id');
                if (!tarefaId) {
                    statusRastreamento.textContent = 'Por favor, selecione uma tarefa.';
                    return;
                }
                
                // Mostrar status de envio
                statusRastreamento.textContent = 'Registrando tempo...';
                
                // Enviar requisição
                fetch('rastreador_tempo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'sucesso') {
                        statusRastreamento.textContent = data.mensagem;
                        
                        // Limpar o formulário e escondê-lo
                        formTempo.reset();
                        formRegistro.style.display = 'none';
                        reiniciarCronometro();
                        
                        // Notificar o usuário
                        alert('Tempo registrado com sucesso!');
                    } else {
                        statusRastreamento.textContent = data.mensagem;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    statusRastreamento.textContent = 'Erro ao registrar tempo. Tente novamente.';
                });
            });
            
            // Funcionalidade de arrastar o rastreador
            rastreadorHeader.addEventListener('mousedown', iniciarArrasto);
            
            function iniciarArrasto(e) {
                e.preventDefault();
                
                posicaoInicial = {
                    x: e.clientX,
                    y: e.clientY
                };
                
                // Posição atual do rastreador
                const rect = rastreador.getBoundingClientRect();
                posicaoRastreador = {
                    x: rect.left,
                    y: rect.top
                };
                
                document.addEventListener('mousemove', moverRastreador);
                document.addEventListener('mouseup', pararArrasto);
            }
            
            function moverRastreador(e) {
                const deltaX = e.clientX - posicaoInicial.x;
                const deltaY = e.clientY - posicaoInicial.y;
                
                const novaX = posicaoRastreador.x + deltaX;
                const novaY = posicaoRastreador.y + deltaY;
                
                rastreador.style.position = 'fixed';
                rastreador.style.left = novaX + 'px';
                rastreador.style.top = novaY + 'px';
            }
            
            function pararArrasto() {
                document.removeEventListener('mousemove', moverRastreador);
                document.removeEventListener('mouseup', pararArrasto);
            }
            
            // Posicionar o rastreador no canto inferior direito da tela inicialmente
            function posicionarInicial() {
                const margemDireita = 20;
                const margemInferior = 20;
                
                rastreador.style.position = 'fixed';
                rastreador.style.bottom = margemInferior + 'px';
                rastreador.style.right = margemDireita + 'px';
                rastreador.style.top = 'auto';
                rastreador.style.left = 'auto';
            }
            
            // Inicializar posição e verificar modo simples
            posicionarInicial();
            verificarModoSimples();
        });
    </script>
</body>
</html>
