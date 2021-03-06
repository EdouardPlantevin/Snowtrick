<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Photo;
use App\Entity\Trick;
use App\Entity\TrickService;
use App\Entity\Video;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\CommentRepository;
use App\Repository\PhotoRepository;
use App\Repository\TrickRepository;
use App\Repository\VideoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/trick')]
class TrickController extends AbstractController
{

    public function __construct(private EntityManagerInterface $manager){}

    #[Route('/liste-des-tricks', name: 'tricks')]
    public function index(TrickRepository $trickRepository): Response
    {
        return $this->render('trick/tricks.html.twig', [
            'tricks' => $trickRepository->findAllOrder()
        ]);
    }

    #[Route('/creation', name: 'add_trick')]
    public function add(Request $request, SluggerInterface $slugger, EntityManagerInterface $manager)
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $trickService = new TrickService();
            $trick->setCreatedAt(new \DateTimeImmutable())
                  ->setUser($this->getUser())
                  ->setSlug($slugger->slug(strtolower($trick->getTitle())));

            if($request->files->get('trick'))
            {
                $trickService->savePhoto($request->files->get('trick')['photos'], $manager, $trick, $this->getParameter('trick_img'));
            }
            if($request->get('trick')['video'])
            {
                $trickService->saveVideo($request, $trick, $manager);
            }

            $manager->persist($trick);
            $manager->flush();

            $this->addFlash('success', 'Votre trick ?? bien ??t?? cr??er');

            return $this->redirectToRoute('homepage');
        }
        return $this->render('trick/manage-trick.html.twig', [
            'form' => $form->createView(),
            'action' => 'add'
        ]);
    }

    #[Route('/modifier/{slug}', name: 'edit_trick')]
    public function edit(Trick $trick, Request $request, EntityManagerInterface $manager, PhotoRepository $photoRepository, VideoRepository $videoRepository)
    {

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);
        $photos = $photoRepository->findBy(['trick' => $trick]);
        $videos = $videoRepository->findBy(['trick' => $trick]);
        if ($form->isSubmitted() && $form->isValid())
        {
            $trickService = new TrickService();
            if($request->files->get('trick'))
            {
                $trickService->savePhoto($request->files->get('trick')['photos'], $manager, $trick, $this->getParameter('trick_img'));
            }

            if ($request->request->all()['trick']['video'] != "")
            {
                $trickService->saveVideo($request, $trick, $manager);
            }

            $trick->setUpdatedAt(new \DateTime());

            $manager->flush();

            $this->addFlash('success', 'Le trick ?? bien ??t?? mis ?? jour');
            return $this->redirectToRoute('homepage');
        }

        return $this->render('trick/manage-trick.html.twig', [
            'form' => $form->createView(),
            'action' => 'edit',
            'photos' => $photos,
            'videos' => $videos
        ]);
    }

    #[Route('/{slug}', name: 'show_trick')]
    public function show(Trick $trick, Request $request, PaginatorInterface $paginator, CommentRepository $commentRepository): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            $comment->setCreatedAt(new \DateTimeImmutable())
                    ->setAuthor($this->getUser())
                    ->setTrick($trick);
            $this->manager->persist($comment);
            $this->manager->flush();

            $this->addFlash('success', 'Votre commentaire  ?? bien ??t?? enregistrer');

            return $this->redirect($request->getUri());
        }

        $comments = $paginator->paginate(
            $commentRepository->findBy(['trick' => $trick], ['createdAt' => 'DESC']),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('trick/show.html.twig', [
            'trick' => $trick,
            'form' => $form->createView(),
            'comments' => $comments
        ]);
    }

    #[Route('/suppression-trick/{slug}', name: 'delete_trick')]
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
        $this->addFlash('success', 'Le trick ?? bien ??t?? supprimer');
        return $this->redirectToRoute('tricks');
    }

    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }

    #[Route('/suppression-image/json', name: 'delete_img', methods: ["POST"])]
    public function removeImg(PhotoRepository $photoRepository, Request $request, EntityManagerInterface $manager)
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $photo = $photoRepository->find($id);

        if (count($photo->getTrick()->getPhotos()) >= 2)
        {
            $manager->remove($photo);
            $manager->flush();
            return new JsonResponse([
                'status' => 'success'
            ]);
        } else {
            return new JsonResponse([
                'status' => 'error'
            ]);
        }
    }

    #[Route('/suppression-video/json', name: 'delete_vod', methods: ["POST"])]
    public function removeVideo(VideoRepository $videoRepository, Request $request, EntityManagerInterface $manager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        
        $video = $videoRepository->find($id);

        if (count($video->getTrick()->getVideos()) >= 2)
        {
            $manager->remove($video);
            $manager->flush();
            return new JsonResponse([
                'status' => 'success'
            ]);
        } else {
            return new JsonResponse([
                'status' => 'error'
            ]);
        }
    }
}