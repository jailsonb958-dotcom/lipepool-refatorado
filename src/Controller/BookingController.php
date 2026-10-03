<?php

declare(strict_types=1);

namespace LipePool\Controller;

use LipePool\Auth\AuthContext;
use LipePool\Http\Request;
use LipePool\Http\Response;
use LipePool\Repository\AppointmentRepository;
use LipePool\Repository\ServiceRepository;
use LipePool\Security\Csrf;
use LipePool\Support\Flash;
use LipePool\Support\Input;
use LipePool\Support\View;
use PDOException;

final class BookingController
{
    public function __construct(
        private readonly View $view,
        private readonly AppointmentRepository $appointments,
        private readonly ServiceRepository $services,
    ) {}

    public function form(Request $request): Response
    {
        AuthContext::requireCustomer();
        return $this->view->render('booking/new', [
            'title' => 'Solicitar agendamento',
            'services' => $this->services->active(),
            'times' => ['08:00', '10:00', '13:00', '15:00', '17:00', '19:00'],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = AuthContext::requireCustomer();
        Csrf::verify($request->input('_csrf'));
        try {
            $serviceId = filter_var($request->input('service_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($serviceId === false || $this->services->findActive((int) $serviceId) === null) {
                throw new \InvalidArgumentException('Selecione um serviço disponível.');
            }
            $date = Input::date($request->input('appointment_date'));
            $time = Input::appointmentTime($request->input('appointment_time'));
            $notes = Input::string($request->input('notes', ''), 1000, 'Observações', false);
            $this->appointments->create((int) $user['id'], (int) $serviceId, $date, $time, $notes);
            Flash::add('success', 'Solicitação registrada. A equipe confirmará os detalhes do atendimento.');
        } catch (\InvalidArgumentException $error) {
            Flash::add('error', $error->getMessage());
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                Flash::add('error', 'Esse horário já foi solicitado. Escolha outro horário.');
                return Response::redirect('/agendar');
            }
            throw $error;
        }
        return Response::redirect('/conta');
    }

    public function account(Request $request): Response
    {
        $user = AuthContext::requireCustomer();
        return $this->view->render('account/index', [
            'title' => 'Minha conta',
            'appointments' => $this->appointments->forCustomer((int) $user['id']),
        ]);
    }

    public function cancel(Request $request, array $params): Response
    {
        $user = AuthContext::requireCustomer();
        Csrf::verify($request->input('_csrf'));
        $id = (int) ($params['id'] ?? 0);
        if ($id < 1 || !$this->appointments->changeStatus($id, 'cancelled', (int) $user['id'])) {
            Flash::add('error', 'Não foi possível cancelar a solicitação. Ela pode já ter sido atualizada.');
        } else {
            Flash::add('success', 'Agendamento cancelado. O horário foi liberado.');
        }
        return Response::redirect('/conta');
    }
}
