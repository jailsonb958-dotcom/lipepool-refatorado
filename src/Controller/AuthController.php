<?php

declare(strict_types=1);

namespace LipePool\Controller;

use LipePool\Auth\Authenticator;
use LipePool\Auth\Session;
use LipePool\Http\Request;
use LipePool\Http\Response;
use LipePool\Repository\LoginThrottleRepository;
use LipePool\Repository\UserRepository;
use LipePool\Security\Csrf;
use LipePool\Support\Flash;
use LipePool\Support\Input;
use LipePool\Support\View;
use PDOException;

final class AuthController
{
    public function __construct(
        private readonly View $view,
        private readonly UserRepository $users,
        private readonly Authenticator $authenticator,
        private readonly LoginThrottleRepository $throttle,
    ) {}

    public function registerForm(Request $request): Response
    {
        return $this->view->render('auth/register', ['title' => 'Criar conta']);
    }

    public function register(Request $request): Response
    {
        Csrf::verify($request->input('_csrf'));
        try {
            $name = Input::string($request->input('name'), 120, 'Nome');
            $email = Input::email($request->input('email'));
            $phone = Input::phone($request->input('phone'));
            $password = Input::string($request->input('password'), 128, 'Senha');
            if (mb_strlen($password, 'UTF-8') < 12 || strlen($password) > 72) {
                throw new \InvalidArgumentException('A senha deve ter pelo menos 12 caracteres e até 72 bytes UTF-8.');
            }
            if ($request->input('password_confirmation') !== $password) {
                throw new \InvalidArgumentException('As senhas não conferem.');
            }
            $id = $this->users->createCustomer($name, $email, $phone, password_hash($password, PASSWORD_DEFAULT));
            Session::regenerate();
            unset($_SESSION['_csrf']);
            $_SESSION['user'] = ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone, 'role' => 'customer'];
            Flash::add('success', 'Sua conta foi criada. Você já pode solicitar um agendamento.');
            return Response::redirect('/conta');
        } catch (\InvalidArgumentException $error) {
            Flash::add('error', $error->getMessage());
            return Response::redirect('/cadastro');
        } catch (PDOException $error) {
            if ((string) $error->getCode() === '23000') {
                Flash::add('error', 'Não foi possível criar a conta com esses dados. Verifique se o e-mail já está cadastrado.');
                return Response::redirect('/cadastro');
            }
            throw $error;
        }
    }

    public function loginForm(Request $request): Response
    {
        return $this->view->render('auth/login', ['title' => 'Entrar']);
    }

    public function login(Request $request): Response
    {
        Csrf::verify($request->input('_csrf'));
        try {
            $email = Input::email($request->input('email'));
            $password = Input::string($request->input('password'), 128, 'Senha');
        } catch (\InvalidArgumentException) {
            Flash::add('error', 'E-mail ou senha incorretos.');
            return Response::redirect('/login');
        }

        $ip = (string) ($request->server['REMOTE_ADDR'] ?? 'unknown');
        $key = $this->throttle->key($email, $ip);
        if ($this->throttle->isBlocked($key)) {
            Flash::add('error', 'E-mail ou senha incorretos. Aguarde alguns minutos e tente novamente.');
            return Response::redirect('/login');
        }
        $user = $this->authenticator->authenticate($email, $password);
        if ($user === null) {
            $this->throttle->recordFailure($key);
            Flash::add('error', 'E-mail ou senha incorretos.');
            return Response::redirect('/login');
        }
        $this->throttle->clear($key);
        Session::regenerate();
        unset($_SESSION['_csrf']);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'phone' => (string) $user['phone'],
            'role' => (string) $user['role'],
        ];
        Flash::add('success', 'Bem-vindo(a), ' . $user['name'] . '.');
        return Response::redirect($user['role'] === 'admin' ? '/admin/agenda' : '/conta');
    }

    public function logout(Request $request): Response
    {
        Csrf::verify($request->input('_csrf'));
        Session::destroy();
        Session::start();
        Flash::add('success', 'Você saiu da sua conta.');
        return Response::redirect('/');
    }
}
