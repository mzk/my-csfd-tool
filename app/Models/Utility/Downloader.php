<?php declare(strict_types = 1);

namespace App\Models\Utility;

use GuzzleHttp\Client;
use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;

class Downloader
{

	private $cache;

	public function __construct(string $tempDir)
	{
		ini_set("user_agent", "Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405");
		$storage = new FileStorage($tempDir);
		$this->cache = new Cache($storage);
	}

	public function get(string $url): string
	{
		$content = $this->cache->load($url);
		if ($content !== null) {
			return $content;
		}

		$client = new Client();
		$response = $client->request('GET', $url);

		$content = $response->getBody()->getContents();
		$this->cache->save($url, $content);

		return $content;
	}
}
