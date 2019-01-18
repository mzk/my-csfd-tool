<?php declare(strict_types = 1);

namespace App\Models\Console;

use Nette\Utils\Finder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MapperCommand extends BaseCommand
{

	public function __construct()
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		parent::configure();
		$this->setName('mapper')
			->setDescription('');
	}

	protected function execute(InputInterface $input, OutputInterface $output): void
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
			$output->writeln('processing ' . $directory->getBasename());
			if (\file_exists($directory->getPathname() . '/' . 'csfd.nfo') === false) {
				$output->writeln(\sprintf('moving %s', $directory->getBasename()));
				\rename($directory->getPathname(), '/Volumes/video/aa nove/' . $directory->getBasename());
			}
		}
	}
}
