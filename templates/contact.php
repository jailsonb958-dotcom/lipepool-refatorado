<?php use LipePool\Support\View; ?>
<section class="page-heading container">
    <p class="eyebrow">Fale com a equipe</p>
    <h1>Contato</h1>
    <p class="lead">Configure os canais oficiais antes de divulgar esta página.</p>
</section>
<section class="container section contact-grid">
    <div class="info-card">
        <h2>Estamos preparando os canais de atendimento.</h2>
        <p>Os dados de contato não foram copiados automaticamente do sistema antigo porque podem estar desatualizados. O responsável pode configurá-los no ambiente de hospedagem.</p>
        <?php if ($email): ?><p><strong>E-mail:</strong> <a href="mailto:<?= View::escape($email) ?>"><?= View::escape($email) ?></a></p><?php endif; ?>
        <?php if ($phone): ?><p><strong>Telefone:</strong> <?= View::escape($phone) ?></p><?php endif; ?>
        <?php if ($whatsapp): ?><p><strong>WhatsApp:</strong> <?= View::escape($whatsapp) ?></p><?php endif; ?>
    </div>
    <div class="contact-note"><span class="feature-icon">i</span><h2>Precisa solicitar um serviço?</h2><p>Entre na sua conta para consultar os horários e registrar sua solicitação.</p><a class="button button-primary" href="/login">Acessar minha conta</a></div>
</section>
