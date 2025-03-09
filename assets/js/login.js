// Login page specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Clear any previous error messages
            errorMessage.classList.add('hidden');

            // Get form data
            const formData = new FormData(loginForm);

            // Submit form via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '/api/auth.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            // Redirect to dashboard on success
                            window.location.href = response.redirect || '/dashboard';
                        } else {
                            // Show error message
                            errorText.textContent = response.message || 'Login failed. Please try again.';
                            errorMessage.classList.remove('hidden');
                        }
                    } catch (e) {
                        // Show generic error message
                        errorText.textContent = 'An error occurred. Please try again.';
                        errorMessage.classList.remove('hidden');
                    }
                }
            };
            xhr.send(formData);
        });
    }

    // Check for timeout message in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('timeout') && urlParams.get('timeout') === '1') {
        errorText.textContent = 'Your session has timed out. Please log in again.';
        errorMessage.classList.remove('hidden');
        errorMessage.classList.remove('bg-red-100', 'border-red-400', 'text-red-700');
        errorMessage.classList.add('bg-yellow-100', 'border-yellow-400', 'text-yellow-700');
    }
});