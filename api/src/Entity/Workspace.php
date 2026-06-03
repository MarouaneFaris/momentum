<?php

namespace App\Entity;

use App\Repository\WorkspaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkspaceRepository::class)]
class Workspace
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $creator;

    /**
     * @var Collection<int, UserWorkspace>
     */
    #[ORM\OneToMany(targetEntity: UserWorkspace::class, mappedBy: 'workspace', cascade: ['remove'], orphanRemoval: true)]
    private Collection $userWorkspaces;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'workspace', cascade: ['remove'], orphanRemoval: true)]
    private Collection $projects;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->userWorkspaces = new ArrayCollection();
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return Collection<int, UserWorkspace>
     */
    public function getUserWorkspaces(): Collection
    {
        return $this->userWorkspaces;
    }

    public function addUserWorkspace(UserWorkspace $userWorkspace): static
    {
        if (!$this->userWorkspaces->contains($userWorkspace)) {
            $this->userWorkspaces->add($userWorkspace);
            $userWorkspace->setWorkspace($this);
        }

        return $this;
    }

    public function removeUserWorkspace(UserWorkspace $userWorkspace): static
    {
        $this->userWorkspaces->removeElement($userWorkspace);

        return $this;
    }
}
