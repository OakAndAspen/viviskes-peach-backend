<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TopicRepository")
 */
class Topic
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Category", inversedBy="topics")
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event", inversedBy="topics")
     */
    private $event;

    /**
     * @ORM\Column(type="boolean")
     */
    private $pinned;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="topic", orphanRemoval=true)
     */
    private $messages;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="unreadTopics")
     */
    private $unreadUsers;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->unreadUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getPinned(): ?bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): self
    {
        $this->pinned = $pinned;

        return $this;
    }

    /**
     * @return Collection|Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setTopic($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getTopic() === $this) {
                $message->setTopic(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUnreadUsers(): Collection
    {
        return $this->unreadUsers;
    }

    public function addUnreadUser(User $unreadUser): self
    {
        if (!$this->unreadUsers->contains($unreadUser)) {
            $this->unreadUsers[] = $unreadUser;
            $unreadUser->addUnreadTopic($this);
        }

        return $this;
    }

    public function removeUnreadUser(User $unreadUser): self
    {
        if ($this->unreadUsers->contains($unreadUser)) {
            $this->unreadUsers->removeElement($unreadUser);
            $unreadUser->removeUnreadTopic($this);
        }

        return $this;
    }
}
