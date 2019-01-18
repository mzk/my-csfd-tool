<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @property-read int $id
 */
trait Identifier
{

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 * @var integer
	 */
	private $id;

	/**
	 * @return int
	 */
	final public function getId(): int
	{
		return $this->id;
	}

	public function __clone()
	{
		$this->id = null;
	}
}
