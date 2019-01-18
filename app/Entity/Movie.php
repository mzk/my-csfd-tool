<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
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
	 * @ORM\Column(type="text", nullable=TRUE)
	 * @var string
	 */
	private $sourceHtml;

	public function __construct(string $name, ?string $description, int $year, ?string $actors, ?float $csfdRating, int $csfdId, string $csfdUrl)
	{
		$this->name = $name;
		$this->description = $description;
		$this->year = $year;
		$this->actors = $actors;
		$this->csfdRating = $csfdRating;
		$this->csfdId = $csfdId;
		$this->csfdUrl = $csfdUrl;
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
}
