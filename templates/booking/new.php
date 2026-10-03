<?php use LipePool\Security\Csrf; use LipePool\Support\View; ?>
<section class="page-heading container"><p class="eyebrow">Área do cliente</p><h1>Solicitar agendamento</h1><p class="lead">Escolha o serviço, a data e um dos horários disponíveis.</p></section>
<section class="container form-section">
    <div class="form-card form-card-wide">
        <?php if (!$services): ?><div class="empty-state"><p>Nenhum serviço está ativo. Entre em contato com a equipe.</p></div><?php else: ?>
        <form method="post" action="/agendar" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= View::escape(Csrf::token()) ?>">
            <label>Serviço<select name="service_id" required><option value="">Selecione um serviço</option><?php foreach ($services as $service): ?><option value="<?= (int) $service['id'] ?>"><?= View::escape($service['name']) ?></option><?php endforeach; ?></select></label>
            <div class="form-row"><label>Data<input type="date" name="appointment_date" min="<?= View::escape(date('Y-m-d')) ?>" required></label><label>Horário<select name="appointment_time" required><option value="">Selecione</option><?php foreach ($times as $time): ?><option value="<?= View::escape($time) ?>"><?= View::escape($time) ?></option><?php endforeach; ?></select></label></div>
            <label>Observações <span class="muted">(opcional)</span><textarea name="notes" rows="4" maxlength="1000" placeholder="Inclua informações úteis para a equipe."></textarea></label>
            <p class="muted">A solicitação depende de confirmação da equipe. Horários oferecidos: 08h, 10h, 13h, 15h, 17h e 19h.</p>
            <button class="button button-primary" type="submit">Enviar solicitação</button>
        </form><?php endif; ?>
    </div>
</section>
