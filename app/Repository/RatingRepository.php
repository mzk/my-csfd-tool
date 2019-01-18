<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Movie;
use App\Entity\Rating;
use App\Models\Provider\EntityManagerProvider;

class RatingRepository
{

	/**
	 * @var EntityManagerProvider
	 */
	private $entityManagerProvider;

	public function __construct(EntityManagerProvider $entityManagerProvider)
	{
		$this->entityManagerProvider = $entityManagerProvider;
	}

	/**
	 * @return Movie[]
	 */
	public function getAllByMovieId(string $userName): array
	{
		return $this->entityManagerProvider->getMaster()->getRepository(Rating::class)->createQueryBuilder('r', 'r.movie')
			->select('r')
			->where('r.userName = :userName')->setParameter('userName', $userName)
			->getQuery()->getResult();
	}
}
