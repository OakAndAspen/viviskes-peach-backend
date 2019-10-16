<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Document;
use App\Entity\Event;
use App\Entity\Folder;
use App\Entity\Loan;
use App\Entity\Message;
use App\Entity\Participation;
use App\Entity\Partner;
use App\Entity\Tag;
use App\Entity\Topic;
use App\Entity\User;
use App\Service\UtilityService as US;

class NormalizerService
{
    private $rootDir;

    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
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

    public static function getBook(Book $b, $loans = false)
    {
        $data = [
            'id' => $b->getId(),
            'name' => $b->getName(),
            'isLoaned' => false
        ];

        foreach ($b->getLoans() as $loan) {
            if (!$loan->getEnd()) $data['isLoaned'] = true;
        }

        if ($loans) {
            $data["loans"] = [];
            foreach ($b->getLoans() as $loan) {
                array_push($data['loans'], self::getLoan($loan));
            }
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

    public static function getDocument(Document $d)
    {
        return [
            'id' => $d->getId(),
            'name' => $d->getName(),
            'extension' => $d->getExtension(),
            'created' => US::datetimeToString($d->getCreated()),
            'folder' => $d->getFolder() ? $d->getFolder()->getId() : null
        ];
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

        if ($topics) {
            $data['topics'] = [];
            foreach ($e->getTopics() as $t) {
                array_push($data['topics'], self::getTopic($t, $u));
            }
        }

        return $data;
    }

    public static function getFolder(Folder $f, $children = false)
    {
        $data = [
            'id' => $f->getId(),
            'name' => $f->getName(),
            'created' => US::datetimeToString($f->getCreated()),
            'parent' => $f->getParent() ? self::getFolder($f->getParent()) : null
        ];

        if ($children) {
            $data['folders'] = [];
            $data['documents'] = [];
            foreach ($f->getChildren() as $child) array_push($data['folders'], self::getFolder($child));
            foreach ($f->getDocuments() as $d) array_push($data['documents'], self::getDocument($d));
        }

        return $data;
    }

    public static function getLoan(Loan $l, $user = true, $book = false)
    {
        $data = [
            'id' => $l->getId(),
            'start' => $l->getStart() ? US::datetimeToString($l->getStart()) : null,
            'end' => $l->getEnd() ? US::datetimeToString($l->getEnd()) : null
        ];

        if ($user) $data["user"] = self::getUser($l->getUser());
        if ($book) $data["book"] = self::getBook($l->getBook());

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

    public static function getParticipation(Participation $p)
    {
        return [
            'id' => $p->getId(),
            'book' => $p->getEvent()->getId(),
            'user' => $p->getUser()->getId(),
            'day' => US::datetimeToString($p->getDay()),
            'status' => $p->getStatus()
        ];
    }

    public static function getPartner(Partner $p)
    {
        return [
            'id' => $p->getId(),
            'label' => $p->getLabel(),
            'url' => $p->getUrl()
        ];
    }

    public static function getTag(Tag $a)
    {
        return [
            'id' => $a->getId(),
            'label' => $a->getLabel(),
        ];
    }

    public static function getTopic(Topic $t, User $u, $messages = false, $parent = false)
    {
        $data = [
            'id' => $t->getId(),
            'title' => $t->getTitle(),
            'pinned' => $t->getPinned(),
            'read' => !$t->getUnreadUsers()->contains($u)
        ];

        $lastMessage = US::getLastMessage($t);
        if ($lastMessage) $data['lastMessage'] = self::getMessage($lastMessage);

        if ($messages) {
            $data['messages'] = [];
            foreach ($t->getMessages() as $m) {
                array_push($data['messages'], self::getMessage($m, true));
            }
        }

        if ($parent) {
            if ($t->getEvent()) $data['event'] = self::getEvent($t->getEvent(), $u);
            if ($t->getCategory()) $data['category'] = self::getCategory($t->getCategory(), $u);
        }

        return $data;
    }

    public static function getUser(User $u, $details = false)
    {
        $data = [
            'id' => $u->getId(),
            'firstName' => $u->getFirstName(),
            'lastName' => $u->getLastName(),
            'celticName' => $u->getCelticName(),
            'avatar' => null
        ];

        $imageUrl = "uploads/users/" . $u->getId() . ".jpg";
        if (file_exists(__DIR__ . "/../../public/" . $imageUrl)) {
            $data["avatar"] = $_ENV['SERVER_URL'] . $imageUrl;
        }

        if ($details) {
            $data['email'] = $u->getEmail();
            $data['phone'] = $u->getPhone();
            $data['isActive'] = $u->getIsActive();
            $data['isFighting'] = $u->getIsFighting();
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
}
