<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Enum\WorkspaceRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $alice = $this->makeUser('alice@example.com', 'password123!', 'Alice Martin', $manager);
        $bob = $this->makeUser('bob@example.com', 'password123!', 'Bob Johnson', $manager);
        $this->makeUser('charlie@example.com', 'password123!', 'Charlie Brown', $manager);

        $aliceWs = $this->makeWorkspace("Alice's Workspace", $alice, $manager);
        $bobWs = $this->makeWorkspace("Bob's Workspace", $bob, $manager);

        $aliceOwner = $this->makeUserWorkspace($alice, $aliceWs, WorkspaceRole::Owner, $manager);
        $bobMember = $this->makeUserWorkspace($bob, $aliceWs, WorkspaceRole::Member, $manager);
        $bobOwner = $this->makeUserWorkspace($bob, $bobWs, WorkspaceRole::Owner, $manager);

        $this->makeProject('API Redesign', 'Redesign the public API surface', ProjectStatus::Active, $aliceWs, $aliceOwner, $manager);
        $this->makeProject('Mobile App', 'React Native mobile client', ProjectStatus::Active, $aliceWs, $bobMember, $manager);
        $this->makeProject('Internal Dashboard', null, ProjectStatus::Draft, $aliceWs, $aliceOwner, $manager);
        $this->makeProject('Legacy Migration', 'Migrate off the old monolith', ProjectStatus::Archived, $aliceWs, $aliceOwner, $manager);

        $this->makeProject('Personal Site', 'Portfolio and blog', ProjectStatus::Active, $bobWs, $bobOwner, $manager);
        $this->makeProject('Side Project', null, ProjectStatus::Draft, $bobWs, $bobOwner, $manager);

        $manager->flush();
    }

    private function makeUser(string $email, string $plainPassword, string $name, ObjectManager $manager): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));
        $manager->persist($user);

        return $user;
    }

    private function makeWorkspace(string $name, User $creator, ObjectManager $manager): Workspace
    {
        $ws = new Workspace();
        $ws->setName($name);
        $ws->setCreator($creator);
        $manager->persist($ws);

        return $ws;
    }

    private function makeUserWorkspace(User $user, Workspace $workspace, WorkspaceRole $role, ObjectManager $manager): UserWorkspace
    {
        $uw = new UserWorkspace();
        $uw->setUser($user);
        $uw->setWorkspace($workspace);
        $uw->setRole($role);
        $manager->persist($uw);

        return $uw;
    }

    private function makeProject(string $name, ?string $description, ProjectStatus $status, Workspace $workspace, UserWorkspace $owner, ObjectManager $manager): void
    {
        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
        $project->setStatus($status);
        $project->setWorkspace($workspace);
        $project->setOwner($owner);
        $manager->persist($project);
    }
}
