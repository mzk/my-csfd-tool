<?php declare(strict_types = 1);

namespace App\Models\Console;

use App\Models\Utility\Downloader;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MapperCommand extends BaseCommand
{

	/**
	 * @var Downloader
	 */
	private $downloader;

	public function __construct(Downloader $downloader)
	{
		parent::__construct();
		$this->downloader = $downloader;
	}

	protected function configure(): void
	{
		parent::configure();
		$this->setName('mapper')
			->setDescription('');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
	{
		//		$this->executeParseSearch($input, $output);
		//		$this->executeDownloadImages($input, $output);
		$this->executeMoveDirectories($input, $output);
	}

	protected function executeMoveDirectories(InputInterface $input, OutputInterface $output): void
	{
		$directories = Finder::findDirectories('*')->in([
			'/Volumes/video/AkcniKomedie/',
			'/Volumes/video/Animovane/',
			'/Volumes/video/Ceske filmy/',
			'/Volumes/video/Pohadky/',
			'/Volumes/video/Simca/',
		]);

		/** @var \SplFileInfo $directory */
		foreach ($directories as $directory) {
			$csfdNfo = \file_get_contents($directory->getPathname() . '/' . 'csfd.nfo');
			$xml = new \SimpleXMLElement($csfdNfo);

			$output->writeln(\sprintf('processing %s => %s %s', $directory->getBasename(), $xml->countries, $xml->genre));

			$csfdNfo = \file_get_contents($directory->getPathname() . '/' . 'csfd.nfo');
			$xml = new \SimpleXMLElement($csfdNfo);

			if (Strings::contains((string)$xml->countries, 'Česko') || Strings::contains((string)$xml->countries, 'Česká')) {
				$output->writeln(\sprintf('moving into ceske filmy %s', $directory->getBasename()));
				\rename($directory->getPathname(), '/Volumes/video/Ceske filmy/' . $directory->getBasename());
			}

			if (Strings::contains((string)$xml->genre, 'Animovaný')) {
				$output->writeln(\sprintf('moving into animované %s', $directory->getBasename()));
				\rename($directory->getPathname(), '/Volumes/video/Animovane/' . $directory->getBasename());
			}
		}
	}

	protected function executeDownloadImages(InputInterface $input, OutputInterface $output): void
	{
		$directories = Finder::findDirectories('*')->in([
			'/Volumes/video/AkcniKomedie/',
			'/Volumes/video/Animovane/',
			'/Volumes/video/Ceske filmy/',
			'/Volumes/video/Pohadky/',
			'/Volumes/video/Simca/',
		]);

		/** @var \SplFileInfo $directory */
		foreach ($directories as $directory) {
			if (\file_exists($directory->getPathname() . '/' . 'csfd.nfo') === false) {
				$output->writeln(\sprintf('<error>csfd.nfo not exists in %s</error>', $directory->getBasename()));
				continue;
			}
			$output->writeln('processing ' . $directory->getBasename());

			$csfdNfo = \file_get_contents($directory->getPathname() . '/' . 'csfd.nfo');
			$xml = new \SimpleXMLElement($csfdNfo);
			if (Strings::contains((string)$xml->thumb, 'https:https://')) {
				$output->writeln(\sprintf('fix xml->thumb %s', $directory->getBasename()));
				$xml->thumb = \str_replace('https:https://', 'https://', (string)$xml->thumb);
				\rename($directory->getPathname() . '/' . 'csfd.nfo', $directory->getPathname() . '/' . 'csfd.nfo.backup');
				\file_put_contents($directory->getPathname() . '/' . 'csfd.nfo', html_entity_decode($xml->asXML()));
			}

			$poster = \str_replace('?h180', '', $xml->thumb);
			$folder = $poster . '?h180';

			$pathToFolderJpg = $directory->getPathname() . '/' . 'folder.jpg';
			$pathToPosterJpg = $directory->getPathname() . '/' . 'poster.jpg';
			if (\file_exists($pathToFolderJpg) === false) {
				$output->writeln(\sprintf('downloading folder for %s', $directory->getBasename()));
				$image = \file_get_contents($poster);
				\file_put_contents($pathToFolderJpg, $image);
			}

			if (\file_exists($pathToPosterJpg) === false) {
				$output->writeln(\sprintf('downloading poster for %s', $directory->getBasename()));
				$image = \file_get_contents($folder);
				\file_put_contents($pathToPosterJpg, $image);
			}
		}
	}

	protected function executeParseSearch(InputInterface $input, OutputInterface $output): void
	{
		$directories = Finder::findDirectories('*')->in([
			'/Volumes/video/aa nove/',
		]);
		/** @var \SplFileInfo $directory */
		foreach ($directories as $directory) {
			$output->writeln('processing ' . $directory->getBasename());
			if (\file_exists($directory->getPathname() . '/' . 'csfd.nfo') === true) {
				$output->writeln(\sprintf('moving %s', $directory->getBasename()));
				\rename($directory->getPathname(), '/Volumes/video/AkcniKomedie/' . $directory->getBasename());
				continue;
			}
			$content = $this->downloader->get('https://www.csfd.cz/hledat/?q=' . \urlencode($directory->getBasename()));

			$dom = new \DOMDocument();
			@$dom->loadHTML($content);
			$div = $dom->getElementById('search-films');
			if ($div === null) {
				$output->writeln(\sprintf('<error>not found %s</error>', $directory->getBasename()));
				continue;
			}
			$liTags = $div->getElementsByTagName('li');
			if ($liTags->count() === 0) {
				$output->writeln(\sprintf('<error>not found %s</error>', $directory->getBasename()));
				continue;
			}
			$parsedMovie = $liTags->item(0);
			$csfdUrl = $parsedMovie->getElementsByTagName('a')->item(0)->getAttribute('href');
			preg_match('/\/film\/(\d+)-/', $csfdUrl, $matches);
			$csfdId = (int)$matches[1];
			$poster = 'https:' . \str_replace('?h180', '', $parsedMovie->getElementsByTagName('img')->item(0)->getAttribute('src'));
			$name = $parsedMovie->getElementsByTagName('h3')->item(0)->nodeValue;

			$p = \explode(',', $parsedMovie->getElementsByTagName('p')->item(0)->nodeValue);
			$genre = \trim($p[0]);
			$country = \trim($p[1]);
			$year = (int)trim($p[2]);

			$peoples = \trim($parsedMovie->getElementsByTagName('p')->item(1)->nodeValue);

			preg_match('/Režie: (.*)\n/', $peoples, $matches);
			$director = $matches[1] ?? '';
			preg_match('/Hrají: (.*)/', $peoples, $matches);
			$actors = $matches[1] ?? '';
			$file = $this->createTemplate($name, $year, $poster, $csfdId, $csfdUrl, $country, $genre, $director, $actors);
			\file_put_contents($directory->getPathname() . '/' . 'csfd.nfo', $file);
			$output->writeln('processing:' . $name);
		}
	}

	private function createTemplate(string $name, int $year, string $poster, int $csfdId, string $csfdUrl, string $country, string $genre, string $director, string $actors): string
	{
		return '<movie>
	<title>' . $name . '</title>
	<originaltitle></originaltitle>
	<sorttitle></sorttitle>
	<set></set>
	<rating></rating>
	<year>' . $year . '</year>
	<outline></outline>
	<plot></plot>
	<tagline></tagline>
	<runtime></runtime>
	<thumb>' . $poster . '</thumb>
	<mpaa></mpaa>
	<playcount></playcount>
	<id></id>
	<csfdId>' . $csfdId . '</csfdId>
	<csfdUrl>' . $csfdUrl . '</csfdUrl>
	<countries>' . $country . '</countries>
	<trailer></trailer>
	<genre>' . $genre . '</genre>
	<credits></credits>
	<director>' . $director . '</director>
	<actor>' . $actors . '</actor>
</movie>
	';
	}
}
