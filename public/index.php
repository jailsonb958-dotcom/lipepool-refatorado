<?php

declare(strict_types=1);

use LipePool\Auth\Authenticator;
use LipePool\Auth\Session;
use LipePool\Controller\AdminController;
use LipePool\Controller\AuthController;
use LipePool\Controller\BookingController;
use LipePool\Controller\SiteController;
use LipePool\Database\ConnectionFactory;
use LipePool\Http\Request;
use LipePool\Http\Response;
use LipePool\Http\Router;
use LipePool\Repository\AppointmentRepository;
use LipePool\Repository\LoginThrottleRepository;
use LipePool\Repository\ServiceRepository;
use LipePool\Repository\UserRepository;
use LipePool\Support\Config;
use LipePool\Support\HttpException;
use LipePool\Support\View;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
Config::load($root);
Session::start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

try {
    $pdo = ConnectionFactory::mysql();
    $users = new UserRepository($pdo);
    $services = new ServiceRepository($pdo);
    $appointments = new AppointmentRepository($pdo);
    $view = new View($root . '/templates');

    $site = new SiteController($view, $services);
    $auth = new AuthController($view, $users, new Authenticator($users), new LoginThrottleRepository($pdo));
    $booking = new BookingController($view, $appointments, $services);
    $admin = new AdminController($view, $appointments, $users, $services);

    $router = new Router();
    $router->add('GET', '/', [$site, 'home']);
    $router->add('GET', '/sobre', [$site, 'about']);
    $router->add('GET', '/servicos', [$site, 'services']);
    $router->add('GET', '/contato', [$site, 'contact']);
    $router->add('GET', '/cadastro', [$auth, 'registerForm']);
    $router->add('POST', '/cadastro', [$auth, 'register']);
    $router->add('GET', '/login', [$auth, 'loginForm']);
    $router->add('POST', '/login', [$auth, 'login']);
    $router->add('POST', '/sair', [$auth, 'logout']);
    $router->add('GET', '/agendar', [$booking, 'form']);
    $router->add('POST', '/agendar', [$booking, 'create']);
    $router->add('GET', '/conta', [$booking, 'account']);
    $router->add('POST', '/conta/agendamentos/{id}/cancelar', [$booking, 'cancel']);
    $router->add('GET', '/admin/agenda', [$admin, 'schedule']);
    $router->add('GET', '/admin/clientes', [$admin, 'customers']);
    $router->add('GET', '/admin/servicos', [$admin, 'services']);
    $router->add('POST', '/admin/servicos', [$admin, 'createService']);
    $router->add('POST', '/admin/servicos/{id}/atualizar', [$admin, 'updateService']);
    $router->add('POST', '/admin/servicos/{id}/desativar', [$admin, 'deactivateService']);
    $router->add('POST', '/admin/agendamentos/{id}/concluir', [$admin, 'complete']);
    $router->add('POST', '/admin/agendamentos/{id}/cancelar', [$admin, 'cancel']);

    $response = $router->dispatch(Request::fromGlobals());
    $response->send();
} catch (HttpException $error) {
    http_response_code($error->status);
    $message = htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Acesso indisponível | LipePool</title><link rel="stylesheet" href="/assets/app.css"><main class="container section"><p class="eyebrow">LipePool</p><h1>' . $error->status . '</h1><p>' . $message . '</p><a class="button button-primary" href="/login">Entrar</a> <a class="text-link" href="/">Voltar ao início</a></main></html>';
} catch (Throwable $error) {
    error_log(sprintf('[lipepool] %s in %s:%d', get_class($error), $error->getFile(), $error->getLine()));
    http_response_code(500);
    $detail = Config::bool('APP_DEBUG') ? '<pre>' . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>' : '';
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Erro | LipePool</title><link rel="stylesheet" href="/assets/app.css"><main class="container section"><h1>Não foi possível carregar esta página.</h1><p>Tente novamente mais tarde.</p>' . $detail . '<a class="button button-primary" href="/">Voltar ao início</a></main></html>';
}
