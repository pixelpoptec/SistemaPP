<?php
session_start();
require_once '../../config/auth.php';
require_once '../../config/functions.php';
verificaLogin();

// Receber dados via POST como JSON
$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (!$input || !isset($input['registro_id']) || !isset($input['segundos_totais'])) {
    $response['message'] = 'Dados incompletos';
    echo json_encode($response);
    exit;
}

$registro_id = (int)$input['registro_id'];
$segundos_totais = (int)$input['segundos_totais'];
$observacoes = isset($input['observacoes']) ? $input['observacoes'] : '';
$finalizar = isset($input['finalizar']) ? (bool)$input['finalizar'] : false;

// Verificar se o registro existe e pertence ao usuário logado
$sql_check = "SELECT tr.id, tr.tarefa_id 
              FROM tempo_rastreamento tr
              JOIN tarefas t ON tr.tarefa_id = t.id
              WHERE tr.id = ? AND tr.usuario_id = ?";

$stmt = $conn->prepare($sql_check);
$stmt->bind_param("ii", $registro_id, $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Registro não encontrado ou não pertence ao usuário';
    echo json_encode($response);
    exit;
}

$row = $result->fetch_assoc();
$tarefa_id = $row['tarefa_id'];

// Calcular horas e minutos
$tempo_horas = floor($segundos_totais / 3600);
$tempo_minutos = floor(($segundos_totais % 3600) / 60);

// Início da transação
$conn->begin_transaction();

try {
    // Atualizar o registro de rastreamento
	//A rotina faz o update na tabela de rastreamento
	//Depois faz os elect na tabela de tarefas e atualiza o Tempo
	//Jaime Pimenta - 17/06
   
    if ($finalizar) {
		$data_hora_fim = date('Y-m-d H:i:s');
		$sql_update = "UPDATE tempo_rastreamento SET segundos_totais = ?, tempo_horas = ?, tempo_minutos = ?, observacoes = ?, data_hora_fim = ? WHERE id = ?";
		$stmt = $conn->prepare($sql_update);
		$stmt->bind_param("iiissi", $segundos_totais, $tempo_horas, $tempo_minutos, $observacoes, $data_hora_fim, $registro_id);
		$stmt->execute();  
		$conn->commit();

		$sql_tarefas = "SELECT tempo_horas, tempo_minutos FROM tarefas WHERE id = ?";
		$stmt = $conn->prepare($sql_tarefas);
		$stmt->bind_param("i", $tarefa_id);
		$stmt->execute();
		$result = $stmt->get_result();
		$row = $result->fetch_assoc();

		$novo_tempo_minutos = $row['tempo_minutos'] + $tempo_minutos;
		$novo_tempo_horas = $row['tempo_horas'] + $tempo_horas;

		// Correção caso minutos ultrapassem 60
		if ($novo_tempo_minutos >= 60) {
			$novo_tempo_horas += floor($novo_tempo_minutos / 60);
			$novo_tempo_minutos = $novo_tempo_minutos % 60;
		}
		
		//Atualizar apenas a última tarefa que foi acabada de fechar
        $sql_update_tarefa = "UPDATE tarefas SET tempo_horas = ?, tempo_minutos = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update_tarefa);
        $stmt->bind_param("iii", $novo_tempo_horas, $novo_tempo_minutos, $tarefa_id);
        $stmt->execute();
		
        // Registrar log
		$usuario_id = $_SESSION['usuario_id'];
        registrarLog($usuario_id, 'TEMPO_REGISTRADO', "Tempo registrado para tarefa ID: $tarefa_id ($tempo_horas h $tempo_minutos min)");		
		
		$conn->commit(); 

    } else {
		$sql_update = "UPDATE tempo_rastreamento SET segundos_totais = ?, tempo_horas = ?, tempo_minutos = ?, observacoes = ? WHERE id = ?";
		$stmt = $conn->prepare($sql_update);
		$stmt->bind_param("iiisi", $segundos_totais, $tempo_horas, $tempo_minutos, $observacoes, $registro_id);
		$stmt->execute();  
		$conn->commit();		
    }

/*     if ($finalizar) {
        // Atualizar o tempo total na tarefa
        // Precisamos somar todo o tempo rastreado para esta tarefa
        $sql_sum = "SELECT SUM(segundos_totais) as total_segundos 
                    FROM tempo_rastreamento 
                    WHERE tarefa_id = ?";
        
        $stmt = $conn->prepare($sql_sum);
        $stmt->bind_param("i", $tarefa_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $total_segundos = $row['total_segundos'] ?? 0;
        // Arredondando para cima
        $total_minutos = ceil($total_segundos / 60);
        
        // Atualizar a tarefa com o tempo total
        $sql_update_tarefa = "UPDATE tarefas SET tempo_gasto = ? WHERE id = ?";
        $stmt = $conn->prepare($sql_update_tarefa);
        $stmt->bind_param("ii", $total_minutos, $tarefa_id);
        $stmt->execute();
		$conn->commit();
    } */
    
    $response = [
        'success' => true,
        'message' => $finalizar ? 'Rastreamento finalizado e salvo' : 'Progresso salvo'
    ];
} catch (Exception $e) {
    // Rollback em caso de erro
    $conn->rollback();
    $response['message'] = 'Erro ao atualizar tempo: ' . $e->getMessage();
	
	//Jaime Pimenta - 24/06
	/*	$arquivo = 'arquivo_' . date('Ymd_His') . '.txt';;
	$conteudo = $response['message'];
	file_put_contents($arquivo, $conteudo);		*/
}

function atualizaTempoRastreamento($conn, $registro_id, $segundos_totais, $tempo_horas, $tempo_minutos, $observacoes, $pasta_log = 'logs') {
    //atualizaTempoRastreamento($conn, $registro_id, $segundos_totais, $tempo_horas, $tempo_minutos, $observacoes);
	// Ativa erros do MySQLi
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Garante que a pasta exista
    if (!file_exists($pasta_log)) {
        mkdir($pasta_log, 0777, true);
    }

    // Arquivo de log
    $log_file = $pasta_log . '/update_log_' . date('Ymd_His') . '.txt';
    ob_start(); // Começa captura de log

    echo "==== INÍCIO DA EXECUÇÃO ====\n";
    echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";

    // Mostra os dados recebidos
    echo "\n>>> Dados Recebidos:\n";
    var_dump($registro_id, $segundos_totais, $tempo_horas, $tempo_minutos, $observacoes);

    // Checa registro atual
    $stmt_check = $conn->prepare("SELECT * FROM tempo_rastreamento WHERE id = ?");
    $stmt_check->bind_param("i", $registro_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $dados_antes = $result->fetch_assoc();

    echo "\n>>> Antes do UPDATE:\n";
    print_r($dados_antes);

    // Define data/hora fim
    $data_hora_fim = date('Y-m-d H:i:s');

    // Realiza o UPDATE
    try {
        $sql_update = "UPDATE tempo_rastreamento 
                       SET segundos_totais = ?, tempo_horas = ?, tempo_minutos = ?, observacoes = ?, data_hora_fim = ? 
                       WHERE id = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("iiissi", $segundos_totais, $tempo_horas, $tempo_minutos, $observacoes, $data_hora_fim, $registro_id);
        $stmt->execute();
		$conn->commit(); 

        echo "\n>>> UPDATE executado com sucesso.\n";
        echo "Linhas afetadas: " . $stmt->affected_rows . "\n";

        // Verifica depois
        $stmt_check2 = $conn->prepare("SELECT * FROM tempo_rastreamento WHERE id = ?");
        $stmt_check2->bind_param("i", $registro_id);
        $stmt_check2->execute();
        $result2 = $stmt_check2->get_result();
        $dados_depois = $result2->fetch_assoc();

        echo "\n>>> Depois do UPDATE:\n";
        print_r($dados_depois);

    } catch (Exception $e) {
        echo "\n>>> ERRO no UPDATE:\n";
        echo $e->getMessage() . "\n";
    }

    echo "==== FIM DA EXECUÇÃO ====\n";

    // Salva o log no arquivo
    $conteudo_log = ob_get_clean();
    file_put_contents($log_file, $conteudo_log);

    // Retorna caminho do log (pra possível uso)
    //return $log_file;
}

echo json_encode($response);
?>
