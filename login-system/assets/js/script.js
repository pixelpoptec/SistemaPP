// Validação do formulário de login
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            let isValid = true;

            // Validar e-mail
            if (!isValidEmail(email)) {
                showError('email', 'Por favor, insira um e-mail válido.');
                isValid = false;
            } else {
                clearError('email');
            }

            // Validar senha
            if (password.length < 6) {
                showError('password', 'A senha deve ter pelo menos 6 caracteres.');
                isValid = false;
            } else {
                clearError('password');
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    // Função para validar e-mail
    function isValidEmail(email) {
        const emailRegex = /
^
[^\s@]+@[^\s@]+\.[^\s@]+
$
/;
        return emailRegex.test(email);
    }

    // Função para mostrar erro
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.createElement('div');

        // Remover mensagem de erro existente, se houver
        clearError(fieldId);

        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        errorDiv.id = `${fieldId}-error`;

        field.classList.add('is-invalid');
        field.parentNode.appendChild(errorDiv);
    }

    // Função para limpar erro
    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const existingError = document.getElementById(`${fieldId}-error`);

        if (existingError) {
            existingError.remove();
        }

        field.classList.remove('is-invalid');
    }

    // Mostrar/ocultar senha
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
    }
});

// Fechar alertas automaticamente após 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
