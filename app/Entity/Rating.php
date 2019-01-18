<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity()
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(name="user_movie", columns={"user_name", "movie_id"})})
 */
class Rating
{

	use Identifier;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $userName;

	/**
	 * @ORM\ManyToOne(targetEntity="Movie", inversedBy="id", cascade={"persist"})
	 * @var Movie
	 */
	private $movie;

	/**
	 * @ORM\Column(type="smallint", nullable=TRUE)
	 * @var int
	 */
	private $rating;

	/**
	 * @ORM\Column(type="date", nullable=TRUE)
	 * @var \DateTime
	 */
	private $date;

	public function __construct(string $userName, Movie $movie, int $rating, \DateTime $date)
	{
		$this->userName = $userName;
		$this->movie = $movie;
		$this->rating = $rating;
		$this->date = $date;
	}

	public function getUserName(): string
	{
		return $this->userName;
	}

	public function setUserName(string $userName): void
	{
		$this->userName = $userName;
	}

	public function getMovie(): Movie
	{
		return $this->movie;
	}

	public function setMovie(Movie $movie): void
	{
		$this->movie = $movie;
	}

	public function getRating(): int
	{
		return $this->rating;
	}

	public function setRating(int $rating): void
	{
		$this->rating = $rating;
	}

	public function getDate(): \DateTime
	{
		return $this->date;
	}

	public function setDate(\DateTime $date): void
	{
		$this->date = $date;
	}
}
