<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Event;
use App\Entity\Partner;
use App\Entity\Tag;
use App\Entity\User;
use App\Service\NormalizerService as NS;
use App\Service\UtilityService as US;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse as JR;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends AbstractController
{
    /**
     * @Route("/debug", name="debug", methods="GET")
     */
    public function debug(Request $req, EntityManagerInterface $em)
    {
        $password = "intranet_viviskes";
        return new JR(password_hash($password, PASSWORD_BCRYPT));
    }

    /**
     * @Route("/recover", name="recover", methods="POST")
     */
    public function recover(Request $req, EntityManagerInterface $em)
    {
        $email = $req->get("email");
        if (!$email) return new JR("Missing data", Response::HTTP_BAD_REQUEST);
        $user = $em->getRepository(User::class)->findOneBy([
            "email" => $email
        ]);
        if (!$user) return new JR("User not found", Response::HTTP_NOT_FOUND);

        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*_";
        $password = substr(str_shuffle($chars), 0, 12);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $user->setPassword($hash);
        $em->persist($user);

        $subject = "Récupération du mot de passe";
        $message = "Nouveau mot de passe: " . $password;

        mail($email, $subject, $message);

        return new JR("New password was sent");
    }

    /**
     * @Route("/contact", name="contact", methods="POST")
     */
    public function contact(Request $req, EntityManagerInterface $em)
    {
        $name = $req->get("name");
        $email = $req->get("email");
        $subject = $req->get("subject");
        $message = $req->get("message");

        if (!$name || !$email || !$subject || !$message) {
            return new JR("Missing data", Response::HTTP_BAD_REQUEST);
        }

        $content = "<h1>Formulaire de contact de viviskes.ch</h1>" .
            "<p>Message de " . $name . " (" . $email . ")</p>" .
            "<p>Sujet: " . $subject . "</p>" .
            "<p>Message: " . $message . "</p>";

        mail("irinadespot@gmail.com", "Formulaire de contact", $content);

        return new JR("Message was sent");
    }

    /**
     * @Route("/login", name="public-login", methods="POST")
     */
    public function login(Request $req, EntityManagerInterface $em)
    {
        if (!$req->get('email') || !$req->get('password')) {
            return new JR('Missing data', Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $req->get('email')]);

        if (!$user) return new JR("User not found", Response::HTTP_NOT_FOUND);
        if ($user->getIsArchived()) return new JR("User is archived", Response::HTTP_FORBIDDEN);

        if (!password_verify($req->get('password'), $user->getPassword())) {
            return new JR("Password incorrect", Response::HTTP_BAD_REQUEST);
        }

        // Create a JWT
        $jwt = US::generateJWT($user);
        $em->flush();

        return new JR([
            'authKey' => $jwt,
            'user' => NS::getUser($user, true)
        ]);
    }

    /**
     * @Route("/public/members", name="public-members", methods="GET")
     */
    public function getMembers(Request $req, EntityManagerInterface $em)
    {
        $data = [];
        foreach ($em->getRepository(User::class)->findAll() as $u) {
            if ($u->getCelticName() && !$u->getIsArchived()) {
                $url = "uploads/users/" . $u->getId() . ".jpg";
                $avatar = $_ENV['SERVER_URL'] . $url;
                if (!file_exists(__DIR__ . "/../../public/" . $url)) $avatar = null;

                array_push($data, [
                    "id" => $u->getId(),
                    "celticName" => $u->getCelticName(),
                    "avatar" => $avatar
                ]);
            }
        }
        return new JR($data);
    }

    /**
     * @Route("/public/partners", name="public-partners", methods={"GET"})
     */
    public function getPartners(EntityManagerInterface $em)
    {
        $partners = $em->getRepository(Partner::class)->findAll();
        $data = [];
        foreach ($partners as $p) array_push($data, NS::getPartner($p));
        return new JR($data);
    }

    /**
     * @Route("/public/events", name="public-events", methods={"GET"})
     */
    public function getEvents(EntityManagerInterface $em)
    {
        $events = $em->getRepository(Event::class)->findAll();
        $data = [];

        foreach ($events as $e) {
            $eventData = [
                'id' => $e->getId(),
                'title' => $e->getTitle(),
                'description' => $e->getDescription(),
                'start' => US::datetimeToString($e->getStart()),
                'end' => US::datetimeToString($e->getEnd()),
                'location' => $e->getLocation(),
                'privacy' => $e->getPrivacy(),
                'isConfirmed' => $e->getIsConfirmed(),
                'photos' => []
            ];

            foreach ($e->getPhotos() as $p) {
                array_push($eventData['photos'], NS::getPhoto($p));
            }
            array_push($data, $eventData);
        }

        return new JR($data);
    }

    /**
     * @Route("/public/tags", name="public-tags", methods={"GET"})
     */
    public function getTags(EntityManagerInterface $em)
    {
        $tags = $em->getRepository(Tag::class)->findAll();
        $array = [];
        foreach ($tags as $p) array_push($array, NS::getTag($p));
        return new JR($array);
    }

    /**
     * @Route("/public/articles", name="public-articles", methods={"GET"})
     */
    public function getArticles(EntityManagerInterface $em)
    {
        $articles = $em->getRepository(Article::class)->findAll();
        $array = [];
        foreach ($articles as $a) array_push($array, NS::getArticle($a));
        return new JR($array);
    }

    /**
     * @Route("/public/articles/{id}", name="public-article", methods={"GET"})
     */
    public function getArticle(EntityManagerInterface $em, $id)
    {
        $article = $em->getRepository(Article::class)->find($id);
        if (!$article) return new JR("Article not found", Response::HTTP_NOT_FOUND);
        return new JR(NS::getArticle($article, true));
    }
}
