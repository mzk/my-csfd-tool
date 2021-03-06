<?php declare(strict_types = 1);

namespace App\Models\Console;

use App\Entity\Movie;
use App\Entity\Rating;
use App\Models\Provider\EntityManagerProvider;
use App\Models\Utility\Downloader;
use App\Repository\MovieRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserRatingCommand extends BaseCommand
{

	const USER_NAME = 'mzk';

	/**
	 * @var EntityManagerProvider
	 */
	private $entityManagerProvider;

	/**
	 * @var Downloader
	 */
	private $downloader;

	/**
	 * @var MovieRepository
	 */
	private $movieRepository;

	/**
	 * @var Movie[]
	 */
	private $allMovies;

	/**
	 * @var OutputInterface
	 */
	private $output;

	public function __construct(EntityManagerProvider $entityManagerProvider, Downloader $downloader, MovieRepository $movieRepository)
	{
		parent::__construct();
		$this->entityManagerProvider = $entityManagerProvider;
		$this->downloader = $downloader;
		$this->movieRepository = $movieRepository;
	}

	protected function configure(): void
	{
		parent::configure();
		$this->setName('parse-user-rating')
			->setDescription('');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		$this->output = $output;
		$this->allMovies = $this->movieRepository->getAllByCsfdId();
		$this->parsePage('https://www.csfd.cz/uzivatel/116833-mzk/hodnoceni/');

		for ($i = 2; $i <= 21; $i++) {
			$this->parsePage(\sprintf('https://www.csfd.cz/uzivatel/116833-mzk/hodnoceni/strana-%s/', $i));
		}
	}

	public function parsePage(string $url): void
	{
		$this->output->writeln('parsing URL: ' . $url);
		$em = $this->entityManagerProvider->getMaster();
		$content = $this->downloader->get($url);
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
			$this->output->writeln('movie: ' . $name);
			$spans = $node->getElementsByTagName('td')->item(0)->getElementsByTagName('span');
			$year = $spans->item($spans->count() - 1)->nodeValue;
			preg_match('/\(\d\d\d\d\)/', $year, $matches);
			$year = (int)str_replace(['(', ')'], '', $matches[0]);
			$dateOfRating = $node->getElementsByTagName('td')->item(2)->nodeValue;
			$dateOfRating = \DateTime::createFromFormat('d.m.Y', $dateOfRating);
			$img = $node->getElementsByTagName('td')->item(1)->getElementsByTagName('img')->item(0);
			if ($img === null) {
				$ratingValue = 0;
			} else {
				$ratingValue = \strlen($img->getAttribute('alt'));
			}

			if (isset($this->allMovies[$csfdId])) {
				$movie = $this->allMovies[$csfdId];
			} else {
				$movie = new Movie($name, null, $year, null, null, $csfdId, $csfdUrl);
				$em->persist($movie);
				$this->allMovies[$csfdId] = $movie;
				$em->flush();
			}

			$rating = $movie->getRatingByUserName(self::USER_NAME);

			if ($rating instanceof Rating) {
				$rating->setDate($dateOfRating);
				$rating->setRating($ratingValue);
			} else {
				$rating = new Rating(self::USER_NAME, $movie, $ratingValue, $dateOfRating);
				$em->persist($rating);
			}
			$em->flush();
		}
	}
}
