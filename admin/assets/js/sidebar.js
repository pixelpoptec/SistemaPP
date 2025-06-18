/**
 * Sidebar Responsiva - Script de controle
 * 
 * Este script gerencia o comportamento da sidebar responsiva,
 * incluindo abertura/fechamento, navegação por teclado e 
 * gestão de estados de ARIA para acessibilidade.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar-menu');
    const sidebarClose = document.getElementById('sidebar-close');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    const body = document.body;
    
    // Elementos focáveis dentro da sidebar para trapear o foco
    const focusableElements = sidebar.querySelectorAll('a, button');
    const firstFocusableElement = focusableElements[0];
    const lastFocusableElement = focusableElements[focusableElements.length - 1];
    
    /**
     * Abre a sidebar e configura os atributos de acessibilidade
     */
    function openSidebar() {
        body.classList.add('sidebar-active');
        sidebarToggle.setAttribute('aria-expanded', 'true');
        sidebar.setAttribute('aria-hidden', 'false');
        sidebarBackdrop.setAttribute('aria-hidden', 'false');
        sidebarToggle.classList.add('menu-open');
        
        // Foca no primeiro elemento quando abre
        setTimeout(() => {
            firstFocusableElement.focus();
        }, 300);
        
        // Adiciona listener para tecla Escape
        document.addEventListener('keydown', handleEscapeKey);
    }
    
    /**
     * Fecha a sidebar e restaura os atributos de acessibilidade
     */
    function closeSidebar() {
        body.classList.remove('sidebar-active');
        sidebarToggle.setAttribute('aria-expanded', 'false');
        sidebar.setAttribute('aria-hidden', 'true');
        sidebarBackdrop.setAttribute('aria-hidden', 'true');
        sidebarToggle.classList.remove('menu-open');
        
        // Retorna o foco para o botão toggle
        sidebarToggle.focus();
        
        // Remove listener de Escape
        document.removeEventListener('keydown', handleEscapeKey);
    }
    
    /**
     * Fecha a sidebar quando a tecla Escape é pressionada
     * @param {KeyboardEvent} event - O evento de tecla
     */
    function handleEscapeKey(event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    }
    
    /**
     * Trap de foco para manter o foco dentro da sidebar quando aberta
     * @param {KeyboardEvent} event - O evento de tecla
     */
    function trapFocus(event) {
        // Só aplica trap de foco quando o menu está aberto
        if (!body.classList.contains('sidebar-active')) return;
        
        if (event.key === 'Tab') {
            // Shift + Tab pressionados
            if (event.shiftKey) {
                // Se o foco estiver no primeiro elemento, move para o último
                if (document.activeElement === firstFocusableElement) {
                    event.preventDefault();
                    lastFocusableElement.focus();
                }
            } 
            // Tab pressionado
            else {
                // Se o foco estiver no último elemento, move para o primeiro
                if (document.activeElement === lastFocusableElement) {
                    event.preventDefault();
                    firstFocusableElement.focus();
                }
            }
        }
    }
    
    // Event Listeners
    sidebarToggle.addEventListener('click', function() {
        // Toggle a visibilidade da sidebar
        if (body.classList.contains('sidebar-active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    // Fechar sidebar ao clicar no botão X
    sidebarClose.addEventListener('click', closeSidebar);
    
    // Fechar sidebar ao clicar fora (no backdrop)
    sidebarBackdrop.addEventListener('click', closeSidebar);
    
    // Adicionar trap de foco
    sidebar.addEventListener('keydown', trapFocus);
    
    // Verificar tamanho de tela ao carregar e redimensionar
    function checkScreenSize() {
        // Em telas grandes, garantimos que a sidebar esteja visível e propriamente configurada
        if (window.innerWidth > 768) {
            sidebar.setAttribute('aria-hidden', 'false');
            body.classList.remove('sidebar-active');
            sidebarToggle.classList.remove('menu-open');
        } else {
            // Em telas pequenas, a sidebar começa fechada
            sidebar.setAttribute('aria-hidden', 'true');
            body.classList.remove('sidebar-active');
        }
    }
    
    // Checar ao carregar
    checkScreenSize();
    
    // Checar ao redimensionar
    window.addEventListener('resize', checkScreenSize);
    
    // Otimização de desempenho para evento resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(checkScreenSize, 250);
    });
});
