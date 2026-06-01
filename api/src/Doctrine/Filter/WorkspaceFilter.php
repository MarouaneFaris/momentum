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

        if (empty($mapping['joinColumns'])) {
            return '';
        }

        $column = $mapping['joinColumns'][0]['name'];

        return sprintf('%s.%s = %s', $targetTableAlias, $column, $this->getParameter('workspaceId'));
    }
}
