<?php
/**
 * views/_form_cliente.php
 * Campos do formulário de cliente (usado no admin e em "Minha conta" da loja).
 * Espera: $dados (array com os campos do cliente) e $emailEditavel (bool).
 */
$emailEditavel = $emailEditavel ?? true;
?>
<div class="form-group">
    <label for="nome">Nome *</label>
    <input type="text" id="nome" name="nome" maxlength="100" value="<?= e($dados['nome']) ?>" required>
</div>

<div class="form-group">
    <label for="email">E-mail *</label>
    <input type="email" id="email" name="email" maxlength="150" value="<?= e($dados['email']) ?>"
           <?= $emailEditavel ? 'required' : 'readonly class="campo-bloqueado"' ?>>
    <?php if (!$emailEditavel): ?>
        <small class="dica">Este cliente tem conta de acesso: o e-mail é o da conta e não pode ser alterado aqui.</small>
    <?php endif; ?>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="cpf">CPF</label>
        <input type="text" id="cpf" name="cpf" maxlength="14" data-mascara="cpf" value="<?= e(Cliente::formatarCpf($dados['cpf'])) ?>" placeholder="000.000.000-00">
    </div>
    <div class="form-group">
        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone" maxlength="20" data-mascara="telefone" value="<?= e($dados['telefone']) ?>" placeholder="(38) 99999-9999">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label for="cep">CEP</label>
        <input type="text" id="cep" name="cep" maxlength="9" data-mascara="cep" value="<?= e($dados['cep']) ?>" placeholder="00000-000">
    </div>
    <div class="form-group" style="flex: 2">
        <label for="endereco">Endereço (rua, número, bairro)</label>
        <input type="text" id="endereco" name="endereco" maxlength="200" value="<?= e($dados['endereco']) ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group" style="flex: 2">
        <label for="cidade">Cidade</label>
        <input type="text" id="cidade" name="cidade" maxlength="80" value="<?= e($dados['cidade']) ?>">
    </div>
    <div class="form-group">
        <label for="estado">Estado</label>
        <select id="estado" name="estado">
            <option value="">UF</option>
            <?php foreach (Cliente::UFS as $uf): ?>
                <option value="<?= $uf ?>" <?= $dados['estado'] === $uf ? 'selected' : '' ?>><?= $uf ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
