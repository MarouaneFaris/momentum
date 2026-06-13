<?php

declare(strict_types=1);

namespace App\Controller\Api\Task;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Workspace;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractTaskController extends AbstractController
{
    protected function assertProjectBelongsToWorkspace(Project $project, Workspace $workspace): void
    {
        $projectWorkspaceId = $project->getWorkspace()->getId();
        $workspaceId = $workspace->getId();
        if ($projectWorkspaceId === null || $workspaceId === null || !$projectWorkspaceId->equals($workspaceId)) {
            throw new NotFoundHttpException();
        }
    }

    protected function assertTaskBelongsToProject(Task $task, Project $project): void
    {
        $taskProjectId = $task->getProject()->getId();
        $projectId = $project->getId();
        if ($taskProjectId === null || $projectId === null || !$taskProjectId->equals($projectId)) {
            throw new NotFoundHttpException();
        }
    }
}
