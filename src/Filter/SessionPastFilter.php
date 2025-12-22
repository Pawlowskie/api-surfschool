<?php

namespace App\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Session;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyInfo\Type;

/**
 * Filter Sessions by whether they are past or upcoming.
 */
final class SessionPastFilter extends AbstractFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        if ('isPast' !== $property || Session::class !== $resourceClass || null === $value) {
            return;
        }

        $boolValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (null === $boolValue) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];
        $parameter = $queryNameGenerator->generateParameterName('session_past');
        $now = new \DateTimeImmutable();

        $expression = sprintf('COALESCE(%1$s.endDate, %1$s.startDate)', $alias);

        if (true === $boolValue) {
            $queryBuilder
                ->andWhere(sprintf('%s < :%s', $expression, $parameter))
                ->setParameter($parameter, $now);
        } else {
            $queryBuilder
                ->andWhere(sprintf('%s >= :%s', $expression, $parameter))
                ->setParameter($parameter, $now);
        }
    }

    public function getDescription(string $resourceClass): array
    {
        if (Session::class !== $resourceClass) {
            return [];
        }

        return [
            'isPast' => [
                'property' => 'isPast',
                'type' => Type::BUILTIN_TYPE_BOOL,
                'required' => false,
                'description' => 'Filtre les sessions passées (true) ou à venir (false).',
                'openapi' => [
                    'name' => 'isPast',
                    'type' => 'boolean',
                ],
            ],
        ];
    }
}
