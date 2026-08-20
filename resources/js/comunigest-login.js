const loginForm = document.querySelector('[data-community-login]');

if (loginForm) {
    const emailInput = loginForm.querySelector('input[name="email"]');
    const passwordInput = loginForm.querySelector('input[name="password"]');
    const submitButton = loginForm.querySelector('[data-login-submit]');
    const submitLabel = loginForm.querySelector('[data-login-label]');

    loginForm.querySelectorAll('[data-quick-login]').forEach((button) => {
        button.addEventListener('click', () => {
            emailInput.value = button.dataset.email ?? '';
            passwordInput.value = 'password';
            emailInput.dispatchEvent(new Event('input', { bubbles: true }));
            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
            submitButton.disabled = true;
            submitLabel.textContent = `Accediendo como ${button.textContent.trim().replace(/\s+/g, ' ')}…`;
            window.setTimeout(() => loginForm.requestSubmit(), 120);
        });
    });

    loginForm.addEventListener('submit', () => {
        submitButton.disabled = true;
        submitLabel.textContent = 'Accediendo…';
    });
}

document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
});
