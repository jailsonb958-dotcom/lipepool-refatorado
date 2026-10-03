<?php use LipePool\Security\Csrf; use LipePool\Support\View; ?>
<section class="form-section container">
    <div class="form-card">
        <p class="eyebrow">Bem-vindo à LipePool</p><h1>Criar conta</h1>
        <p>Use seus dados para acompanhar solicitações de atendimento.</p>
        <form method="post" action="/cadastro" class="stack-form" novalidate>
            <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
            <label>Nome completo<input name="name" type="text" maxlength="120" autocomplete="name" required></label>
            <label>E-mail<input name="email" type="email" maxlength="254" autocomplete="email" required></label>
            <label>Telefone com DDD<input name="phone" type="tel" maxlength="24" autocomplete="tel" required></label>
            <label>Senha<input name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required><small>Use pelo menos 12 caracteres; limite de 72 bytes em UTF-8.</small></label>
            <label>Confirme a senha<input name="password_confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></label>
            <button class="button button-primary button-wide" type="submit">Criar minha conta</button>
        </form>
        <p class="form-footnote">Já tem uma conta? <a href="/login">Entrar</a></p>
    </div>
</section>
