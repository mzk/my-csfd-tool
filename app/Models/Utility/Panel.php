<?php declare(strict_types = 1);

namespace App\Models\Utility;

use Doctrine;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Nette;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Tracy\Bar;
use Tracy\Debugger;
use Tracy\Dumper;
use Tracy\Helpers;
use Tracy\IBarPanel;

/**
 * Debug panel for Doctrine
 *
 * @author David Grudl
 * @author Patrik Votoček
 * @author Filip Procházka <filip@prochazka.su>
 * @author Olda Salek <https://github.com/mzk, https://mozektevidi.net>
 */
class Panel implements IBarPanel, Doctrine\DBAL\Logging\SQLLogger
{

	use Nette\SmartObject;

	/**
	 * @var float logged time
	 */
	public $totalTime;

	/**
	 * @var array<int, mixed>
	 */
	public $queries = [];

	/**
	 * @var string[]
	 */
	public $failed = [];

	/**
	 * @var string[]
	 */
	public $skipPaths = [
		'vendor/nette/',
		'src/Nette/',
		'vendor/doctrine/collections/',
		'lib/Doctrine/Collections/',
		'vendor/doctrine/common/',
		'lib/Doctrine/Common/',
		'vendor/doctrine/dbal/',
		'lib/Doctrine/DBAL/',
		'vendor/doctrine/orm/',
		'lib/Doctrine/ORM/',
		'vendor/phpunit',
	];

	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @param string|mixed $sql
	 * @param mixed[]|null $params
	 * @param string[]|null $types
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null): void
	{
		Debugger::timer('doctrine');

		$source = null;
		foreach (\debug_backtrace() as $row) {
			if (isset($row['file']) && $this->filterTracePaths(\realpath($row['file']))) {
				if (isset($row['class']) && \stripos($row['class'], '\\' . Proxy::MARKER) !== false) {
					if (!\in_array(Doctrine\Common\Persistence\Proxy::class, \class_implements($row['class']), true)) {
						continue;
					}

					if (isset($row['function']) && $row['function'] === '__load') {
						continue;
					}
				} elseif (\stripos($row['file'], \DIRECTORY_SEPARATOR . Proxy::MARKER) !== false) {
					continue;
				}

				$source = [
					$row['file'],
					(int)$row['line'],
				];
				break;
			}
		}

		$this->queries[] = [
			$sql,
			$params,
			null,
			$types,
			$source,
		];
	}

	private function filterTracePaths(string $file): bool
	{
		$file = \str_replace(\DIRECTORY_SEPARATOR, '/', $file);
		$return = \is_file($file);
		foreach ($this->skipPaths as $path) {
			if (!$return) {
				break;
			}
			$return = $return && \strpos($file, '/' . \trim($path, '/') . '/') === false;
		}

		return $return;
	}

	public function stopQuery(): void
	{
		$keys = \array_keys($this->queries);
		$key = \end($keys);
		$time = Debugger::timer('doctrine');
		$this->queries[$key][2] = $time;
		$this->totalTime += $time;
	}

	/**
	 * @return string
	 */
	public function getTab(): ?string
	{
		return '<span title="Doctrine 2">'
			. '<svg viewBox="0 0 2048 2048"><path fill="#aaa" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"></path></svg>'
			. '<span class="tracy-label">'
			. \count($this->queries) . ' queries'
			. ($this->totalTime > 0 ? ' / ' . \sprintf('%0.1f', $this->totalTime * 1000) . ' ms' : '')
			. '</span>'
			. '</span>';
	}

	/**
	 * @return string
	 */
	public function getPanel(): string
	{
		if (\count($this->queries) < 1) {
			return '';
		}

		$connParams = $this->connection->getParams();
		if ($connParams['driver'] === 'pdo_sqlite' && isset($connParams['path'])) {
			$host = 'path: ' . \basename($connParams['path']);
		} else {
			$port = $this->connection->getPort();
			$host = \sprintf(
				'host: %s%s/%s',
				$this->connection->getHost(),
				($port !== null ? ':' . $port : ''),
				$this->connection->getDatabase()
			);
		}

		return $this->renderStyles() .
			\sprintf(
				'<h1>Queries: %s %s, %s</h1>',
				\count($this->queries),
				($this->totalTime > 0 ? ', time: ' . \sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : ''),
				$host
			) .
			'<div class="nette-inner tracy-inner nette-Doctrine2Panel">' .
			\implode('<br>', \array_filter([
				$this->renderPanelCacheStatistics(),
				$this->renderPanelQueries(),
			])) .
			'</div>';
	}

	private function renderPanelCacheStatistics(): string
	{
		if ($this->em === null) {
			return '';
		}

		$config = $this->em->getConfiguration();
		if (!$config->isSecondLevelCacheEnabled()) {
			return '';
		}

		$loggerChain = $config->getSecondLevelCacheConfiguration()
			->getCacheLogger();

		if (!$loggerChain instanceof Doctrine\ORM\Cache\Logging\CacheLoggerChain) {
			return '';
		}

		$statistics = $loggerChain->getLogger('statistics');
		if ($statistics === null) {
			return '';
		}

		return Dumper::toHtml($statistics, [Dumper::DEPTH => 5]);
	}

	private function renderPanelQueries(): string
	{
		if (\count($this->queries) < 1) {
			return '';
		}

		$s = '';
		foreach ($this->queries as $query) {
			$s .= $this->processQuery($query);
		}

		return '<table><tr><th>ms</th><th>SQL Statement</th></tr>' . $s . '</table>';
	}

	private function renderStyles(): string
	{
		return '<style>
			#nette-debug td.nette-Doctrine2Panel-sql { background: white !important}
			#nette-debug .nette-Doctrine2Panel-source { color: #BBB !important }
			#nette-debug nette-Doctrine2Panel tr table { margin: 8px 0; max-height: 150px; overflow:auto }
			#tracy-debug td.nette-Doctrine2Panel-sql { background: white !important}
			#tracy-debug .nette-Doctrine2Panel-source { color: #BBB !important }
			#tracy-debug nette-Doctrine2Panel tr table { margin: 8px 0; max-height: 150px; overflow:auto }
		</style>';
	}

	/**
	 * @param mixed[] $query
	 * @return string
	 */
	private function processQuery(array $query): string
	{
		$h = 'htmlspecialchars';
		[$sql, $params, $time, $types, $source] = $query;

		$s = self::highlightQuery(static::formatQuery($sql, (array)$params, (array)$types, $this->connection !== null ? $this->connection->getDatabasePlatform() : null));
		if ($source) {
			$s .= self::editorLink($source[0], $source[1], $h('.../' . \basename(\dirname($source[0]))) . '/<b>' . $h(\basename($source[0])) . '</b>');
		}

		return '<tr><td>' . \sprintf('%0.3f', $time * 1000) . '</td>' .
			'<td class = "nette-Doctrine2Panel-sql">' . $s . '</td></tr>';
	}

	/**
	 * Returns syntax highlighted SQL command.
	 * This method is same as Nette\Database\Helpers::dumpSql except for parameters handling.
	 *
	 * @link   https://github.com/nette/database/blob/667143b2d5b940f78c8dc9212f95b1bbc033c6a3/src/Database/Helpers.php#L75-L138
	 * @author David Grudl
	 */
	private static function highlightQuery(string $sql): string
	{
		static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|[RI]?LIKE|REGEXP|TRUE|FALSE|WITH|INSTANCE\s+OF';

		// insert new lines
		$sql = " $sql ";
		$sql = \preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

		// reduce spaces
		$sql = \preg_replace('#[ \t]{2,}#', ' ', $sql);

		$sql = \wordwrap($sql, 100);
		$sql = \preg_replace('#([ \t]*\r?\n){2,}#', "\n", $sql);

		// syntax highlight
		$sql = \htmlspecialchars($sql, \ENT_IGNORE, 'UTF-8');
		$sql = \preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", function ($matches) {
			if (isset($matches[1]) && $matches[1]) { // comment
				return '<em style="color:gray">' . $matches[1] . '</em>';
			}

			if (isset($matches[2]) && $matches[2]) { // error
				return '<strong style="color:red">' . $matches[2] . '</strong>';
			}

			if (isset($matches[3]) && $matches[3]) { // most important keywords
				return '<strong style="color:blue">' . $matches[3] . '</strong>';
			}

			if (isset($matches[4]) && $matches[4]) { // other keywords
				return '<strong style="color:green">' . $matches[4] . '</strong>';
			}
		}, $sql);

		return '<pre class="dump">' . \trim($sql) . "</pre>\n";
	}

	/**
	 * @param mixed[] $params
	 * @param string[] $types
	 * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
	 * @throws Nette\InvalidStateException
	 */
	private static function formatQuery(string $query, array $params, array $types = [], ?AbstractPlatform $platform = null): string
	{
		if ($platform === null) {
			$platform = new Doctrine\DBAL\Platforms\MySqlPlatform();
		}

		if (\count($types) > 0) {
			foreach ($params as $key => $param) {
				if (\is_array($param)) {
					$types[$key] = Connection::PARAM_STR_ARRAY;
				} else {
					$types[$key] = 'string';
				}
			}
		}

		try {
			[$query, $params, $types] = \Doctrine\DBAL\SQLParserUtils::expandListParameters($query, $params, $types);
		} catch (Doctrine\DBAL\SQLParserUtilsException $e) {
			$e->getMessage(); //ignore this exception
		}

		$formattedParams = [];
		foreach ($params as $key => $param) {
			if (isset($types[$key])) {
				if ($param instanceof \DateTime) {
					$types[$key] = Type::getType(Type::DATETIME);
				} elseif (\is_scalar($types[$key]) && \array_key_exists($types[$key], Type::getTypesMap())) {
					$types[$key] = Type::getType($types[$key]);
				}

				/** @var Type[] $types */
				if ($types[$key] instanceof Type) {
					$param = $types[$key]->convertToDatabaseValue($param, $platform);
				}
			}

			$formattedParams[] = $param;
		}
		$params = $formattedParams;

		if (Nette\Utils\Validators::isList($params)) {
			$parts = \explode('?', $query);
			if (\count($params) > $parts) {
				throw new Nette\InvalidStateException('Too mny parameters passed to query.');
			}

			return \implode('', self::zipper($parts, $params));
		}

		return Strings::replace($query, '~(\\:[a-z][a-z0-9]*|\\?[0-9]*)~i', function ($m) use (&$params) {
			if (\substr($m[0], 0, 1) === '?') {
				if (\strlen($m[0]) > 1) {
					$k = \substr($m[0], 1);
					if (isset($params[$k])) {
						return $params[$k];
					}
				} else {
					return \array_shift($params);
				}
			} else {
				$k = \substr($m[0], 1);
				if (isset($params[$k])) {
					return $params[$k];
				}
			}

			return $m[0];
		});
	}

	/**
	 * @param string[] $one
	 * @param string[] $two
	 * @return string[]
	 */
	public static function zipper(array $one, array $two): array
	{
		$output = [];
		while ($one && $two) {
			$output[] = \array_shift($one);
			$output[] = \array_shift($two);
		}

		return \array_merge($output, $one, $two);
	}

	/**
	 * Returns link to editor.
	 *
	 * @return Html
	 * @author David Grudl
	 */
	private static function editorLink(string $file, int $line, ?string $text = null): Html
	{
		if (Debugger::$editor && \is_file($file) && $text !== null) {
			return Html::el('a')
				->href(\strtr(Debugger::$editor, ['%file' => \rawurlencode($file), '%line' => $line]))
				->setAttribute('title', "$file:$line")
				->setHtml($text);
		}

		return Html::el()->setHtml(Helpers::editorLink($file, $line));
	}

	public function bindConnection(Connection $connection): Panel
	{
		if ($this->connection !== null) {
			throw new Nette\InvalidStateException('Doctrine Panel is already bound to connection.');
		}

		$this->connection = $connection;

		// Tracy
		$this->registerBarPanel(Debugger::getBar());

		return $this;
	}

	private function registerBarPanel(Bar $bar): void
	{
		$bar->addPanel($this);
	}
}
