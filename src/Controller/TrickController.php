<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Photo;
use App\Entity\Trick;
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
            'tricks' => $trickRepository->findAll()
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
                $trick->addVideos($video);
            }

            $exist = true;
            $index = 1;
            do {
                if ($request->get("video-$index") != null)
                {
                    $video = new Video();
                    $video->setTrick($trick)
                        ->setTitle($trick->getTitle())
                        ->setUrl($request->get("video-$index"));
                    $manager->persist($video);
                    $trick->addVideos($video);
                    $index++;
                }
                else
                {
                    $exist = false;
                }
            } while ($exist);

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
    public function edit(Trick $trick, Request $request, EntityManagerInterface $manager, PhotoRepository $photoRepository, VideoRepository $videoRepository)
    {

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);
        $photos = $photoRepository->findBy(['trick' => $trick]);
        $videos = $videoRepository->findBy(['trick' => $trick]);
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

            if ($request->request->all()['trick']['video'] != "")
            {
                $video = new Video();
                $video->setTrick($trick)
                    ->setTitle($trick->getTitle())
                    ->setUrl($request->request->all()['trick']['video']);
                $manager->persist($video);
                $trick->addVideos($video);
            }

            $exist = true;
            $index = 1;
            do {
                if ($request->get("video-$index") != null)
                {
                    $video = new Video();
                    $video->setTrick($trick)
                        ->setTitle($trick->getTitle())
                        ->setUrl($request->get("video-$index"));
                    $manager->persist($video);
                    $trick->addVideos($video);
                    $index++;
                }
                else
                {
                    $exist = false;
                }
            } while ($exist);

            $trick->setUpdatedAt(new \DateTime());

            $manager->flush();

            $this->addFlash('success', 'Le trick à bien été mis à jour');
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

            $this->addFlash('success', 'Votre commentaire  à bien été enregistrer');

            return $this->redirect($request->getUri());
        }

        $comments = $paginator->paginate(
            $commentRepository->findBy(['trick' => $trick], ['createdAt' => 'ASC']),
            $request->query->getInt('page', 1),
            2
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
        $this->addFlash('success', 'Le trick à bien été supprimer');
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