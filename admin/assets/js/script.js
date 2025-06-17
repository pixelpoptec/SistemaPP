// Função para mostrar/ocultar alertas automaticamente após alguns segundos
document.addEventListener('DOMContentLoaded', function() {
    // Seleciona todos os alertas
    const alertas = document.querySelectorAll('.alert');
    
    // Configura um timer para esconder cada alerta após 5 segundos
    alertas.forEach(alerta => {
        setTimeout(() => {
            alerta.style.opacity = '0';
            alerta.style.transition = 'opacity 0.5s';
            
            // Remove o alerta do DOM após a transição
            setTimeout(() => {
                alerta.remove();
            }, 500);
        }, 5000);
    });
    
    // Validação de formulário de senha
    const formRegistro = document.querySelector('form');
    
    if (formRegistro) {
        formRegistro.addEventListener('submit', function(event) {
            const senhaInput = document.getElementById('senha');
            const confirmaSenhaInput = document.getElementById('confirma_senha');
            
            if (senhaInput && confirmaSenhaInput) {
                const senha = senhaInput.value;
                const confirmaSenha = confirmaSenhaInput.value;
                
                // Verificar se as senhas coincidem
                if (senha !== confirmaSenha) {
                    event.preventDefault();
                    alert('As senhas não coincidem!');
                    return false;
                }
                
                // Verificar força da senha
                if (senha.length < 8) {
                    event.preventDefault();
                    alert('A senha deve ter pelo menos 8 caracteres!');
                    return false;
                }
                
                // Verificar se contém pelo menos um número
                if (!/[0-9]/.test(senha)) {
                    event.preventDefault();
                    alert('A senha deve conter pelo menos um número!');
                    return false;
                }
                
                // Verificar se contém pelo menos uma letra maiúscula
                if (!/[A-Z]/.test(senha)) {
                    event.preventDefault();
                    alert('A senha deve conter pelo menos uma letra maiúscula!');
                    return false;
                }
                
                // Verificar se contém pelo menos uma letra minúscula
                if (!/[a-z]/.test(senha)) {
                    event.preventDefault();
                    alert('A senha deve conter pelo menos uma letra minúscula!');
                    return false;
                }
            }
        });
    }
    
    // Confirmação para ações importantes
    const btnPerigosos = document.querySelectorAll('.btn-warning, .btn-danger');
    
    btnPerigosos.forEach(btn => {
        btn.addEventListener('click', function(event) {
            if (!confirm('Tem certeza que deseja realizar esta ação?')) {
                event.preventDefault();
                return false;
            }
        });
    });
});

// Função para verificar tempo de inatividade e fazer logout automático
(function() {
    let tempoInatividade = 0;
    const tempoLimite = 300 * 60; // 60 minutos em segundos
    
    // Reinicia o contador quando o usuário interage com a página
    function reiniciarContador() {
        tempoInatividade = 0;
    }
    
    // Incrementa o contador a cada segundo
    setInterval(function() {
        tempoInatividade++;
        
        // Se o tempo de inatividade ultrapassar o limite, faz logout
        if (tempoInatividade >= tempoLimite) {
            window.location.href = '/pp-files/admin/logout.php';
        }
    }, 1000);
    
    // Eventos que reiniciam o contador
    document.addEventListener('mousemove', reiniciarContador);
    document.addEventListener('keypress', reiniciarContador);
    document.addEventListener('click', reiniciarContador);
    document.addEventListener('scroll', reiniciarContador);
})();

// Abrir rastreador de tempo em janela flutuante
// Jaime Pimenta - 16/06/25
// Abrir rastreador de tempo em janela flutuante
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
}