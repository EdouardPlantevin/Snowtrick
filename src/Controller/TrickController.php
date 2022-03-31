<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\Trick;
use App\Entity\Video;
use App\Form\TrickType;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/trick')]
class TrickController extends AbstractController
{

    #[Route('/liste-des-tricks', name: 'tricks')]
    public function index(TrickRepository $trickRepository): Response
    {
        return $this->render('trick/tricks.html.twig', [
            'tricks' => $trickRepository->findAll()
        ]);
    }



    #[Route('/creation', name: 'add_trick')]
    public function add(Request $request, SluggerInterface $slugger, EntityManagerInterface $manager)
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick, ['video' => '']);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $trick->setCreatedAt(new \DateTimeImmutable())
                  ->setUser($this->getUser())
                  ->setSlug($slugger->slug(strtolower($trick->getTitle())));

            if($request->files->get('trick'))
            {
                foreach ($request->files->get('trick')['photos'] as $image)
                {
                    $photo = new Photo();

                    $fileName = $this->generateUniqueFileName() . '.' . $image->guessExtension();

                    $image->move(
                        $this->getParameter('trick_img'),
                        $fileName
                    );

                    $photo->setTitle($fileName)
                            ->setTrick($trick);
                    $trick->addPhoto($photo);
                    $manager->persist($photo);
                }
            }
            if($request->get('trick')['video'])
            {
                $video = new Video();
                $video->setTitle($trick->getTitle())
                        ->setTrick($trick)
                        ->setUrl($request->get('trick')['video']);
                $manager->persist($video);
                $trick->addVideo($video);
            }

            $manager->persist($trick);
            $manager->flush();

            $this->addFlash('success', 'Votre trick à bien été créer');

            return $this->redirectToRoute('homepage');
        }
        return $this->render('trick/manage-trick.html.twig', [
            'form' => $form->createView(),
            'action' => 'add'
        ]);
    }

    #[Route('/modifier/{slug}', name: 'edit_trick')]
    public function edit(Trick $trick, Request $request, EntityManagerInterface $manager)
    {
        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            if($request->files->get('trick'))
            {
                foreach ($request->files->get('trick')['photos'] as $image)
                {
                    $photo = new Photo();

                    $fileName = $this->generateUniqueFileName() . '.' . $image->guessExtension();

                    $image->move(
                        $this->getParameter('trick_img'),
                        $fileName
                    );

                    $photo->setTitle($fileName)
                        ->setTrick($trick);
                    $trick->addPhoto($photo);
                    $manager->persist($photo);
                }
            }
            if($request->get('trick')['video'])
            {
                $video = new Video();
                $video->setTitle($trick->getTitle())
                    ->setTrick($trick)
                    ->setUrl($request->get('trick')['video']);
                $manager->persist($video);
                $trick->addVideo($video);
            }

            $trick->setUpdatedAt(new \DateTime());

            $this->addFlash('success', 'Le trick à bien été mis à jour');
            return $this->redirectToRoute('homepage');
        }


        return $this->render('trick/manage-trick.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit'
        ]);
    }

    #[Route('/{slug}', name: 'show_trick')]
    public function show(Trick $trick): Response
    {
        return $this->render('trick/show.html.twig', [
            'trick' => $trick
        ]);
    }

    #[Route('/suppresion-trick/{slug}', name: 'delete_trick')]
    public function delete(Trick $trick, EntityManagerInterface $manager): Response
    {

        foreach($trick->getPhotos() as $photo)
        {
            if(file_exists($this->getParameter('trick_img') . '/' . $photo->getTitle()))
            {
                unlink($this->getParameter('trick_img') . '/' . $photo->getTitle());
            }
        }
        $manager->remove($trick);
        $manager->flush();
        $this->addFlash('success', 'Le trick à bien été supprimer');
        return $this->redirectToRoute('tricks');
    }

    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}