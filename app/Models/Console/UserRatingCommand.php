<?php declare(strict_types = 1);

namespace App\Models\Console;

use App\Entity\Movie;
use App\Models\Provider\EntityManagerProvider;
use App\Models\Utility\Downloader;
use GuzzleHttp\Client;
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

		try {
			$dom = new \DOMDocument();
			@$dom->loadHTML($content);
			$finder = new \DOMXPath($dom);
			$nodesByElement = $finder->query("//table");
			/** @var \DOMElement $table */
			$table = $nodesByElement->item(0);

			/*

			<tr>
						<td><a href="/film/221638-faunuv-labyrint/" class="film c1">Faunův labyrint</a> <span class="film-year" dir="ltr">(2006)</span></td>
						<td><img src="https://img.csfd.cz/assets/b343/images/rating/stars/4.gif" class="rating" width="32" alt="****" /></td>
						<td>24.03.2018</td>
					</tr>

			 */
			$tbody = $table->getElementsByTagName('tbody')->item(0);
			$tr = $tbody->getElementsByTagName('tr');
			/** @var \DOMText $node */
			foreach ($tr as $node) {


				$node->textContent;
			}
		} catch (\Exception $e) {
			$e->getMessage();
		}

		$em = $this->entityManagerProvider->getMaster();
		$movie = new Movie('sdf', 'sdf', 'sdf', 123, 2323, 'http://sdfsdf');
		$em->persist($movie);
		$em->flush();

	}
}
