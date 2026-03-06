<?php
/**
 * notification.php - Sistema de notificações para formulários
 * 
 * Este arquivo gerencia a exibição de notificações e mensagens de erro
 * para os formulários do site de impressão 3D.
 */

class Notification {
    /**
     * Configura uma notificação para ser exibida
     * 
     * @param string $message Mensagem a ser exibida
     * @param string $type Tipo da notificação (error, success, warning, info)
     * @param array $details Detalhes adicionais (opcional)
     * @return void
     */
    public static function set($message, $type = 'error', $details = []) {
        // Inicia a sessão se ainda não estiver ativa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['notification'] = [
            'message' => $message,
            'type' => $type,
            'details' => $details
        ];
    }
    
    /**
     * Retorna a notificação atual e limpa da sessão
     * 
     * @return array|null A notificação ou null se não houver
     */
    public static function get() {
        // Inicia a sessão se ainda não estiver ativa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
        
        // Limpa a notificação da sessão após recuperá-la
        if (isset($_SESSION['notification'])) {
            unset($_SESSION['notification']);
        }
        
        return $notification;
    }
    
    /**
     * Verifica se existe uma notificação pendente
     * 
     * @return bool
	 * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function exists() {
        // Inicia a sessão se ainda não estiver ativa
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['notification']);
    }
    
    /**
     * Formata uma lista de erros em HTML
     * 
     * @param array $errors Lista de mensagens de erro
     * @return string HTML formatado
     */
    public static function formatErrorList($errors) {
        if (empty($errors)) {
            return '';
        }
        
        $html = '<ul class="error-list">';
        foreach ($errors as $error) {
            $html .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
}
?>
