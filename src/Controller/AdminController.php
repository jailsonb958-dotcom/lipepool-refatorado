<?php

declare(strict_types=1);

namespace LipePool\Controller;

use LipePool\Auth\AuthContext;
use LipePool\Http\Request;
use LipePool\Http\Response;
use LipePool\Repository\AppointmentRepository;
use LipePool\Repository\ServiceRepository;
use LipePool\Repository\UserRepository;
use LipePool\Security\Csrf;
use LipePool\Support\Flash;
use LipePool\Support\Input;
use LipePool\Support\View;
use PDOException;

final class AdminController
{
    public function __construct(
        private readonly View $view,
        private readonly AppointmentRepository $appointments,
        private readonly UserRepository $users,
        private readonly ServiceRepository $services,
    ) {}

    public function schedule(Request $request): Response
    {
        AuthContext::requireAdmin();
        $statusValue = $request->query('status', 'requested');
        $status = is_string($statusValue) ? $statusValue : 'requested';
        if (!in_array($status, ['requested', 'completed', 'cancelled', 'all'], true)) {
            $status = 'requested';
        }
        return $this->view->render('admin/schedule', [
            'title' => 'Agenda administrativa',
            'status' => $status,
            'appointments' => $this->appointments->forAdmin($status === 'all' ? '' : $status),
        ]);
    }

    public function customers(Request $request): Response
    {
        AuthContext::requireAdmin();
        $searchValue = $request->query('q', '');
        $search = is_string($searchValue) ? trim($searchValue) : '';
        if (mb_strlen($search, 'UTF-8') > 100) {
            $search = mb_substr($search, 0, 100, 'UTF-8');
        }
        return $this->view->render('admin/customers', [
            'title' => 'Clientes',
            'search' => $search,
            'customers' => $this->users->allCustomers($search),
        ]);
    }

    public function services(Request $request): Response
    {
        AuthContext::requireAdmin();
        return $this->view->render('admin/services', [
            'title' => 'Gerenciar serviços',
            'services' => $this->services->all(),
        ]);
    }

    public function createService(Request $request): Response
    {
        AuthContext::requireAdmin();
        Csrf::verify($request->input('_csrf'));
        try {
            [$name, $description, $duration] = $this->serviceInput($request);
            $this->services->create($name, $description, $duration);
            Flash::add('success', 'Serviço criado.');
        } catch (\InvalidArgumentException $error) {
            Flash::add('error', $error->getMessage());
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                Flash::add('error', 'Já existe um serviço com esse nome.');
            } else {
                throw $error;
            }
        }
        return Response::redirect('/admin/servicos');
    }

    public function updateService(Request $request, array $params): Response
    {
        AuthContext::requireAdmin();
        Csrf::verify($request->input('_csrf'));
        $id = (int) ($params['id'] ?? 0);
        try {
            if ($id < 1) {
                throw new \InvalidArgumentException('Serviço inválido.');
            }
            [$name, $description, $duration] = $this->serviceInput($request);
            if (!$this->services->update($id, $name, $description, $duration)) {
                Flash::add('error', 'Serviço não encontrado ou sem alterações.');
            } else {
                Flash::add('success', 'Serviço atualizado.');
            }
        } catch (\InvalidArgumentException $error) {
            Flash::add('error', $error->getMessage());
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                Flash::add('error', 'Já existe um serviço com esse nome.');
            } else {
                throw $error;
            }
        }
        return Response::redirect('/admin/servicos');
    }

    public function deactivateService(Request $request, array $params): Response
    {
        AuthContext::requireAdmin();
        Csrf::verify($request->input('_csrf'));
        $id = (int) ($params['id'] ?? 0);
        if ($id < 1 || !$this->services->deactivate($id)) {
            Flash::add('error', 'Serviço não encontrado ou já desativado.');
        } else {
            Flash::add('success', 'Serviço desativado. O histórico de agendamentos foi preservado.');
        }
        return Response::redirect('/admin/servicos');
    }

    private function serviceInput(Request $request): array
    {
        $name = Input::string($request->input('name'), 120, 'Nome do serviço');
        $description = Input::string($request->input('description', ''), 1000, 'Descrição', false);
        $rawDuration = $request->input('duration_minutes', '');
        if (!is_string($rawDuration)) {
            throw new \InvalidArgumentException('Duração inválida.');
        }
        $duration = null;
        if (trim($rawDuration) !== '') {
            $duration = filter_var($rawDuration, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1440]]);
            if ($duration === false) {
                throw new \InvalidArgumentException('A duração deve ser um número de 1 a 1440 minutos.');
            }
        }
        return [$name, $description, $duration];
    }

    public function complete(Request $request, array $params): Response
    {
        AuthContext::requireAdmin();
        Csrf::verify($request->input('_csrf'));
        $id = (int) ($params['id'] ?? 0);
        if ($id < 1 || !$this->appointments->changeStatus($id, 'completed')) {
            Flash::add('error', 'Não foi possível concluir. O agendamento pode já ter sido atualizado.');
        } else {
            Flash::add('success', 'Atendimento marcado como concluído.');
        }
        return Response::redirect('/admin/agenda');
    }

    public function cancel(Request $request, array $params): Response
    {
        AuthContext::requireAdmin();
        Csrf::verify($request->input('_csrf'));
        $id = (int) ($params['id'] ?? 0);
        if ($id < 1 || !$this->appointments->changeStatus($id, 'cancelled')) {
            Flash::add('error', 'Não foi possível cancelar. O agendamento pode já ter sido atualizado.');
        } else {
            Flash::add('success', 'Agendamento cancelado.');
        }
        return Response::redirect('/admin/agenda');
    }
}
