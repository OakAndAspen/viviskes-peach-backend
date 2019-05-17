<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Message;
use App\Entity\Partner;
use App\Entity\Tag;
use App\Entity\Topic;
use App\Entity\User;
use App\Service\UtilityService as US;

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

    public static function getEvent(Event $e, User $u, $participations = false, $topics = false)
    {
        $data = [
            'id' => $e->getId(),
            'title' => $e->getTitle(),
            'description' => $e->getDescription(),
            'start' => US::datetimeToString($e->getStart()),
            'end' => US::datetimeToString($e->getEnd()),
            'location' => $e->getLocation(),
            'privacy' => $e->getPrivacy(),
            'read' => true
        ];

        foreach ($e->getTopics() as $t) {
            if ($t->getUnreadUsers()->contains($u)) $data['read'] = false;
        }

        if ($participations) {
            $data['participations'] = [];
            foreach ($e->getParticipations() as $p) {
                array_push($data['participations'], [
                    'user' => self::getUser($p->getUser()),
                    'day' => US::datetimeToString($p->getDay()),
                    'status' => $p->getStatus()
                ]);
            }
        }

        if($topics) {
            $data['topics'] = [];
            foreach ($e->getTopics() as $t) {
                array_push($data['topics'], self::getTopic($t, $u));
            }
        }

        return $data;
    }

    public static function getBook(Book $b)
    {
        $data = [
            'id' => $b->getId(),
            'name' => $b->getName(),
            'loans' => []
        ];

        foreach ($b->getLoans() as $loan) {
            array_push($data['loans'], [
                'user' => self::getUser($loan->getUser()),
                'start' => US::datetimeToString($loan->getStart()),
                'end' => $loan->getEnd() ? US::datetimeToString($loan->getEnd()) : null
            ]);
        }

        return $data;
    }

    public static function getCategory(Category $c, User $u, $topics = false)
    {
        $data = [
            'id' => $c->getId(),
            'label' => $c->getLabel(),
            'read' => true
        ];

        foreach ($c->getTopics() as $t) {
            if ($t->getUnreadUsers()->contains($u)) $data['read'] = false;
        }

        if ($topics) {
            $data['topics'] = [];
            foreach ($c->getTopics() as $t) {
                array_push($data['topics'], self::getTopic($t, $u));
            }
        }

        return $data;
    }

    public static function getTopic(Topic $t, User $u, $messages = false)
    {
        $data = [
            'id' => $t->getId(),
            'title' => $t->getTitle(),
            'event' => $t->getEvent() ? self::getEvent($t->getEvent(), $u) : null,
            'category' => $t->getCategory() ? self::getCategory($t->getCategory(), $u) : null,
            'pinned' => $t->getPinned(),
            'read' => !$t->getUnreadUsers()->contains($u)
        ];

        $lm = null;
        foreach ($t->getMessages() as $m) {
            if (!$lm || $m->getCreated() > $lm->getCreated()) $lm = $m;
        }
        if ($lm) $data['lastMessage'] = self::getMessage($lm);

        if ($messages) {
            $data['messages'] = [];
            foreach ($t->getMessages() as $m) {
                array_push($data['messages'], self::getMessage($m, true));
            }
        }

        return $data;
    }

    public static function getMessage(Message $m, $content = false)
    {
        $data = [
            'id' => $m->getId(),
            'author' => self::getUser($m->getAuthor()),
            'created' => US::datetimeToString($m->getCreated()),
            'edited' => US::datetimeToString($m->getEdited())
        ];

        if ($content) {
            $data['content'] = $m->getContent();
        }

        return $data;
    }

    public static function getArticle(Article $a, $content = false)
    {
        $data = [
            'id' => $a->getId(),
            'title' => $a->getTitle(),
            'author' => self::getUser($a->getAuthor()),
            'created' => US::datetimeToString($a->getCreated()),
            'edited' => US::datetimeToString($a->getEdited()),
            'tags' => []
        ];

        foreach ($a->getTags() as $tag) array_push($data['tags'], self::getTag($tag));

        if ($content) $data['content'] = $a->getContent();

        return $data;
    }

    public static function getTag(Tag $a)
    {
        $data = [
            'id' => $a->getId(),
            'label' => $a->getLabel(),
        ];
        return $data;
    }
}