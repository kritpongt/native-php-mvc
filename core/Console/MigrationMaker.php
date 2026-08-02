<?php
declare(strict_types=1);

namespace Core\Console;

use RuntimeException;

// creates migration files - no Database dependency, pure filesystem
final class MigrationMaker
{
	public function __construct(private readonly string $migrationsPath){}

	/** @return string path of the created file */
	public function make(string $name): string
	{
		if(preg_match('/^[a-z][a-z0-9_]*$/', $name) !== 1){
			throw new RuntimeException(
				"Invalid migration name: '{$name}' (snake_case only, e.g. create_users_table)"
			);
		}

		foreach($this->existingFiles() as $file){
			if(preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.*)\.php$/', basename($file), $m) === 1 && $m[1] === $name){
				throw new RuntimeException('Migration already exists: '.basename($file)."\n");
			}
		}

		$file = sprintf('%s/%s_%06d_%s.php',
			$this->migrationsPath,
			date('Y_m_d'),
			$this->nextSequence(),
			$name
		);

		if(file_put_contents($file, $this->emptyTemplate()) === false){
			throw new RuntimeException("Cannot write file: {$file}");
		}

		return $file;
	}

	/** @return list<string> */
	private function existingFiles(): array
	{
		return glob($this->migrationsPath.'/*.php') ?: [];
	}

	/** max sequence among today's files, plus one */
	private function nextSequence(): int
	{
		$today = date('Y_m_d');
		$max = 0;

		foreach($this->existingFiles() as $file){
			if(preg_match('/^'.$today.'_(\d{6})_/', basename($file), $m) === 1){
				$max = max($max, (int) $m[1]);
			}
		}
	
		return $max + 1;
	}

	private function emptyTemplate(): string
	{
		return <<<'PHP'
		<?php
		declare(strict_types=1);

		use Core\Database;

		return new class{
			public function up(Database $db): void
			{
				$db->raw(match($db->driver()){
					'mysql' => '',
					'pgsql' => ''
				});
			}

			public function down(Database $db): void
			{
				$db->raw('');
			}
		};

		PHP;
	}
}