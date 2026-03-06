<?php
session_start();
require_once '../config/auth.php';
verificaLogin();

// Obter o ID do usuário atual a partir da sessão
$usuario_id = $_SESSION['usuario_id'];

// Buscar tarefas disponíveis (não concluídas e não em espera)
// Buscar tarefas não concluídas para o select
// Apenas tarefas com status de fazendo é que podem ter o tempo rastreado
// Porque, se estou rastreando o tempo as abertas precisam ser alteradas para fazendo
// E as com status = 'esperando' não tem porque serem rastreadas, tem que mudar o status
$sql_tarefas = "SELECT id, nome FROM tarefas WHERE status = 'fazendo' ORDER BY nome";
$result_tarefas = $conn->query($sql_tarefas);
$tarefas = [];

while ($tarefa = $result_tarefas->fetch_assoc()) {
    $tarefas[] = $tarefa;
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();


/* $sql = "SELECT id, nome FROM tarefas WHERE
        usuario_id = ? AND
        status != 'concluido' AND
        status != 'esperando'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$tarefas = [];


while ($row = $result->fetch_assoc()) {
    $tarefas[] = $row;
}*/
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreador de Tempo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #6b705c;
            --color-secondary: #87b7a4;
            --color-accent: #c58c6d;
            --color-light: #ddbea9;
            --color-lighter: #f1e3d3;
            --color-lightest: #fff6eb;
            --color-dark: #000000;
            --color-white: #ffffff;
        }
        
        body {
            background-color: var(--color-lightest);
            font-family: Arial, sans-serif;
            min-width: 300px;
            min-height: 400px;
            max-width: 500px;
            padding: 15px;
        }
        
        .rastreador-container {
            background-color: var(--color-white);
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        h2 {
            color: var(--color-primary);
            margin: 0;
            font-size: 1.5rem;
        }
        
        .timer {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--color-primary);
            margin: 20px 0;
        }
        
        #btnControle {
            background-color: var(--color-secondary);
            color: var(--color-white);
            border: none;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 15px;
        }
        
        #btnControle:hover {
            background-color: var(--color-primary);
        }
        
        #btnControle.parar {
            background-color: var(--color-accent);
        }
        
        #btnControle.parar:hover {
            background-color: #b47a5e;
        }
        
        select.form-select {
            background-color: var(--color-lightest);
            border-color: var(--color-light);
            color: var(--color-primary);
            margin-bottom: 15px;
        }
        
        .observacoes {
            margin-top: 15px;
        }
        
        textarea {
            background-color: var(--color-lightest);
            border-color: var(--color-light);
            resize: none;
        }
        
        .minimizar {
            background: none;
            border: none;
            color: var(--color-primary);
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .status-info {
            color: var(--color-accent);
            margin: 10px 0;
            font-style: italic;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="rastreador-container">
        <div class="header">
            <h2>Rastreador de Tempo</h2>
            <!--<button id="btnMinimizar" class="minimizar">➖</button>-->
        </div>
        
        <form id="rastreadorForm">
            <div class="mb-3">
                <label for="tarefa" class="form-label">Selecione a Tarefa:</label>
                <select class="form-select" id="tarefa" required>
                    <option value="">Escolha uma tarefa</option>
                    <?php foreach ($tarefas as $tarefa) : ?>
                        <option value="<?= $tarefa['id'] ?>"><?= sprintf("%03d", $tarefa['id']) . " | " . htmlspecialchars($tarefa['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="timer" id="timer">00:00:00</div>
            <div class="status-info" id="statusInfo">Selecione uma tarefa para começar</div>
            
            <button type="button" id="btnControle" disabled>Iniciar</button>
            
            <div class="observacoes mt-3">
                <textarea class="form-control" id="observacoes" rows="3" placeholder="Observações (opcional)"></textarea>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnControle = document.getElementById('btnControle');
            const timer = document.getElementById('timer');
            const tarefaSelect = document.getElementById('tarefa');
            const observacoesText = document.getElementById('observacoes');
            const statusInfo = document.getElementById('statusInfo');
            const btnMinimizar = document.getElementById('btnMinimizar');
            
            let cronometroAtivo = false;
            let segundosTotais = 0;
            let intervalId = null;
            let registroId = null;
            let ultimoSalvamento = 0;
            let dataHoraInicio = null;
            
            // Verificar se há um cronômetro em andamento para este usuário
            verificarCronometroAtivo();
            
            tarefaSelect.addEventListener('change', function() {
                if (this.value) {
                    btnControle.removeAttribute('disabled');
                    verificarTempoExistente(this.value);
                } else {
                    btnControle.setAttribute('disabled', 'disabled');
                    statusInfo.textContent = 'Selecione uma tarefa para começar';
                }
            });
            
            btnControle.addEventListener('click', function() {
                if (cronometroAtivo) {
                    pararCronometro();
                } else {
                    iniciarCronometro();
                }
            });
            
            btnMinimizar.addEventListener('click', function() {
                // Minimiza a janela, mas mantém o cronômetro rodando
                window.resizeTo(300, 40);
                document.body.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 5px;">
                        <span id="miniTimer">${timer.textContent}</span>
                        <button id="btnMaximizar" style="background: none; border: none; cursor: pointer;">🔍</button>
                    </div>
                `;
                
                document.getElementById('btnMaximizar').addEventListener('click', function() {
                    location.reload();
                    window.resizeTo(400, 500);
                });
                
                // Continua atualizando o cronômetro
                if (cronometroAtivo) {
                    setInterval(() => {
                        document.getElementById('miniTimer').textContent = formatarTempo(segundosTotais);
                    }, 1000);
                }
            });
            
            function iniciarCronometro() {
                const tarefaId = tarefaSelect.value;
                if (!tarefaId) return;
                
                cronometroAtivo = true;
                dataHoraInicio = new Date();
                btnControle.textContent = 'Parar';
                btnControle.classList.add('parar');
                
                // Desabilitar seleção de tarefa
                tarefaSelect.setAttribute('disabled', 'disabled');
                
                // Iniciar registro no banco
                fetch('api/iniciar_tempo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tarefa_id: tarefaId,
                        usuario_id: <?= $usuario_id ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        registroId = data.registro_id;
                        statusInfo.textContent = 'Tempo sendo rastreado...';
                        
                        // Iniciar contagem
                        intervalId = setInterval(function() {
                            segundosTotais++;
                            timer.textContent = formatarTempo(segundosTotais);
                            
                            // Salvar a cada 2 minutos (120 segundos)
                            if (segundosTotais - ultimoSalvamento >= 120) {
                                salvarProgresso(false);
                                ultimoSalvamento = segundosTotais;
                            }
                        }, 1000);
                    } else {
                        alert('Erro ao iniciar rastreamento: ' + data.message);
                        cronometroAtivo = false;
                        btnControle.textContent = 'Iniciar';
                        btnControle.classList.remove('parar');
                        tarefaSelect.removeAttribute('disabled');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Ocorreu um erro ao iniciar o rastreamento.');
                    cronometroAtivo = false;
                    btnControle.textContent = 'Iniciar';
                    btnControle.classList.remove('parar');
                    tarefaSelect.removeAttribute('disabled');
                });
            }
            
            function pararCronometro() {
                if (intervalId) {
                    clearInterval(intervalId);
                }
                
                salvarProgresso(true);
                
                cronometroAtivo = false;
                btnControle.textContent = 'Iniciar';
                btnControle.classList.remove('parar');
                tarefaSelect.removeAttribute('disabled');
                statusInfo.textContent = 'Tempo parado. Total registrado.';
                
                // Resetar
                segundosTotais = 0;
                ultimoSalvamento = 0;
                registroId = null;
                timer.textContent = formatarTempo(segundosTotais);
            }
            
            function salvarProgresso(finalizar = false) {
                fetch('api/atualizar_tempo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        registro_id: registroId,
                        segundos_totais: segundosTotais,
                        observacoes: observacoesText.value,
                        finalizar: finalizar
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Erro ao salvar progresso:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                });
            }
            
            function formatarTempo(segundos) {
                const horas = Math.floor(segundos / 3600);
                const minutos = Math.floor((segundos % 3600) / 60);
                const segs = segundos % 60;
                
                return [
                    horas.toString().padStart(2, '0'),
                    minutos.toString().padStart(2, '0'),
                    segs.toString().padStart(2, '0')
                ].join(':');
            }
            
            function verificarCronometroAtivo() {
                fetch('api/verificar_cronometro.php')
                .then(response => response.json())
                .then(data => {
                    if (data.ativo) {
                        // Há um cronômetro ativo
                        cronometroAtivo = true;
                        registroId = data.registro_id;
                        /* segundosTotais = data.segundos_totais; */
                        segundosTotais = 0;
                        
                        // Selecionar a tarefa correspondente
                        tarefaSelect.value = data.tarefa_id;
                        tarefaSelect.setAttribute('disabled', 'disabled');
                        
                        // Preencher observações
                        if (data.observacoes) {
                            observacoesText.value = data.observacoes;
                        }
                        
                        // Calcular segundos desde o último salvamento
                        const ultimaAtualizacao = new Date(data.ultima_atualizacao);
                        const agora = new Date();
                        const segundosPassados = Math.floor((agora - ultimaAtualizacao) / 1000);
                        
                        // Atualizar o contador
                        segundosTotais += segundosPassados;
                        ultimoSalvamento = segundosTotais;
                        
                        // Atualizar interface
                        timer.textContent = formatarTempo(segundosTotais);
                        btnControle.textContent = 'Parar';
                        btnControle.classList.add('parar');
                        btnControle.removeAttribute('disabled');
                        statusInfo.textContent = 'Tempo sendo rastreado...';
                        
                        // Reiniciar contagem
                        intervalId = setInterval(function() {
                            segundosTotais++;
                            timer.textContent = formatarTempo(segundosTotais);
                            
                            // Salvar a cada 2 minutos (120 segundos)
                            if (segundosTotais - ultimoSalvamento >= 120) {
                                salvarProgresso(false);
                                ultimoSalvamento = segundosTotais;
                            }
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar cronômetro ativo:', error);
                });
            }
            
            function verificarTempoExistente(tarefaId) {
                fetch(`api/verificar_tempo_tarefa.php?tarefa_id=${tarefaId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.tempoRegistrado) {
                        statusInfo.textContent = `Tempo já registrado: ${data.tempoFormatado}`;
                    } else {
                        statusInfo.textContent = 'Nenhum tempo registrado para esta tarefa.';
                    }
                })
                .catch(error => {
                    console.error('Erro:', error.message);
                    statusInfo.textContent = 'Erro ao verificar tempo existente: ' + error.message;
                });
            }
            
            // Garantir que a janela continue funcionando mesmo se a janela principal fechar
            window.onbeforeunload = function() {
                if (cronometroAtivo) {
                    // Salvar progresso antes de fechar
                    salvarProgresso(false);
                    return "O cronômetro continuará rodando em segundo plano.";
                }
            };
        });
    </script>
</body>
</html>
