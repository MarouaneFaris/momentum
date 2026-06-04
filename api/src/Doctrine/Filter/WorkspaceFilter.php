<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use App\Entity\UserWorkspace;
use App\Entity\WorkspaceInvitation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class WorkspaceFilter extends SQLFilter
{
    private const array EXCLUDED = [UserWorkspace::class, WorkspaceInvitation::class];

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (\in_array($targetEntity->getName(), self::EXCLUDED, true)) {
            return '';
        }

        if (!$targetEntity->hasAssociation('workspace')) {
            return '';
        }

        $mapping = $targetEntity->getAssociationMapping('workspace');

        if (!($mapping instanceof \Doctrine\ORM\Mapping\ToOneOwningSideMapping) || empty($mapping->joinColumns)) {
            return '';
        }

        $column = $mapping->joinColumns[0]->name;

        // workspace_id is stored as BINARY(16); convert the UUID string parameter before comparing
        return sprintf(
            '%s.%s = UNHEX(REPLACE(%s, \'-\', \'\'))',
            $targetTableAlias,
            $column,
            $this->getParameter('workspaceId'),
        );
    }
}
