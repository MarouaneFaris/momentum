<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class WorkspaceFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
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
