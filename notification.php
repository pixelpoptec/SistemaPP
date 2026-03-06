<?php

/**
 * notification.php - Sistema de notificações para formulários
 *
 * Este arquivo gerencia a exibição de notificações e mensagens de erro
 * para os formulários do site de impressão 3D.
 */

namespace SistemaPP;

class Notification
{
    /**
     * Configura uma notificação para ser exibida
     *
     * @param string $message Mensagem a ser exibida
     * @param string $type    Tipo da notificação (error, success, warning, info)
     * @param array  $details Detalhes adicionais (opcional)
     * @return void
     */
    public static function set($message, $type = 'error', $details = [])
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['notification'] = [
            'message' => $message,
            'type'    => $type,
            'details' => $details,
        ];
    }

    /**
     * Retorna a notificação atual e limpa da sessão
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return array|null A notificação ou null se não houver
     */
    public static function get()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;

        if (isset($_SESSION['notification'])) {
            unset($_SESSION['notification']);
        }

        return $notification;
    }

    /**
     * Verifica se existe uma notificação pendente
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @return bool
     */
    public static function exists()
    {
        if (session_status() === PHP_SESSION_NONE) {
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
    public static function formatErrorList($errors)
    {
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
