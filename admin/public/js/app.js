/* =========================================================
   GAMESTOCK - app.js
   - Modal de exclusão (jogos e usuários)
   - Pré-visualização da imagem escolhida
   - Some sozinho com as mensagens de sucesso
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // ---------- MODAL DE EXCLUSÃO ----------
    var modal = document.getElementById('deleteModal');

    if (modal) {
        document.querySelectorAll('[data-excluir]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                modal.querySelector('input[name="id"]').value = btn.dataset.id;
                modal.querySelector('.js-nome').textContent = btn.dataset.nome;
                modal.showModal();
            });
        });

        modal.querySelectorAll('[data-fechar]').forEach(function (btn) {
            btn.addEventListener('click', function () { modal.close(); });
        });

        // Clicar fora da caixa fecha o modal
        modal.addEventListener('click', function (ev) {
            var r = modal.getBoundingClientRect();
            var fora = ev.clientX < r.left || ev.clientX > r.right ||
                       ev.clientY < r.top  || ev.clientY > r.bottom;
            if (fora) { modal.close(); }
        });
    }

    // ---------- PRÉ-VISUALIZAÇÃO DE IMAGEM ----------
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        var img = document.querySelector(input.dataset.preview);

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];

            if (!file) { img.hidden = true; return; }

            if (file.size > 2 * 1024 * 1024) {
                alert('A imagem deve ter no máximo 2 MB.');
                input.value = '';
                img.hidden = true;
                return;
            }

            img.src = URL.createObjectURL(file);
            img.hidden = false;
        });
    });

    // ---------- PEDIDO (ADMIN): linhas de jogos ----------
    document.querySelectorAll('[data-add-linha]').forEach(function (btn) {
        var cont = document.querySelector(btn.dataset.addLinha);

        btn.addEventListener('click', function () {
            var nova = cont.querySelector('.linha-pedido').cloneNode(true);
            nova.querySelector('select').selectedIndex = 0;
            nova.querySelector('input').value = 1;
            cont.appendChild(nova);
        });
    });

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-remover-linha]');
        if (!btn) { return; }

        var cont  = btn.closest('#linhas-pedido');
        var linha = btn.closest('.linha-pedido');

        if (cont.querySelectorAll('.linha-pedido').length > 1) {
            linha.remove();
        } else {
            linha.querySelector('select').selectedIndex = 0;
            linha.querySelector('input').value = 1;
        }
    });

    // ---------- PEDIDO (ADMIN): sugere o endereço do cliente escolhido ----------
    document.querySelectorAll('select[data-cliente-endereco]').forEach(function (sel) {
        var campo = document.querySelector(sel.dataset.clienteEndereco);
        if (!campo) { return; }

        campo.addEventListener('input', function () { campo.dataset.auto = '0'; });

        sel.addEventListener('change', function () {
            var end = sel.options[sel.selectedIndex].dataset.endereco || '';
            if (end && (campo.value.trim() === '' || campo.dataset.auto === '1')) {
                campo.value = end;
                campo.dataset.auto = '1';
            }
        });
    });

    // ---------- MENSAGENS DE SUCESSO ----------
    document.querySelectorAll('.alert-sucesso').forEach(function (a) {
        setTimeout(function () {
            a.style.opacity = '0';
            setTimeout(function () { a.remove(); }, 400);
        }, 4000);
    });
});
