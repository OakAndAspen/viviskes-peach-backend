<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Message;
use App\Entity\Participation;
use App\Entity\Partner;
use App\Entity\Topic;
use App\Entity\User;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    protected static $defaultName = 'app:migrate';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription("Imports data from exported php files.")
            ->setHelp("Imports data from exported php files.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $event = $participation = $category = null;
        $subject = $message = $article = $partner = null;
        $sep = DIRECTORY_SEPARATOR;
        include("annexes" . $sep . "migration-data" . $sep . "nhdn_dbviviskes.php");

        $output->writeln("1) Importing users...");
        $this->importUsers($user);
        $output->writeln("2) Importing events...");
        $this->importEvents($event);
        $output->writeln("3) Importing participations...");
        $this->importParticipations($participation, $user, $event);
        $output->writeln("4) Importing categories...");
        $this->importCategories($category);
        $output->writeln("5) Importing topics...");
        $this->importTopics($subject, $category);
        $output->writeln("6) Importing messages...");
        $this->importMessages($message, $user, $subject);
        $output->writeln("7) Importing articles...");
        $this->importArticles($article);
        $output->writeln("8) Importing partners...");
        $this->importPartners($partner);

        $output->writeln("Done!");
    }

    private function importUsers($users)
    {

        foreach ($users as $u) {
            $user = new User();
            $user->setEmail($u['email']);
            $user->setFirstName($u['firstName']);
            $user->setLastName($u['lastName']);
            $user->setPassword($u['password']);
            $user->setCelticName($u['celtName']);
            $user->setIsActive(false);
            $user->setIsAdmin($u['firstName'] === "Irina");
            $this->em->persist($user);
        }

        $this->em->flush();
    }

    private function importEvents($events)
    {

        foreach ($events as $e) {
            $event = new Event();
            $event->setTitle($e['title']);
            $event->setDescription($e['description']);
            $event->setLocation($e['location']);
            $event->setIsConfirmed(true);
            $event->setPrivacy($e['public'] === "1" ? "u" : "i");
            try {
                $event->setStart(new DateTime($e['startDate']));
            } catch (Exception $e) {
                $event->setStart(null);
            }
            try {
                $event->setEnd(new DateTime($e['endDate']));
            } catch (Exception $e) {
                $event->setEnd(null);
            }

            $this->em->persist($event);
        }

        $this->em->flush();
    }

    private function importParticipations($participations, $oldUsers, $oldEvents)
    {
        $newUsers = $this->em->getRepository(User::class)->findAll();
        $newEvents = $this->em->getRepository(Event::class)->findAll();

        foreach ($participations as $p) {
            $u = $this->findNewUser($p['userId'], $oldUsers, $newUsers);
            $e = $this->findNewEvent($p['eventId'], $oldEvents, $newEvents);

            if ($u && $e) {
                $status = $p['participation'];
                if($status !== "u") {
                    $participation = new Participation();
                    $participation->setUser($u);
                    $participation->setEvent($e);
                    $participation->setDay($e->getStart());
                    $participation->setStatus($p['participation']);
                    $this->em->persist($participation);
                }
            }
        }

        $this->em->flush();
    }

    private function importCategories($categories)
    {

        foreach ($categories as $c) {
            $category = new Category();
            $category->setLabel($c['title']);
            $this->em->persist($category);
        }

        $this->em->flush();
    }

    private function importTopics($topics, $oldCategories)
    {
        $newCategories = $this->em->getRepository(Category::class)->findAll();

        foreach ($topics as $t) {
            $topic = new Topic();
            $topic->setTitle($t['title']);
            $cat = $this->findNewCategory($t['categoryId'], $oldCategories, $newCategories);
            $topic->setCategory($cat);
            $topic->setPinned($t['pinned'] === "1");

            $this->em->persist($topic);
        }

        $this->em->flush();
    }

    private function importMessages($messages, $oldUsers, $oldTopics)
    {
        $newUsers = $this->em->getRepository(User::class)->findAll();
        $newTopics = $this->em->getRepository(Topic::class)->findAll();

        foreach ($messages as $m) {
            $message = new Message();
            $message->setContent($m['content']);
            $timestamp = new DateTime($m['dateTime']);
            $message->setCreated($timestamp);
            $message->setEdited($timestamp);

            $a = $this->findNewUser($m['userId'], $oldUsers, $newUsers);
            $message->setAuthor($a);
            $t = $this->findNewTopic($m['subjectId'], $oldTopics, $newTopics);
            $message->setTopic($t);

            $this->em->persist($message);
        }

        $this->em->flush();
    }

    private function importArticles($articles)
    {
        $author = $this->em->getRepository(User::class)->findOneBy(
            ["firstName" => "Laurène", "lastName" => "Glardon"]
        );

        foreach ($articles as $a) {
            $article = new Article();
            $article->setTitle($a['title']);
            $article->setContent("<h1>Coucou</h1>");
            $timestamp = new DateTime($a['date']);
            $article->setCreated($timestamp);
            $article->setEdited($timestamp);
            $article->setIsPublished(false);
            $article->setAuthor($author);

            $this->em->persist($article);
        }

        $this->em->flush();
    }

    private function importPartners($partners)
    {
        foreach ($partners as $p) {
            $partner = new Partner();
            $partner->setLabel($p['name']);
            $partner->setUrl($p['link']);

            $this->em->persist($partner);
        }

        $this->em->flush();
    }

    /**
     * @param $id
     * @param $oldUsers []
     * @param $newUsers User[]
     * @return User|null
     */
    private function findNewUser($id, $oldUsers, $newUsers)
    {
        foreach ($oldUsers as $ou) {
            if ($ou['id'] === $id) {
                foreach ($newUsers as $nu) {
                    if ($ou['email'] === $nu->getEmail() &&
                        $ou['firstName'] === $nu->getFirstName() &&
                        $ou['lastName'] === $nu->getLastName()) {
                        return $nu;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param $id
     * @param $oldCategories []
     * @param $newCategories Category[]
     * @return Category|null
     */
    private function findNewCategory($id, $oldCategories, $newCategories)
    {
        foreach ($oldCategories as $oc) {
            if ($oc['id'] === $id) {
                foreach ($newCategories as $nc) {
                    if ($oc['title'] === $nc->getLabel()) return $nc;
                }
            }
        }
        return null;
    }

    /**
     * @param $id
     * @param $oldTopics []
     * @param $newTopics Topic[]
     * @return Topic|null
     */
    private function findNewTopic($id, $oldTopics, $newTopics)
    {
        foreach ($oldTopics as $ot) {
            if ($ot['id'] === $id) {
                foreach ($newTopics as $nt) {
                    if ($ot['title'] === $nt->getTitle()) return $nt;
                }
            }
        }
        return null;
    }

    /**
     * @param $id
     * @param $oldEvents []
     * @param $newEvents Event[]
     * @return Event|null
     * @throws Exception
     */
    private function findNewEvent($id, $oldEvents, $newEvents)
    {
        foreach ($oldEvents as $oe) {
            if (intval($oe['id']) === intval($id)) {
                foreach ($newEvents as $ne) {
                    $oldDate = new DateTime($oe['startDate']);
                    $oldDate = $oldDate->format('Y-m-d');
                    $newDate = $ne->getStart()->format('Y-m-d');
                    if (strcmp(trim($oe['title']), trim($ne->getTitle())) === 0
                        && $oldDate == $newDate) {
                        return $ne;
                    }
                }
            }
        }
        return null;
    }
}