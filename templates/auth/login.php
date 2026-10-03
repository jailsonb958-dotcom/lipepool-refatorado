<?php use LipePool\Security\Csrf; use LipePool\Support\View; ?>
<section class="form-section container">
    <div class="form-card">
        <p class="eyebrow">Área do cliente</p><h1>Entrar</h1>
        <p>Acesse para solicitar e acompanhar seus atendimentos.</p>
        <form method="post" action="/login" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
            <label>E-mail<input name="email" type="email" maxlength="254" autocomplete="username" required></label>
            <label>Senha<input name="password" type="password" maxlength="128" autocomplete="current-password" required></label>
            <button class="button button-primary button-wide" type="submit">Entrar</button>
        </form>
        <p class="form-footnote">Ainda não tem conta? <a href="/cadastro">Criar conta</a></p>
        <p class="muted">Recuperação de senha será habilitada após configurar um provedor de e-mail seguro.</p>
    </div>
</section>
