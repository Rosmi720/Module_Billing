<?php

namespace XcVm\Module\Billing;

use ReflectionClass;
use XcVm\Core\Localization\Translator;

/**
 * BillingTranslator — Localization loader and bridge for the Billing Module.
 *
 * Provides complete translation management for all 8 supported languages:
 * English (en), Arabic (ar), Russian (ru), German (de), French (fr),
 * Spanish (es), Portuguese (pt), Bulgarian (bg).
 *
 * Bridges seamlessly with XcVm\Core\Localization\Translator without altering
 * any core panel files, injecting module dictionaries dynamically so that:
 * 1. $language::get('billing_...') resolves across all views, headers, and navbars.
 * 2. Unconfigured keys fall back cleanly to English.
 * 3. Module keys appear in the Admin Translations editor (Settings > Translations).
 *
 * @package XC_VM_Module_Billing
 */
class BillingTranslator {

	private static bool $initialized = false;
	private static ?string $injectedLang = null;
	/** @var array<string, string> */
	private static array $cachedTranslations = [];
	private static string $langDir = __DIR__ . '/lang/';

	/**
	 * Initialize and bridge module translations into the runtime Translator.
	 */
	public static function init(): void {
		$currentLang = class_exists(Translator::class) ? Translator::current() : 'en';

		if (self::$initialized && self::$injectedLang === $currentLang) {
			return;
		}

		$enData = self::loadFile('en');
		$currentData = ($currentLang !== 'en') ? self::loadFile($currentLang) : $enData;

		// Fallback missing keys in the current language to English
		$mergedCurrent = array_merge($enData, $currentData);
		self::$cachedTranslations = $mergedCurrent;

		// Bridge dynamically into core Translator
		if (class_exists(Translator::class)) {
			try {
				$ref = new ReflectionClass(Translator::class);

				// Inject current translations
				if ($ref->hasProperty('translations')) {
					$propTrans = $ref->getProperty('translations');
					$propTrans->setAccessible(true);
					$existingTrans = $propTrans->getValue() ?: [];
					// Core keys take precedence if identical key exists, but module keys are prefixed
					$propTrans->setValue(null, array_merge($mergedCurrent, $existingTrans));
				}

				// Inject English dictionary for fallback & Translations page
				if ($ref->hasProperty('enTranslations')) {
					$propEn = $ref->getProperty('enTranslations');
					$propEn->setAccessible(true);
					$existingEn = $propEn->getValue();
					if ($existingEn === null) {
						$existingEn = Translator::englishTranslations();
					}
					$propEn->setValue(null, array_merge($enData, $existingEn));
				}
			} catch (\Throwable $e) {
				error_log('BillingTranslator: failed to bridge into core Translator: ' . $e->getMessage());
			}
		}

		self::$initialized = true;
		self::$injectedLang = $currentLang;
	}

	/**
	 * Read and parse a language .ini file from the module's lang directory.
	 *
	 * @param string $lang Two-letter language code (e.g. 'en', 'ar')
	 * @return array<string, string>
	 */
	public static function loadFile(string $lang): array {
		$file = self::$langDir . $lang . '.ini';
		if (is_file($file) && is_readable($file)) {
			$data = @parse_ini_file($file, false, INI_SCANNER_RAW);
			if (is_array($data)) {
				return $data;
			}
		}
		return [];
	}

	/**
	 * Retrieve a translated string by key with optional replacements.
	 *
	 * @param string $key Translation key (e.g. 'billing_common_orders')
	 * @param array<string, string> $replace Substitutions for strtr()
	 * @return string
	 */
	public static function get(string $key, array $replace = []): string {
		if (!self::$initialized) {
			self::init();
		}

		if (class_exists(Translator::class)) {
			return Translator::get($key, $replace);
		}

		$text = self::$cachedTranslations[$key] ?? $key;
		return $replace !== [] ? strtr($text, $replace) : $text;
	}

	/**
	 * @return array<string, string> Currently cached translations
	 */
	public static function all(): array {
		if (!self::$initialized) {
			self::init();
		}
		return self::$cachedTranslations;
	}
}
