<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
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

        $this->makeUserWorkspace($alice, $aliceWs, WorkspaceRole::Owner, $manager);
        $this->makeUserWorkspace($bob, $aliceWs, WorkspaceRole::Member, $manager);
        $this->makeUserWorkspace($bob, $bobWs, WorkspaceRole::Owner, $manager);

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

    private function makeUserWorkspace(User $user, Workspace $workspace, WorkspaceRole $role, ObjectManager $manager): void
    {
        $uw = new UserWorkspace();
        $uw->setUser($user);
        $uw->setWorkspace($workspace);
        $uw->setRole($role);
        $manager->persist($uw);
    }
}
