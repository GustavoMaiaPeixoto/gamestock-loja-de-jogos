/* =========================================================
   GAMESTOCK - validacao.js
   Validação de formulários no navegador (JavaScript puro).

   Como usar: no <form> coloque  data-validar novalidate.
   Regras vêm dos atributos HTML (required, minlength, min, type="email"...)
   e destes atributos extras nos campos:
     data-mascara="cpf|telefone|cep"  -> aplica máscara e valida o formato
     data-igual="#outroCampo"         -> valor precisa ser igual ao do outro campo
   Obs.: o servidor (PHP) valida tudo de novo; isto é só para melhorar a experiência.
========================================================= */

(function () {
    'use strict';

    /* ---------- Máscaras ---------- */
    var mascaras = {
        cpf: function (v) {
            v = v.replace(/\D/g, '').slice(0, 11);
            return v.replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        },
        telefone: function (v) {
            v = v.replace(/\D/g, '').slice(0, 11);
            if (v.length > 10) { return v.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3'); }
            if (v.length > 6)  { return v.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3'); }
            if (v.length > 2)  { return v.replace(/(\d{2})(\d{0,5})/, '($1) $2'); }
            return v;
        },
        cep: function (v) {
            v = v.replace(/\D/g, '').slice(0, 8);
            return v.length > 5 ? v.slice(0, 5) + '-' + v.slice(5) : v;
        }
    };

    /* ---------- Regras extras ---------- */
    function cpfValido(cpf) {
        cpf = cpf.replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) { return false; }

        for (var t = 9; t < 11; t++) {
            var soma = 0;
            for (var i = 0; i < t; i++) { soma += Number(cpf[i]) * ((t + 1) - i); }
            if (Number(cpf[t]) !== ((10 * soma) % 11) % 10) { return false; }
        }
        return true;
    }

    function mensagemNativa(c) {
        var v = c.validity;
        if (v.valueMissing)   { return c.type === 'checkbox' ? 'Marque esta opção.' : 'Preencha este campo.'; }
        if (v.typeMismatch)   { return c.type === 'email' ? 'Informe um e-mail válido.' : 'Valor inválido.'; }
        if (v.rangeUnderflow) { return 'O valor mínimo é ' + c.min + '.'; }
        if (v.rangeOverflow)  { return 'O valor máximo é ' + c.max + '.'; }
        if (v.stepMismatch)   { return 'Informe um valor válido.'; }
        if (v.patternMismatch){ return 'Formato inválido.'; }
        return c.validationMessage || 'Valor inválido.';
    }

    // Retorna a mensagem de erro do campo ('' = válido)
    function erroDoCampo(c) {
        if (!c.checkValidity()) { return mensagemNativa(c); }

        var valor = c.value.trim();

        if (valor !== '' && c.minLength > 0 && c.value.length < c.minLength) {
            return 'Use pelo menos ' + c.minLength + ' caracteres.';
        }

        if (valor !== '') {
            var tipo = c.dataset.mascara;

            if (tipo === 'cpf' && !cpfValido(valor)) { return 'CPF inválido.'; }

            if (tipo === 'telefone') {
                var d = valor.replace(/\D/g, '');
                if (d.length < 10 || d.length > 11) { return 'Telefone inválido. Use DDD + número.'; }
            }

            if (tipo === 'cep' && !/^\d{5}-?\d{3}$/.test(valor)) { return 'CEP inválido. Use 00000-000.'; }
        }

        if (c.dataset.igual) {
            var outro = document.querySelector(c.dataset.igual);
            if (outro && outro.value !== c.value) { return 'Os valores não conferem.'; }
        }

        return '';
    }

    /* ---------- Exibição do erro junto do campo ---------- */
    function mostrar(c, msg) {
        var chave = c.id || c.name;
        var el = c.parentNode.querySelector('.erro-campo[data-for="' + chave + '"]');

        if (!msg) {
            if (el) { el.remove(); }
            c.classList.remove('campo-invalido');
            c.removeAttribute('aria-invalid');
            return;
        }

        if (!el) {
            el = document.createElement('small');
            el.className = 'erro-campo';
            el.dataset['for'] = chave;
            c.insertAdjacentElement('afterend', el);
        }

        el.textContent = msg;
        c.classList.add('campo-invalido');
        c.setAttribute('aria-invalid', 'true');
    }

    function validarCampo(c) {
        var msg = erroDoCampo(c);
        mostrar(c, msg);
        return msg === '';
    }

    function campos(form) {
        return Array.prototype.filter.call(form.elements, function (c) {
            return /^(INPUT|SELECT|TEXTAREA)$/.test(c.tagName) &&
                   ['hidden', 'submit', 'button', 'file', 'reset'].indexOf(c.type) === -1 &&
                   !c.disabled;
        });
    }

    /* ---------- Ligação nos formulários ---------- */
    document.addEventListener('DOMContentLoaded', function () {

        // Máscaras (também formata o valor que já veio preenchido)
        document.querySelectorAll('[data-mascara]').forEach(function (c) {
            var fn = mascaras[c.dataset.mascara];
            if (!fn) { return; }

            if (c.value) { c.value = fn(c.value); }
            c.addEventListener('input', function () { c.value = fn(c.value); });
        });

        document.querySelectorAll('form[data-validar]').forEach(function (form) {

            campos(form).forEach(function (c) {
                c.addEventListener('blur', function () { validarCampo(c); });

                // Depois de um erro, revalida enquanto o usuário corrige
                c.addEventListener('input', function () {
                    if (c.classList.contains('campo-invalido')) { validarCampo(c); }
                });

                // Se mudar a senha, revalida a confirmação
                if (c.id) {
                    document.querySelectorAll('[data-igual="#' + c.id + '"]').forEach(function (dep) {
                        c.addEventListener('input', function () {
                            if (dep.value !== '') { validarCampo(dep); }
                        });
                    });
                }
            });

            form.addEventListener('submit', function (ev) {
                var primeiro = null;

                campos(form).forEach(function (c) {
                    if (!validarCampo(c) && !primeiro) { primeiro = c; }
                });

                if (primeiro) {
                    ev.preventDefault();
                    primeiro.focus();
                    primeiro.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                // Evita enviar duas vezes (desabilita depois que os dados já foram coletados)
                var botao = form.querySelector('button[type="submit"]');
                if (botao) {
                    setTimeout(function () { botao.disabled = true; }, 0);
                }
            });
        });
    });

    // Voltar pelo navegador não deve deixar o botão travado
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-validar] button[type="submit"]').forEach(function (b) {
            b.disabled = false;
        });
    });
})();
