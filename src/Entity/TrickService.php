<?php

namespace App\Entity;

class TrickService {
    public function savePhoto($images, $manager, $trick, $place)
    {
        foreach ($images as $image)
        {
            $photo = new Photo();

            $fileName = $this->generateUniqueFileName() . '.' . $image->guessExtension();

            $image->move(
                $place,
                $fileName
            );

            $photo->setTitle($fileName)
                ->setTrick($trick);
            $trick->addPhoto($photo);
            $manager->persist($photo);
        }
    }

    public function saveVideo($request, $trick, $manager)
    {
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
    }

    private function generateUniqueFileName()
    {
        return md5(uniqid());
    }
}