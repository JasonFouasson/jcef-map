<?php
/**
 * GitHub-based plugin updates via Plugin Update Checker.
 *
 * @package WP_Interactive_Map_JCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JCE_Map_Updater
 */
class JCE_Map_Updater {

	const GITHUB_REPO = 'https://github.com/JasonFouasson/jcef-map/';
	const SLUG        = 'wp-interactive-map-jce';

	/**
	 * Bootstrap the update checker.
	 */
	public static function init() {
		$autoload = JCE_MAP_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
		if ( ! file_exists( $autoload ) ) {
			return;
		}

		require_once $autoload;

		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		$checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			self::GITHUB_REPO,
			JCE_MAP_FILE,
			self::SLUG
		);

		// Prefer GitHub Releases (tag = version). Pre-releases are ignored by PUC.
		$api = $checker->getVcsApi();
		if ( $api && method_exists( $api, 'enableReleaseAssets' ) ) {
			// Prefer a release ZIP asset; fall back to GitHub source archive.
			$api->enableReleaseAssets();
		}
	}
}
