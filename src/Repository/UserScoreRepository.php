<?php

namespace App\Repository;

use App\Entity\UserScore;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserScore>
 */
class UserScoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserScore::class);
    }

    /**
     * Obtiene las top 10 puntuaciones para un juego específico
     */
    public function findTopScoresByGame(int $gameId, int $limit = 10): array
    {
        $results = $this->createQueryBuilder('us')
            ->select('us', 'u.username')
            ->join('us.user', 'u')
            ->where('us.game = :gameId')
            ->setParameter('gameId', $gameId)
            ->orderBy('us.score', 'DESC')
            ->addOrderBy('us.playedAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Formatear los resultados para que sean más fáciles de usar en el template
        $formatted = [];
        foreach ($results as $result) {
            $formatted[] = [
                'score' => $result[0],
                'username' => $result['username'] ?? 'Usuario',
            ];
        }
        
        return $formatted;
    }

    /**
     * Obtiene la mejor puntuación y posición de un usuario para un juego específico
     */
    public function findUserBestScoreAndPosition(int $gameId, int $userId): ?array
    {
        // Obtener la mejor puntuación del usuario
        $userBestScore = $this->createQueryBuilder('us')
            ->select('us', 'u.username')
            ->join('us.user', 'u')
            ->where('us.game = :gameId')
            ->andWhere('us.user = :userId')
            ->setParameter('gameId', $gameId)
            ->setParameter('userId', $userId)
            ->orderBy('us.score', 'DESC')
            ->addOrderBy('us.playedAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$userBestScore) {
            return null;
        }

        // Contar cuántas puntuaciones son mejores que la del usuario
        $position = $this->createQueryBuilder('us')
            ->select('COUNT(us.id)')
            ->where('us.game = :gameId')
            ->andWhere('(us.score > :userScore OR (us.score = :userScore AND us.playedAt < :userPlayedAt))')
            ->setParameter('gameId', $gameId)
            ->setParameter('userScore', $userBestScore[0]->getScore())
            ->setParameter('userPlayedAt', $userBestScore[0]->getPlayedAt())
            ->getQuery()
            ->getSingleScalarResult();

        $position = (int)$position + 1; // +1 porque la posición empieza en 1

        return [
            'score' => $userBestScore[0],
            'username' => $userBestScore['username'] ?? 'Usuario',
            'position' => $position,
        ];
    }
}

