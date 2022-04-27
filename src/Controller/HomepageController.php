<?php

namespace App\Controller;

use App\Repository\TrickRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(TrickRepository $trickRepository): Response
    {
        $tricks = $trickRepository->findAllOrder();
        return $this->render('homepage/index.html.twig', [
            'tricks' => $tricks
        ]);
    }
}