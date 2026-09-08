<?php
declare(strict_types=1);

namespace App\Utils;

final class NumberHelper
{
	public static function bahtText(float $amount): string
	{
		if ($amount === 0.0) { return 'ศูนย์บาทถ้วน'; }
			
		$amount = number_format($amount, 2, '.', '');
		[$integer, $fraction] = explode('.', $amount);
		
		$convert = function(string $number): string {
			$txtNumArr = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
			$txtDigitArr = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
			
			$number = ltrim($number, '0');
			if ($number === '') return '';
			
			$strlen = strlen($number);
			$res = '';
			for ($i = 0; $i < $strlen; $i++) {
				$n = (int)$number[$i];
				if ($n !== 0) {
					$p = ($strlen - $i - 1) % 6;
					$t = $txtNumArr[$n];

					if ($n === 1 && $p === 0 && $strlen > 1) {
						$t = 'เอ็ด';
					} elseif ($n === 2 && $p === 1) {
						$t = 'ยี่';
					} elseif ($n === 1 && $p === 1) {
						$t = '';
					}

					$res .= $t.$txtDigitArr[$p];
				}

				if (($strlen - $i - 1) % 6 === 0 && $i < $strlen - 1) {
					$res .= 'ล้าน';
				}
			}

			return $res;
		};

		$bahtText = $convert($integer);
		$satangText = $convert($fraction);

		$result = '';
		if ($bahtText !== '') {
			$result .= $bahtText . 'บาท';
		}
		if ($satangText !== '') {
			$result .= $satangText . 'สตางค์';
		} else {
			$result .= 'ถ้วน';
		}

		return $result === '' ? 'ศูนย์บาทถ้วน' : $result;
	}
}