<?php
namespace nzedb;

use nzedb\db\Settings;
use nzedb\utility\Utility;

/**
 * Class TraktTv
 * Lookup information from trakt.tv using their API.
 */
class TraktTv
{
	/**
	 * The Trakt.tv API v2 Client ID (SHA256 hash - 64 characters long string). Used for movie and tv lookups.
	 * Create one here: https://trakt.tv/oauth/applications/new
	 * @var array|bool|string
	 */
	private $clientID;

	/**
	 * List of headers to send to Trakt.tv when making a request.
	 * @see http://docs.trakt.apiary.io/#introduction/required-headers
	 * @var array
	 */
	private $requestHeaders;

	/**
	 * Construct. Set up API key.
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
		];
		$options += $defaults;

		$settings = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->clientID = $settings->getSetting('trakttvclientkey');
		$this->requestHeaders = [
			'Content-Type: application/json',
			'trakt-api-version: 2',
			'trakt-api-key: ' . $this->clientID
		];
	}

	/**
	 * Fetches summary from trakt.tv for the TV show using the title/season/episode.
	 *
	 * @param string $title
	 * @param string $season
	 * @param string $ep
	 *
	 * @see http://docs.trakt.apiary.io/#reference/episodes/summary/get-a-single-episode-for-a-show
	 *
	 * @return bool|mixed
	 *
	 * @access public
	 */
	public function episodeSummary($title = '', $season = '', $ep = '')
	{
		if (!empty($this->clientID)) {
			$json = Utility::getUrl([
					'url' =>
						'https://api-v2launch.trakt.tv/shows/' .
						str_replace([' ', '_', '.'], '-', $title) .
						'/seasons/' .
						str_replace(['S', 's'], '', $season) .
						'/episodes/' .
						str_replace(['E', 'e'], '', $ep),
					'requestheaders' => $this->requestHeaders
				]
			);
			if ($json !== false) {
				return json_decode($json, true);
			}
		}
		return false;
	}

	/**
	 * Fetches summary from trakt.tv for the movie.
	 * Accept a title (the-big-lebowski-1998), a IMDB id, or a TMDB id.
	 *
	 * @param string $movie Title or IMDB id.
	 * @param bool $imdbID  Return only the IMDB id ?
	 *
	 * @see http://docs.trakt.apiary.io/#reference/movies/summary/get-a-movie
	 *
	 * @return bool|mixed
	 *
	 * @access public
	 */
	public function movieSummary($movie = '', $imdbID = false)
	{
		if (!empty($this->clientID)) {
			$json = Utility::getUrl([
					'url' =>
						'https://api-v2launch.trakt.tv/movies/' .
						str_replace([' ', '_', '.'], '-', str_replace(['(', ')'], '', $movie)),
					'requestheaders' => $this->requestHeaders
				]
			);
			if ($json !== false) {
				$json = json_decode($json, true);
				if (isset($json['status']) && $json['status'] === 'failure') {
					return false;
				} else if ($imdbID && isset($json["imdb_id"])) {
					return $json["imdb_id"];
				}
				return $json;
			}
		}
		return false;
	}
}
