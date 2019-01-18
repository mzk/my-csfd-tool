<?php declare(strict_types = 1);

namespace App\Models\Console;

use App\Entity\Movie;
use App\Entity\Rating;
use App\Models\Provider\EntityManagerProvider;
use App\Models\Utility\Downloader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserRatingCommand extends BaseCommand
{

	/**
	 * @var EntityManagerProvider
	 */
	private $entityManagerProvider;

	/**
	 * @var Downloader
	 */
	private $downloader;

	public function __construct(EntityManagerProvider $entityManagerProvider, Downloader $downloader)
	{
		parent::__construct();
		$this->entityManagerProvider = $entityManagerProvider;
		$this->downloader = $downloader;
	}

	protected function configure(): void
	{
		parent::configure();
		$this->setName('parse-user-rating')
			->setDescription('');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		$content = $this->downloader->get('https://www.csfd.cz/uzivatel/116833-mzk/hodnoceni/');
		$em = $this->entityManagerProvider->getMaster();

		try {
			$dom = new \DOMDocument();
			@$dom->loadHTML($content);
			$finder = new \DOMXPath($dom);
			$nodesByElement = $finder->query("//table");
			/** @var \DOMElement $table */
			$table = $nodesByElement->item(0);

			$tbody = $table->getElementsByTagName('tbody')->item(0);
			$tr = $tbody->getElementsByTagName('tr');
			/** @var \DOMElement $node */
			foreach ($tr as $node) {
				/** @var \DOMElement $href */
				$csfdUrl = $node->getElementsByTagName('a')->item(0)->getAttribute('href');
				preg_match('!\d+!', $csfdUrl, $matches);
				$csfdId = (int)$matches[0];
				$name = $node->getElementsByTagName('td')->item(0)->nodeValue;
				$spans = $node->getElementsByTagName('td')->item(0)->getElementsByTagName('span');
				$year = $spans->item($spans->count() - 1)->nodeValue;
				preg_match('/\(\d\d\d\d\)/', $year, $matches);
				$year = (int) str_replace(['(', ')'], '', $matches[0]);
				$dateOfRating = $node->getElementsByTagName('td')->item(2)->nodeValue;
				$dateOfRating = \DateTime::createFromFormat('d.m.Y', $dateOfRating);
				$rating = \strlen($node->getElementsByTagName('td')->item(1)->getElementsByTagName('img')->item(0)->getAttribute('alt'));

				$movie = new Movie($name, null, $year, null, null, $csfdId, $csfdUrl);
				$rating = new Rating('mzk', $movie, $rating, $dateOfRating);
				$em->persist($movie);
				$em->persist($rating);
				$em->flush();
			}
		} catch (\Exception $e) {
			$e->getMessage();
		}
	}
}
