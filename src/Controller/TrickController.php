<?php

namespace App\Controller;

use App\Entity\Trick;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TrickControllerController extends AbstractController
{
    #[Route('/{slug}', name: 'show_trick')]
    public function index(Trick $trick): Response
    {
        return $this->render('');
    }
}