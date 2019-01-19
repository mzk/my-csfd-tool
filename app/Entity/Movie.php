<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @ORM\Entity()
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(name="csfd_id", columns={"csfd_id"})})
 */
class Movie
{

	use Identifier;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $name;

	/**
	 * @ORM\Column(type="text", nullable=TRUE)
	 * @var string
	 */
	private $description;

	/**
	 * @ORM\Column(type="smallint", nullable=TRUE)
	 * @var int
	 */
	private $year;

	/**
	 * @ORM\Column(type="text", nullable=TRUE)
	 * @var string
	 */
	private $actors;

	/**
	 * @ORM\Column(type="float", nullable=TRUE)
	 * @var float
	 */
	private $csfdRating;

	/**
	 * @ORM\Column(type="integer", nullable=TRUE)
	 * @var int
	 */
	private $csfdId;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $csfdUrl;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $pathToFolder;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $countries;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $genre;

	/**
	 * @ORM\Column(type="text", nullable=TRUE)
	 * @var string
	 */
	private $sourceHtml;

	/**
	 * @ORM\OneToMany(targetEntity="Rating", mappedBy="movie", cascade={"persist"})
	 * @var Rating[]|\Doctrine\Common\Collections\ArrayCollection
	 */
	private $ratings;

	public function __construct(string $name, ?string $description, int $year, ?string $actors, ?float $csfdRating, int $csfdId, string $csfdUrl)
	{
		$this->name = $name;
		$this->description = $description;
		$this->year = $year;
		$this->actors = $actors;
		$this->csfdRating = $csfdRating;
		$this->csfdId = $csfdId;
		$this->csfdUrl = $csfdUrl;
		$this->ratings = new ArrayCollection();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): void
	{
		$this->description = $description;
	}

	public function getActors(): string
	{
		return $this->actors;
	}

	public function setActors(string $actors): void
	{
		$this->actors = $actors;
	}

	public function getCsfdRating(): float
	{
		return $this->csfdRating;
	}

	public function setCsfdRating(float $csfdRating): void
	{
		$this->csfdRating = $csfdRating;
	}

	public function getCsfdId(): int
	{
		return $this->csfdId;
	}

	public function setCsfdId(string $csfdId): void
	{
		$this->csfdId = $csfdId;
	}

	public function getCsfdUrl(): string
	{
		return $this->csfdUrl;
	}

	public function setCsfdUrl(string $csfdUrl): void
	{
		$this->csfdUrl = $csfdUrl;
	}

	public function getPathToFolder(): string
	{
		return $this->pathToFolder;
	}

	public function setPathToFolder(string $pathToFolder): void
	{
		$this->pathToFolder = $pathToFolder;
	}

	public function setYear(int $year): void
	{
		$this->year = $year;
	}

	public function getYear(): int
	{
		return $this->year;
	}

	/**
	 * @return Rating[]
	 */
	public function getRatings(): array
	{
		return $this->ratings->toArray();
	}

	public function getRatingByUserName(string $userName): ?Rating
	{
		foreach ($this->ratings as $rating) {
			if ($rating->getUserName() === $userName) {
				return $rating;
			}
		}

		return null;
	}

	public function addRating(Rating $rating): void
	{
		$this->ratings->add($rating);
	}

	public function getCountries(): string
	{
		return $this->countries;
	}

	public function setCountries(string $countries): void
	{
		$this->countries = $countries;
	}

	public function getGenre(): string
	{
		return $this->genre;
	}

	public function setGenre(string $genre): void
	{
		$this->genre = $genre;
	}
}
