<?php

/*
 * This file is part of Seedlet project
 *
 * (c) Antal Áron <antalaron@antalaron.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\Seedlet\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
final class MainController extends AbstractController
{
    #[Route(path: '/')]
    public function index(): Response
    {
        return $this->render('main/index.html.twig');
    }
}
