<?php
declare(strict_types=1);

namespace Core;

final class Logger implements LoggerInterface
{
	// higher number = more severe. compare against the threshold below.
	private const LEVELS = ['INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];

	private readonly int $minLevel;

	// scalars in constructor -> must be bound explicitly in bootstrap,
	// container cannot autowire these
	public function __construct(
		private readonly string $dir,
		private readonly int $retentionDays,
		string $minLevel = 'WARNING'
	){
		$this->minLevel = self::LEVELS[strtoupper($minLevel)] ?? self::LEVELS['INFO'];
	}

	/** @param array<string, mixed> $context */
	public function error(string $message, array $context = []): void
	{
		$this->log('ERROR', $message, $context);
	}

	/** @param array<string, mixed> $context */
	public function warning(string $message, array $context = []): void
	{
		$this->log('WARNING', $message, $context);
	}

	/** @param array<string, mixed> $context */
	public function info(string $message, array $context = []): void
	{
		$this->log('INFO', $message, $context);
	}

	/** @param array<string, mixed> $context */
	private function log(string $level, string $message, array $context): void
	{
		// gate: drop anything less severe than the threshold
		if((self::LEVELS[$level] ?? 0) < $this->minLevel){ return; }

		// one file per day - project rule, no OS logrotate
		$file = $this->dir.'/app-'.date('Y-m-d').'.log';

		// first write of a new day -> purge old files, runs once daily
		if(!is_file($file)){ $this->purgeOldFiles(); }

		$line = sprintf(
			"[%s] %s: %s%s\n",
			date('Y-m-d H:i:s'),
			$level,
			$message,
			$context === [] ? '' : ' '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
		);

		// LOCK_EX: parallel requests must not interleave half-lines.
		// @ on purpose: a full disk must never crash the app on top of the log
		@file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
	}

	private function purgeOldFiles(): void
	{
		$cutoff = time() - $this->retentionDays * 86400;

		foreach(glob($this->dir.'/app-*.log') ?: [] as $old){
			if((@filemtime($old) ?: 0) < $cutoff){ @unlink($old); }
		}
	}
}