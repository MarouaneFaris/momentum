<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Enum\TaskStatus;
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

        $apiRedesign = $this->makeProject('API Redesign', 'Redesign the public API surface', ProjectStatus::Active, $aliceWs, $aliceOwner, $manager);
        $mobileApp = $this->makeProject('Mobile App', 'React Native mobile client', ProjectStatus::Active, $aliceWs, $bobMember, $manager);
        $this->makeProject('Internal Dashboard', null, ProjectStatus::Draft, $aliceWs, $aliceOwner, $manager);
        $legacyMigration = $this->makeProject('Legacy Migration', 'Migrate off the old monolith', ProjectStatus::Archived, $aliceWs, $aliceOwner, $manager);

        $personalSite = $this->makeProject('Personal Site', 'Portfolio and blog', ProjectStatus::Active, $bobWs, $bobOwner, $manager);
        $this->makeProject('Side Project', null, ProjectStatus::Draft, $bobWs, $bobOwner, $manager);

        // API Redesign tasks
        $this->makeTask('Design new endpoint schema', TaskStatus::Todo, $apiRedesign, $alice, null, $manager);
        $this->makeTask('Write OpenAPI documentation', TaskStatus::Todo, $apiRedesign, $alice, $bob, $manager);
        $this->makeTask('Implement authentication endpoints', TaskStatus::InProgress, $apiRedesign, $alice, $alice, $manager);
        $this->makeTask('Add rate limiting middleware', TaskStatus::InProgress, $apiRedesign, $alice, $bob, $manager);
        $this->makeTask('Set up project scaffolding', TaskStatus::Done, $apiRedesign, $alice, $alice, $manager);
        $this->makeTask('Configure CI pipeline', TaskStatus::Done, $apiRedesign, $alice, null, $manager);

        // Mobile App tasks
        $this->makeTask('Create onboarding flow', TaskStatus::Todo, $mobileApp, $bob, null, $manager);
        $this->makeTask('Push notification setup', TaskStatus::Todo, $mobileApp, $bob, $bob, $manager);
        $this->makeTask('Build login screen', TaskStatus::InProgress, $mobileApp, $bob, $bob, $manager);
        $this->makeTask('Integrate with REST API', TaskStatus::InProgress, $mobileApp, $bob, $alice, $manager);
        $this->makeTask('Project kickoff', TaskStatus::Done, $mobileApp, $bob, null, $manager);
        $this->makeTask('Set up Expo project', TaskStatus::Done, $mobileApp, $bob, $bob, $manager);

        // Legacy Migration tasks (archived project, all done)
        $this->makeTask('Audit existing endpoints', TaskStatus::Done, $legacyMigration, $alice, $alice, $manager);
        $this->makeTask('Extract user service', TaskStatus::Done, $legacyMigration, $alice, $bob, $manager);
        $this->makeTask('Decommission old servers', TaskStatus::Done, $legacyMigration, $alice, $alice, $manager);

        // Personal Site tasks (Bob's workspace)
        $this->makeTask('Write about page', TaskStatus::Todo, $personalSite, $bob, $bob, $manager);
        $this->makeTask('Add dark mode', TaskStatus::Todo, $personalSite, $bob, null, $manager);
        $this->makeTask('Update portfolio section', TaskStatus::InProgress, $personalSite, $bob, $bob, $manager);
        $this->makeTask('Deploy to production', TaskStatus::Done, $personalSite, $bob, $bob, $manager);

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

    private function makeProject(string $name, ?string $description, ProjectStatus $status, Workspace $workspace, UserWorkspace $owner, ObjectManager $manager): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
        $project->setStatus($status);
        $project->setWorkspace($workspace);
        $project->setOwner($owner);
        $manager->persist($project);

        return $project;
    }

    private function makeTask(string $title, TaskStatus $status, Project $project, User $creator, ?User $assignee, ObjectManager $manager): void
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setStatus($status);
        $task->setProject($project);
        $task->setCreator($creator);
        $task->setAssignee($assignee);
        $manager->persist($task);
    }
}
