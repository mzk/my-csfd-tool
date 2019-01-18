<?php declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Movie;
use App\Models\Provider\EntityManagerProvider;

class MovieRepository
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
	public function getAllByCsfdId(): array
	{
		return $this->entityManagerProvider->getMaster()->getRepository(Movie::class)->createQueryBuilder('m', 'm.csfdId')
			->select('m')
			//->addSelect('m.csfdId')
			->addSelect('ratings')
			->leftJoin('m.ratings', 'ratings')
			->getQuery()->getResult();
	}
}
