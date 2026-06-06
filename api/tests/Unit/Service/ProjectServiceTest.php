<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use App\Entity\UserWorkspace;
use App\Entity\Workspace;
use App\Enum\ProjectStatus;
use App\Service\ProjectService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

final class ProjectServiceTest extends TestCase
{
    private ProjectService $service;
    private EntityManagerInterface&MockObject $em;
    private WorkflowInterface&MockObject $workflow;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->workflow = $this->createMock(WorkflowInterface::class);
        $this->service = new ProjectService($this->em, $this->workflow);
    }

    public function testCreatePersistsAndFlushes(): void
    {
        $workspace = new Workspace();
        $owner = new UserWorkspace();

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Project::class));
        $this->em->expects($this->once())->method('flush');

        $project = $this->service->create($workspace, $owner, 'My Project', 'A description', ProjectStatus::Active);

        self::assertSame('My Project', $project->getName());
        self::assertSame('A description', $project->getDescription());
        self::assertSame(ProjectStatus::Active, $project->getStatus());
        self::assertSame($workspace, $project->getWorkspace());
        self::assertSame($owner, $project->getOwner());
    }

    public function testCreateWithNullDescription(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $project = $this->service->create(new Workspace(), new UserWorkspace(), 'Project', null, ProjectStatus::Draft);

        self::assertNull($project->getDescription());
        self::assertSame(ProjectStatus::Draft, $project->getStatus());
    }

    public function testUpdateUpdatesProvidedFields(): void
    {
        $project = $this->makeProject('Original', 'Old desc', ProjectStatus::Draft);

        $this->workflow
            ->method('getEnabledTransitions')
            ->willReturn([new Transition('start', ['draft'], ['active'])]);
        $this->workflow->expects($this->once())->method('apply')->with($project, 'start');
        $this->em->expects($this->once())->method('flush');

        $this->service->update($project, 'Updated', 'New desc', ProjectStatus::Active);

        self::assertSame('Updated', $project->getName());
        self::assertSame('New desc', $project->getDescription());
    }

    public function testUpdateSkipsNullFields(): void
    {
        $project = $this->makeProject('Original', 'Old desc', ProjectStatus::Draft);

        $this->workflow->expects($this->never())->method('apply');
        $this->em->expects($this->once())->method('flush');

        $this->service->update($project, null, null, null);

        self::assertSame('Original', $project->getName());
        self::assertSame('Old desc', $project->getDescription());
        self::assertSame(ProjectStatus::Draft, $project->getStatus());
    }

    public function testUpdateSameStatusSkipsWorkflow(): void
    {
        $project = $this->makeProject('Name', null, ProjectStatus::Draft);

        $this->workflow->expects($this->never())->method('apply');
        $this->em->expects($this->once())->method('flush');

        $this->service->update($project, null, null, ProjectStatus::Draft);
    }

    public function testUpdateClearsDescriptionWithEmptyString(): void
    {
        $project = $this->makeProject('Name', 'Has desc', ProjectStatus::Active);

        $this->workflow->expects($this->never())->method('apply');
        $this->em->expects($this->once())->method('flush');

        $this->service->update($project, null, '', null);

        self::assertNull($project->getDescription());
    }

    public function testUpdateThrowsOnInvalidTransition(): void
    {
        $project = $this->makeProject('Name', null, ProjectStatus::Draft);

        $this->workflow->method('getEnabledTransitions')->willReturn([]);
        $this->em->expects($this->never())->method('flush');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transition project from "draft" to "archived".');

        $this->service->update($project, null, null, ProjectStatus::Archived);
    }

    public function testDeleteRemovesAndFlushes(): void
    {
        $project = $this->makeProject('To delete', null, ProjectStatus::Draft);

        $this->em->expects($this->once())->method('remove')->with($project);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete($project);
    }

    public function testAssignGuestPersistsAndFlushes(): void
    {
        $project = $this->makeProject('Project', null, ProjectStatus::Active);
        $user = new User();

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(UserProject::class));
        $this->em->expects($this->once())->method('flush');

        $assignment = $this->service->assignGuest($project, $user);

        self::assertSame($project, $assignment->getProject());
        self::assertSame($user, $assignment->getUser());
    }

    public function testRemoveGuestRemovesAndFlushes(): void
    {
        $assignment = new UserProject();

        $this->em->expects($this->once())->method('remove')->with($assignment);
        $this->em->expects($this->once())->method('flush');

        $this->service->removeGuest($assignment);
    }

    private function makeProject(string $name, ?string $description, ProjectStatus $status): Project
    {
        $project = new Project();
        $project->setWorkspace(new Workspace());
        $project->setOwner(new UserWorkspace());
        $project->setName($name);
        $project->setDescription($description);
        $project->setStatus($status);

        return $project;
    }
}
