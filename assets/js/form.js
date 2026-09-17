document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('denunciaForm');
    const steps = Array.from(form.querySelectorAll('.form-step'));
    const progressSteps = Array.from(document.querySelectorAll('.progress-step'));
    const btnAnterior = document.getElementById('btnAnterior');
    const btnProximo = document.getElementById('btnProximo');
    const btnEnviar = document.getElementById('btnEnviar');
    const btnVoltarSite = document.querySelector('.btn-voltar-site');
    const formMessage = document.getElementById('formMessage');

    let currentStep = 1;
    const totalSteps = steps.length;

    // ---------- Lógica condicional ----------
    const condicionais = Array.from(form.querySelectorAll('.campo.condicional'));

    function aplicarCondicionais() {
        condicionais.forEach(function (campo) {
            const [nomeCampo, valorEsperado] = campo.dataset.showIf.split('=');
            const inputsRelacionados = form.querySelectorAll('[name="' + nomeCampo + '"]');
            let valorAtual = '';

            inputsRelacionados.forEach(function (input) {
                if ((input.type === 'radio' || input.type === 'checkbox') && input.checked) {
                    valorAtual = input.value;
                } else if (input.type !== 'radio' && input.type !== 'checkbox') {
                    valorAtual = input.value;
                }
            });

            const exibir = valorAtual === valorEsperado;
            campo.style.display = exibir ? '' : 'none';

            const camposInternos = campo.querySelectorAll('input, select, textarea');
            camposInternos.forEach(function (input) {
                if (exibir) {
                    if (input.dataset.requiredQuandoVisivel === 'true' || input.hasAttribute('data-required-cond')) {
                        input.required = true;
                    }
                } else {
                    input.required = false;
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                }
            });
        });
    }

    // Marca campos condicionais obrigatórios (apenas quando visíveis)
    condicionais.forEach(function (campo) {
        const textarea = campo.querySelector('textarea, input[type="text"]');
        if (textarea) {
            textarea.setAttribute('data-required-cond', 'true');
        }
    });

    form.addEventListener('change', aplicarCondicionais);
    aplicarCondicionais();

    // ---------- Navegação entre etapas ----------
    function mostrarStep(numero) {
        steps.forEach(function (step) {
            step.classList.toggle('active', parseInt(step.dataset.step, 10) === numero);
        });

        progressSteps.forEach(function (ps) {
            const n = parseInt(ps.dataset.step, 10);
            ps.classList.toggle('active', n === numero);
            ps.classList.toggle('completed', n < numero);
        });

        btnAnterior.style.display = numero > 1 ? 'inline-block' : 'none';
        btnProximo.style.display = numero < totalSteps ? 'inline-block' : 'none';
        btnEnviar.style.display = numero === totalSteps ? 'inline-block' : 'none';
        if (btnVoltarSite) btnVoltarSite.style.display = numero === 1 ? 'inline-block' : 'none';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function validarStep(numero) {
        const step = steps[numero - 1];
        let valido = true;

        const campos = step.querySelectorAll('.campo');
        campos.forEach(function (campo) {
            if (campo.style.display === 'none') {
                campo.classList.remove('invalido');
                return;
            }

            const obrigatorios = campo.querySelectorAll('[required]');
            let campoValido = true;

            obrigatorios.forEach(function (input) {
                if (input.type === 'radio') {
                    const grupo = campo.querySelectorAll('input[name="' + input.name + '"]');
                    const algumMarcado = Array.from(grupo).some(function (r) { return r.checked; });
                    if (!algumMarcado) campoValido = false;
                } else if (!input.value.trim()) {
                    campoValido = false;
                }
            });

            campo.classList.toggle('invalido', !campoValido);
            if (!campoValido) valido = false;
        });

        return valido;
    }

    btnProximo.addEventListener('click', function () {
        if (!validarStep(currentStep)) {
            formMessage.style.display = 'block';
            formMessage.className = 'form-message erro';
            formMessage.textContent = 'Por favor, preencha todos os campos obrigatórios desta etapa.';
            return;
        }
        formMessage.style.display = 'none';
        if (currentStep < totalSteps) {
            currentStep++;
            mostrarStep(currentStep);
        }
    });

    btnAnterior.addEventListener('click', function () {
        if (currentStep > 1) {
            currentStep--;
            mostrarStep(currentStep);
        }
    });

    form.addEventListener('submit', function (e) {
        if (!validarStep(currentStep)) {
            e.preventDefault();
            formMessage.style.display = 'block';
            formMessage.className = 'form-message erro';
            formMessage.textContent = 'Por favor, preencha todos os campos obrigatórios desta etapa.';
            return;
        }

        // Captura o máximo de dados técnicos da máquina e ambiente
        try {
            const info = {
                resolucao: (window.screen ? window.screen.width + 'x' + window.screen.height : ''),
                janela: (window.innerWidth + 'x' + window.innerHeight),
                fuso_horario: (Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : ''),
                idioma: (navigator.language || navigator.userLanguage || ''),
                plataforma: (navigator.platform || ''),
                cores_cpu: (navigator.hardwareConcurrency || ''),
                memoria_ram: (navigator.deviceMemory ? navigator.deviceMemory + ' GB' : ''),
                touch: (navigator.maxTouchPoints > 0 ? 'Sim' : 'Não')
            };
            const inputDispositivo = document.getElementById('dispositivoDados');
            if (inputDispositivo) {
                inputDispositivo.value = JSON.stringify(info);
            }
        } catch (err) {}

        btnEnviar.disabled = true;
        btnEnviar.textContent = 'Enviando...';
    });

    mostrarStep(currentStep);
});
