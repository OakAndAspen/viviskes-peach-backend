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
use App\Entity\Photo;
use App\Entity\Tag;
use App\Entity\Topic;
use App\Entity\User;
use App\Service\UtilityService as US;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class FormService
{
    public static function upsertArticle(EntityManagerInterface $em, $data, Article $a = null)
    {
        $authorId = isset($data["author"]) ? $data["author"] : null;
        $title = isset($data["title"]) ? $data["title"] : null;
        $content = isset($data["content"]) ? $data["content"] : null;
        $isPublished = isset($data["isPublished"]) ? US::getBoolean($data["isPublished"]) : null;

        if (!$a) {
            if (!$authorId || !$title || !$content) return "Missing data";
            $author = $em->getRepository(User::class)->find($authorId);
            if (!$author) return "Author not found";

            $a = new Article();
            $a->setAuthor($author);
            $a->setCreated(new DateTime());
            $a->setIsPublished(false);
        }

        if ($title) $a->setTitle($title);
        if ($content) $a->setContent($content);
        if ($isPublished !== null) $a->setIsPublished($isPublished);
        $a->setEdited(new DateTime());

        $em->persist($a);
        $em->flush();
        return $a;
    }

    public static function upsertBook(EntityManagerInterface $em, $data, Book $b = null)
    {
        $name = isset($data["name"]) ? $data["name"] : null;

        if (!$b) {
            if (!$name) return "Missing data";
            $b = new Book();
        }

        if ($name) $b->setName($name);

        $em->persist($b);
        $em->flush();
        return $b;
    }

    public static function upsertCategory(EntityManagerInterface $em, $data, Category $c = null)
    {
        $label = isset($data["label"]) ? $data["label"] : null;

        if (!$c) {
            if (!$label) return "Missing data";
            $c = new Category();
        }

        if ($label) $c->setLabel($label);

        $em->persist($c);
        $em->flush();
        return $c;
    }

    public static function upsertDocument(EntityManagerInterface $em, $data, Document $d = null)
    {
        $folderId = isset($data["folder"]) ? $data["folder"] : null;
        $name = isset($data["name"]) ? $data["name"] : null;

        if (!$d) {
            if (!$folderId || !$name) return "Missing data";
            $d = new Document();
            $d->setCreated(new DateTime());
        }

        if ($folderId) {
            $folder = $em->getRepository(Folder::class)->find($folderId);
            if (!$folder) return "Folder not found";
            $d->setFolder($folder);
        }

        if ($name) {
            $d->setName(pathinfo($name, PATHINFO_FILENAME));
            $d->setExtension(pathinfo($name, PATHINFO_EXTENSION));
        }

        $em->persist($d);
        $em->flush();
        return $d;
    }

    public static function upsertEvent(EntityManagerInterface $em, $data, Event $e = null)
    {
        $title = isset($data["title"]) ? $data["title"] : null;
        $description = isset($data["description"]) ? $data["description"] : null;
        $publicDescription = isset($data["publicDescription"]) ? $data["publicDescription"] : null;
        $start = isset($data["start"]) ? US::createDate($data["start"]) : null;
        if ($start === false) return "Start invalid";
        $end = isset($data["end"]) ? US::createDate($data["end"]) : null;
        if ($end === false) return "End invalid";
        $location = isset($data["location"]) ? $data["location"] : null;
        $privacy = isset($data["privacy"]) ? $data["privacy"] : null;

        if (!$e) {
            if (!$title || !$privacy) return "Missing data";
            $e = new Event();
        }

        if ($title) $e->setTitle($title);
        if ($description !== null) $e->setDescription($description);
        if ($publicDescription !== null) $e->setPublicDescription($publicDescription);
        if ($start) $e->setStart($start);
        if ($end) $e->setEnd($end);
        if ($location) $e->setLocation($location);
        if ($privacy) $e->setPrivacy($privacy);

        $em->persist($e);
        $em->flush();
        return $e;
    }

    public static function upsertFolder(EntityManagerInterface $em, $data, Folder $f = null)
    {
        $parentId = isset($data["parent"]) ? $data["parent"] : null;
        $name = isset($data["name"]) ? $data["name"] : null;

        if (!$f) {
            if (!$name) return "Missing data";
            $f = new Folder();
            $f->setCreated(new DateTime());
        }

        if ($parentId) {
            $parent = $em->getRepository(Folder::class)->find($parentId);
            if (!$parent) return "Folder not found";
            $f->setParent($parent);
        }

        if ($name) $f->setName($name);

        $em->persist($f);
        $em->flush();
        return $f;
    }

    public static function upsertLoan(EntityManagerInterface $em, $data, Loan $l = null)
    {
        $bookId = isset($data["book"]) ? $data["book"] : null;
        $userId = isset($data["user"]) ? $data["user"] : null;

        if (!$l) {
            if (!$bookId || !$userId) return "Missing data";
            $book = $em->getRepository(Book::class)->find($bookId);
            $user = $em->getRepository(User::class)->find($userId);
            if (!$book || !$user) return "Book or user not found";

            $l = new Loan();
            $l->setBook($book);
            $l->setUser($user);
            $l->setStart(new DateTime());
        } else {
            $l->setEnd(new DateTime());
        }

        $em->persist($l);
        $em->flush();
        return $l;
    }

    public static function upsertMessage(EntityManagerInterface $em, $data, Message $m = null)
    {
        $authorId = isset($data["author"]) ? $data["author"] : null;
        $topicId = isset($data["topic"]) ? $data["topic"] : null;
        $content = isset($data["content"]) ? $data["content"] : null;

        if (!$m) {
            if (!$authorId || !$topicId || !$content) return "Missing data";
            $author = $em->getRepository(User::class)->find($authorId);
            $topic = $em->getRepository(Topic::class)->find($topicId);
            if (!$author || !$topic) return "Author or topic not found";

            $m = new Message();
            $m->setAuthor($author);
            $m->setTopic($topic);
            $m->setCreated(new DateTime());
        }

        $m->setEdited(new DateTime());
        if ($content) $m->setContent($content);

        $em->persist($m);
        $em->flush();
        return $m;
    }

    public static function upsertParticipation(EntityManagerInterface $em, $data)
    {
        $eventId = isset($data["event"]) ? $data["event"] : null;
        $userId = isset($data["user"]) ? $data["user"] : null;
        $day = isset($data["day"]) ? US::createDate($data["day"]) : null;
        if ($day === false) return "Day invalid";
        $status = isset($data["status"]) ? $data["status"] : null;

        if (!$eventId || !$userId || !$day || !$status) return "Missing data";
        $user = $em->getRepository(User::class)->find($userId);
        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$user || !$event) return "User or event not found";

        $p = $em->getRepository(Participation::class)->findOneBy([
            "event" => $event,
            "user" => $user,
            "day" => $day
        ]);

        if (!$p) {
            $p = new Participation();
            $p->setUser($user);
            $p->setEvent($event);
            $p->setDay($day);
        }

        $p->setStatus($status);

        $em->persist($p);
        $em->flush();
        return $p;
    }

    public static function upsertPartner(EntityManagerInterface $em, $data, Partner $p = null)
    {
        $label = isset($data["label"]) ? $data["label"] : null;
        $url = isset($data["url"]) ? $data["url"] : null;

        if (!$p) {
            if (!$label) return "Missing data";
            $p = new Partner();
        }

        if ($label) $p->setLabel($label);
        if ($url) $p->setUrl($url);

        $em->persist($p);
        $em->flush();
        return $p;
    }

    public static function upsertPhoto(EntityManagerInterface $em, $data)
    {
        $eventId = isset($data["event"]) ? $data["event"] : null;

        if (!$eventId) return "Missing data";
        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$event) return "Event not found";

        $p = new Photo();
        $p->setEvent($event);

        $em->persist($p);
        $em->flush();
        return $p;
    }

    public static function upsertTag(EntityManagerInterface $em, $data, Tag $t = null)
    {
        $label = isset($data["label"]) ? $data["label"] : null;

        if (!$t) {
            if (!$label) return "Missing data";
            $t = new Tag();
        }

        if ($label) $t->setLabel($label);

        $em->persist($t);
        $em->flush();
        return $t;
    }

    public static function upsertTopic(EntityManagerInterface $em, $data, Topic $t = null)
    {
        $eventId = isset($data["event"]) ? $data["event"] : null;
        $categoryId = isset($data["category"]) ? $data["category"] : null;
        $title = isset($data["title"]) ? $data["title"] : null;
        $pinned = isset($data["pinned"]) ? US::getBoolean($data["pinned"]) : null;

        if (!$t) {
            if ((!$eventId && !$categoryId) || !$title || $pinned === null) return "Missing data";

            $t = new Topic();

            if ($categoryId) {
                $category = $em->getRepository(Category::class)->find($categoryId);
                if (!$category) return "Category not found";
                $t->setCategory($category);
            }
            if ($eventId) {
                $event = $em->getRepository(Event::class)->find($eventId);
                if (!$event) return "Event not found";
                $t->setEvent($event);
            }
        }

        if ($title) $t->setTitle($title);
        if ($pinned !== null) $t->setPinned($pinned);

        $em->persist($t);
        $em->flush();
        return $t;
    }

    public static function upsertUser(EntityManagerInterface $em, $data, User $u = null)
    {
        $firstName = isset($data["firstName"]) ? $data["firstName"] : null;
        $lastName = isset($data["lastName"]) ? $data["lastName"] : null;
        $celticName = isset($data["celticName"]) ? $data["celticName"] : null;
        $isAdmin = isset($data["isAdmin"]) ? US::getBoolean($data["isAdmin"]) : null;
        $isFighting = isset($data["isFighting"]) ? US::getBoolean($data["isFighting"]) : null;
        $isActive = isset($data["isActive"]) ? US::getBoolean($data["isActive"]) : null;
        $email = isset($data["email"]) ? $data["email"] : null;
        $phone = isset($data["phone"]) ? $data["phone"] : null;
        $address = isset($data["address"]) ? $data["address"] : null;
        $npa = isset($data["npa"]) ? $data["npa"] : null;
        $city = isset($data["city"]) ? $data["city"] : null;
        $mentorId = isset($data["mentor"]) ? $data["mentor"] : null;
        $password = isset($data["password"]) ? $data["password"] : null;
        $oldPassword = isset($data["oldPassword"]) ? $data["oldPassword"] : null;
        $newPassword = isset($data["newPassword"]) ? $data["newPassword"] : null;

        if (!$u) {
            if (!$email || !$password) return "Missing data";
            $u = new User();
            $u->setPassword(password_hash($password, PASSWORD_BCRYPT));
        }

        if ($firstName) $u->setFirstName($firstName);
        if ($lastName) $u->setLastName($lastName);
        if ($celticName !== null) $u->setCelticName($celticName);
        if ($isAdmin !== null) $u->setIsAdmin($isAdmin);
        if ($isFighting !== null) $u->setIsFighting($isFighting);
        if ($isActive !== null) $u->setIsActive($isActive);
        if ($phone !== null) $u->setPhone($phone);
        if ($address !== null) $u->setAddress($address);
        if ($npa !== null) $u->setNpa($npa);
        if ($city !== null) $u->setCity($city);
        if ($mentorId) {
            $mentor = $em->getRepository(User::class)->find($mentorId);
            if (!$mentor) return "Mentor not found";
            $u->setMentor($mentor);
        }
        if ($newPassword || $oldPassword) {
            if (!$u || !$newPassword || !$oldPassword) return "Missing data";
            if (!password_verify($oldPassword, $u->getPassword())) return "Wrong password";
            $u->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
        }

        $em->persist($u);
        $em->flush();
        return $u;
    }
}