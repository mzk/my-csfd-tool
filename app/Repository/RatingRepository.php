<?php declare(strict_types = 1);

namespace App\Repository;

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
}
