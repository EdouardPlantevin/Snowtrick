<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Photo;
use App\Entity\Trick;
use App\Entity\User;
use App\Entity\Video;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{

    public function __construct(private UserPasswordHasherInterface $plaintextPassword, private SluggerInterface $slugger)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = ["Edouard", "SnowRider", "BillSnow"];
        $tricks = ["Stalefish", "Flips", "Front flips", "Tail slide", "Ride", "Front tail"];
        $comments = ["Trop bien ce trick", "Hate de tester cela", "Trop facile je suis un pro"];
        $categories = ["Débutant", "Intermediaire", "Difficile", "Expert"];
        $index = 0;

        foreach ($categories as $category)
        {
            $categoryModel = new Category();
            $categoryModel->setTitle($category)
                    ->setSlug($this->slugger->slug(strtolower($category)));

            $manager->persist($categoryModel);
        }

        foreach ($users as $user)
        {
            //User
            $u = new User();

            $u->setEmail($user . "@snowtrick.com")
                ->setAvatar('default.png')
                ->setIsVerified(true)
                ->setUsername($user);

            $hashedPassword = $this->plaintextPassword->hashPassword(
                $u,
                "password"
            );
            $u->setPassword($hashedPassword);

            $manager->persist($u);

            //Tricks
            if($user == "Edouard")
            {
                foreach ($tricks as $trick)
                {
                    $photo = new Photo();
                    $photo->setTitle("trick-$trick.jpeg");
                    $manager->persist($photo);

                    $video = new Video();
                    $video->setTitle("video snow")
                        ->setUrl("https://www.youtube.com/embed/1TJ08caetkw");

                    $t = new Trick();
                    $t->setTitle($trick)
                        ->setCreatedAt(new \DateTimeImmutable())
                        ->setDescription('<p>Aliquam vel nunc sed massa molestie suscipit. Curabitur ac purus cursus erat varius volutpat a quis nibh. Donec sed luctus libero. Mauris tempor sapien a odio commodo, eget consectetur justo scelerisque. Donec placerat convallis consectetur. Nullam interdum elit et sem vehicula imperdiet. Sed eget facilisis dolor. Pellentesque condimentum elementum mi ut scelerisque. Aliquam placerat metus et arcu finibus, hendrerit accumsan mi rhoncus. Donec posuere eget tellus sed cursus. Nam quis neque at massa dapibus imperdiet. Nulla dapibus nisl eu cursus pretium.</p><h2>Lorem ipsum</h2><p>Proin eu tincidunt risus. Ut viverra ornare nunc, eget elementum ipsum hendrerit sit amet. Sed ac purus felis. Quisque fermentum lorem sit amet sapien faucibus bibendum. Sed ornare elit velit, sed sollicitudin tortor tempor suscipit. In mattis elit consequat ultrices maximus. Praesent finibus nunc leo, vitae consequat purus vulputate eget. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Nunc semper metus sit amet sodales tincidunt.</p><h2>Lorem ipsum</h2><p>Aenean ut urna vel nisl aliquet tempor sed vitae nunc. Aenean luctus laoreet pretium. Nullam pretium dolor in odio consectetur, a porta ante condimentum. Donec posuere mattis sapien ac viverra. Nunc tempor, massa a finibus placerat, risus metus fringilla mauris, sed feugiat dolor libero vel eros. Vivamus lectus orci, lobortis eget posuere vel, commodo in nulla. Vivamus fringilla turpis ligula, at efficitur enim consequat sit amet. Nunc vulputate mollis purus at bibendum. Nulla efficitur sapien et nisl mollis, ut laoreet tellus tincidunt. In vulputate eleifend dapibus. Etiam faucibus nisi lorem, vel ornare ex congue a. Aliquam erat volutpat. Nullam tempor elit nec arcu feugiat, eu fermentum massa posuere. Cras nisl massa, aliquam in leo sit amet, tempor fringilla ex.</p>')
                        ->setUser($u)
                        ->setSlug($this->slugger->slug(strtolower($trick)))
                        ->addPhoto($photo)
                        ->addCategory($categoryModel)
                        ->addVideos($video);

                    $manager->persist($t);

                    for ($i = 0; $i < 2; $i++)
                    {
                        $comment = new Comment();
                        $comment->setAuthor($u)
                            ->setCreatedAt(new \DateTimeImmutable())
                            ->setMessage($comments[$i])
                            ->setTrick($t);

                        $manager->persist($comment);
                    }
                }
            }

        }

        $manager->flush();
    }
}
