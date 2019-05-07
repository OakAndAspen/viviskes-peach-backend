<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Loan;
use App\Entity\Message;
use App\Entity\Partner;
use App\Entity\Topic;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class JsonService
{
    private $rootDir;

    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public static function getPartner(Partner $p)
    {
        $data = [
            'id' => $p->getId(),
            'label' => $p->getLabel(),
            'url' => $p->getUrl()
        ];
        return $data;
    }

    public static function getUser(User $u, $details = false)
    {
        $data = [
            'id' => $u->getId(),
            'firstName' => $u->getFirstName(),
            'lastName' => $u->getLastName(),
            'celticName' => $u->getCelticName()
        ];

        if ($details) {
            $data['email'] = $u->getEmail();
            $data['phone'] = $u->getPhone();
            $data['isActive'] = $u->getIsActive();
            $data['isAdmin'] = $u->getIsAdmin();
            $data['address'] = $u->getAddress();
            $data['npa'] = $u->getNpa();
            $data['city'] = $u->getCity();
            $newbie = $u->getNewbie();
            $data['newbie'] = $newbie ? self::getUser($newbie) : null;
            $mentor = $u->getMentor();
            $data['mentor'] = $mentor ? self::getUser($mentor) : null;
        }

        return $data;
    }

    public static function getEvent(Event $e, $participations = false)
    {
        $data = [
            'id' => $e->getId(),
            'title' => $e->getTitle(),
            'description' => $e->getDescription(),
            'start' => $e->getStart()->format('Y-m-d'),
            'end' => $e->getEnd()->format('Y-m-d'),
            'location' => $e->getLocation(),
            'privacy' => $e->getPrivacy()
        ];

        if ($participations) {
            $data['participations'] = [];
            foreach ($e->getParticipations() as $p) {
                array_push($data['participations'], [
                    'user' => [
                        'id' => $p->getUser()->getId(),
                        'fullName' => $p->getUser()->getFirstName() . ' ' . $p->getUser()->getLastName(),
                    ],
                    'day' => $p->getDay()->format('Y-m-d'),
                    'status' => $p->getStatus()
                ]);
            }
        }

        return $data;
    }

    public static function getBook(Book $b, EntityManagerInterface $em)
    {
        $data = [
            'id' => $b->getId(),
            'name' => $b->getName()
        ];

        $loan = $em->getRepository(Loan::class)->findOneBy([
            'book' => $b,
            'end' => null
        ]);

        if ($loan) $data['loan'] = [
            'user' => [
                'id' => $loan->getUser()->getId(),
                'fullName' => $loan->getUser()->getFirstName() . ' ' . $loan->getUser()->getLastName()
            ],
            'start' => $loan->getStart()->format('Y-m-d')
        ];

        return $data;
    }

    public static function getCategory(Category $c, $topics = false)
    {
        $data = [
            'id' => $c->getId(),
            'label' => $c->getLabel()
        ];

        if ($topics) {
            $data['topics'] = [];
            foreach ($c->getTopics() as $t) {
                array_push($data['topics'], self::getTopic($t));
            }
        }

        return $data;
    }

    public static function getTopic(Topic $t, $messages = false)
    {
        $data = [
            'id' => $t->getId(),
            'title' => $t->getTitle(),
            'event' => $t->getEvent() ? self::getEvent($t->getEvent()) : null,
            'category' => $t->getCategory() ? self::getCategory($t->getCategory()) : null,
            'pinned' => $t->getPinned()
        ];

        if ($messages) {
            $data['messages'] = [];
            foreach ($t->getMessages() as $m) {
                array_push($data['messages'], self::getMessage($m));
            }
        }

        return $data;
    }

    public static function getMessage(Message $m, $topic = false)
    {
        $data = [
            'id' => $m->getId(),
            'content' => $m->getContent(),
            'author' => self::getUser($m->getAuthor()),
            'created' => UtilityService::datetimeToString($m->getCreated()),
            'edited' => UtilityService::datetimeToString($m->getEdited())
        ];

        if($topic) {
            $data['topic'] = self::getTopic($m->getTopic());
        }

        return $data;
    }
}