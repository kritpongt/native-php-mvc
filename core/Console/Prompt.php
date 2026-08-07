<?php
declare(strict_types=1);

namespace Core\Console;

// tiny CLI I/O helper - reusable by any console command
final class Prompt
{
	/** visible line input */
	public static function ask(string $label): string
	{
		echo $label;

		$line = fgets(STDIN);

		return $line === false ? '' : trim($line);
	}

	/**
	 * Hidden input - typed chars never echo, never hit shell history.
	 * Windows: PowerShell SecureString. POSIX: stty turns echo off.
	 */
	public static function secret(string $label): string
	{
		echo $label;

		// Windows has no stty - drive PowerShell to read silently
		if(str_starts_with(strtoupper(PHP_OS), 'WIN')){
			$cmd = 'powershell -NoProfile -Command "$p = Read-Host -AsSecureString; '
				.'[Runtime.InteropServices.Marshal]::PtrToStringAuto('
				.'[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
		
			$value = shell_exec($cmd);
			echo PHP_EOL;

			return $value === null ? '' : trim($value);
		}

		// POSIX: remember tty state, kill echo, read, restore
		$before = shell_exec('stty -g');
		shell_exec('stty -echo');

		$line = fgets(STDIN);

		shell_exec('stty '.$before);
		echo PHP_EOL;

		return $line === false ? '' : trim($line);
	}
}