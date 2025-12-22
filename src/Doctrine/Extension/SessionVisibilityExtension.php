<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Session;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Hide past sessions from non-admin users.
 */
final class SessionVisibilityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        $this->restrict($queryBuilder, $resourceClass, $queryNameGenerator->generateParameterName('session_visibility'));
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        $this->restrict($queryBuilder, $resourceClass, 'session_visibility_now');
    }

    private function restrict(QueryBuilder $queryBuilder, string $resourceClass, string $parameterName): void
    {
        if (Session::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0] ?? null;
        if (null === $alias) {
            return;
        }

        $expression = sprintf('COALESCE(%1$s.endDate, %1$s.startDate)', $alias);

        $queryBuilder
            ->andWhere(sprintf('%s >= :%s', $expression, $parameterName))
            ->setParameter($parameterName, new \DateTimeImmutable());
    }
}
