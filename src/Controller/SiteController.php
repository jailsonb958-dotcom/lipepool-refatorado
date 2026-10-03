<?php

declare(strict_types=1);

namespace LipePool\Controller;

use LipePool\Http\Request;
use LipePool\Http\Response;
use LipePool\Repository\ServiceRepository;
use LipePool\Support\Config;
use LipePool\Support\View;

final class SiteController
{
    public function __construct(private readonly View $view, private readonly ServiceRepository $services) {}

    public function home(Request $request): Response
    {
        return $this->view->render('home', ['title' => 'LipePool | Cuidado profissional para piscinas']);
    }

    public function about(Request $request): Response
    {
        return $this->view->render('about', ['title' => 'Sobre a LipePool']);
    }

    public function services(Request $request): Response
    {
        return $this->view->render('services', [
            'title' => 'Serviços',
            'services' => $this->services->active(),
        ]);
    }

    public function contact(Request $request): Response
    {
        return $this->view->render('contact', [
            'title' => 'Contato',
            'email' => Config::get('CONTACT_EMAIL'),
            'phone' => Config::get('CONTACT_PHONE'),
            'whatsapp' => Config::get('CONTACT_WHATSAPP'),
        ]);
    }
}
