/**
 * notifications.js - Gerencia exibição de notificações em pop-up
 */
document.addEventListener('DOMContentLoaded', function() {
    // Função para criar e exibir o pop-up
    function showNotification(message, type, details = null) {
        // Cria o container do pop-up se não existir
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            document.body.appendChild(container);
        }
        
        // Cria o elemento de notificação
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Adiciona botão de fechar
        const closeBtn = document.createElement('span');
        closeBtn.className = 'notification-close';
        closeBtn.innerHTML = '&times;';
        closeBtn.onclick = function() {
            notification.classList.add('notification-hiding');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        };
        
        // Adiciona ícone baseado no tipo
        const icon = document.createElement('div');
        icon.className = 'notification-icon';
        let iconContent = '';
        
        switch(type) {
            case 'error':
                iconContent = '&#9888;'; // Triângulo de aviso
                break;
            case 'success':
                iconContent = '&#10004;'; // Check
                break;
            case 'warning':
                iconContent = '&#8252;'; // Exclamação
                break;
            case 'info':
                iconContent = 'ℹ'; // Info
                break;
        }
        
        icon.innerHTML = iconContent;
        
        // Adiciona o título baseado no tipo
        const title = document.createElement('h4');
        title.className = 'notification-title';
        
        switch(type) {
            case 'error':
                title.textContent = 'Erro no Formulário';
                break;
            case 'success':
                title.textContent = 'Operação Concluída';
                break;
            case 'warning':
                title.textContent = 'Atenção';
                break;
            case 'info':
                title.textContent = 'Informação';
                break;
        }
        
        // Adiciona a mensagem
        const content = document.createElement('div');
        content.className = 'notification-content';
        content.innerHTML = message;
        
        // Adiciona detalhes se existirem
        if (details) {
            const detailsContainer = document.createElement('div');
            detailsContainer.className = 'notification-details';
            detailsContainer.innerHTML = details;
            content.appendChild(detailsContainer);
        }
        
        // Monta a notificação
        notification.appendChild(closeBtn);
        notification.appendChild(icon);
        notification.appendChild(title);
        notification.appendChild(content);
        
        // Adiciona ao container
        container.appendChild(notification);
        
        // Adiciona classe para animação de entrada
        setTimeout(() => {
            notification.classList.add('notification-visible');
        }, 10);
        
        // Configura para fechar automaticamente após 8 segundos (exceto erros)
        if (type !== 'error') {
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.add('notification-hiding');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 8000);
        }
    }
    
    // Verifica se existe uma notificação PHP para exibir
    if (typeof phpNotification !== 'undefined' && phpNotification) {
        showNotification(
            phpNotification.message, 
            phpNotification.type, 
            phpNotification.details ? phpNotification.details : null
        );
    }
    
    // Expõe a função globalmente para uso em outros scripts
    window.showNotification = showNotification;
});
