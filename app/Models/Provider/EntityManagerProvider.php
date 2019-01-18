<?php declare(strict_types = 1);

namespace App\Models\Provider;

use App\Models\Utility\Panel;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;

class EntityManagerProvider
{

	/**
	 * @var EntityManager
	 */
	private $createdEntityManager;

	/**
	 * @var Panel
	 */
	private $panel;

	/**
	 * @var string
	 */
	private $redisHost;

	/**
	 * @param string[] $dbParams
	 * @param string[] $paths
	 */
	public function __construct(array $dbParams, array $paths, bool $isDevMode, string $proxyDir, string $redisHost, Panel $panel)
	{
		$this->panel = $panel;
		$this->redisHost = $redisHost;
		$this->createdEntityManager = $this->create($dbParams, $paths, $isDevMode, $proxyDir);
	}

	public function getMaster(): EntityManager
	{
		return $this->createdEntityManager;
	}

	public function getSlave(): EntityManager
	{
		return $this->createdEntityManager;
	}

	/**
	 * @param string[] $dbParams
	 * @param string[] $paths
	 * @throws \Doctrine\ORM\ORMException
	 */
	private function create(array $dbParams, array $paths, bool $isDevMode, string $proxyDir): EntityManager
	{
		$redis = new \Redis();
		$redis->connect($this->redisHost);
		$redis->select(1);
		$cache = new RedisCache();
		$cache->setRedis($redis);
		$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache, false);
		$config->setAutoGenerateProxyClasses(AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS);
		$config->setNamingStrategy(new UnderscoreNamingStrategy());
		$config->setQuoteStrategy(new DefaultQuoteStrategy());
		$config->setSQLLogger($this->panel);

		$entityManager = EntityManager::create($dbParams, $config);
		/** @var MasterSlaveConnection $connection */
		$connection = $entityManager->getConnection();
		$this->panel->bindConnection($connection);

		return $entityManager;
	}
}
