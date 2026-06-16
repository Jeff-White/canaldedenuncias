// Alterna a visibilidade dos campos de senha (botão "olho")
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-senha').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var alvo = document.getElementById(btn.dataset.alvo);
            if (!alvo) return;
            var mostrando = alvo.type === 'text';
            alvo.type = mostrando ? 'password' : 'text';
            btn.textContent = mostrando ? '👁' : '🙈';
            btn.setAttribute('aria-label', mostrando ? 'Mostrar senha' : 'Ocultar senha');
        });
    });
});
