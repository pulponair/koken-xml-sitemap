<?php
class PulonairXmlSitemapTest extends KokenPlugin {
	const NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';
	const IMAGE_NS = 'http://www.google.com/schemas/sitemap-image/1.1';

	/**
	 * Constructor
	 */
	function __construct() {
		$this->require_setup = true;

		$this->register_hook('site.url', 'siteUrlHook');
	}

	/**
	 * Site url hook for newer koken versions
	 */
	public function siteUrlHook() {
		if ($this->isSitemapUrl()) {
			if ($cache = Shutter::get_cache($this->getCachePath())) {
				header('Content-type: text/xml; charset=utf-8');
				echo $cache['data'];
				exit;
			} else {
				Koken::$cache_path = $this->getCachePath();
				$xmlSitemap = $this->buildXmlSitemap();
				$this->outputXmlSitemapAndExit($xmlSitemap);

			}
		}
	}

	/**
	 * Gets the cache path
	 *
	 * @return string
	 */
	public function getCachePath() {
		return 'site/sitemap.xml/cache.html';
	}

	/**
	 * Sets the plugin data.
	 * We need to highjack this function since it is the last opportunity to hock in before we get redirected
	 * to the 404 error page for a non existing url. Hopefully there will be hook in future koken versions.
	 *
	 * This is needed for koken versions prior 0.20
	 *
	 * @param array $data
	 * @return void
	 */
	public function set_data($data) {
		parent::set_data($data);
		if ($this->isFrontend() && $this->isSitemapUrl()) {
			$xmlSitemap = $this->buildXmlSitemap();
			$this->outputXmlSitemapAndExit($xmlSitemap);
		}
	}

	/**
	 * Builds the xml sitemap
	 *
	 * @return SimpleXMLElement
	 */
	protected function buildXmlSitemap() {
		$urlset = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>' .
			'<urlset xmlns="' . self::NS . '" xmlns:image="' . self::IMAGE_NS . '" />');

		// Pages
		if ($this->data->exclude_pages !== TRUE) {
			list($apiUrl)  = Koken::load(array('source' => 'pages'));
			$items = Koken::api($apiUrl);

			foreach ($items['text'] as $item) {
				$pageUrl = $this->addUrlChild($urlset, $item);
				if ($images = $this->extractImagesFormText($item['content'])) {
					foreach ($images as $image) {
						$this->addImageChild($pageUrl, $image, $this->data->page_image_loc_preset);
					}
				}
			}
		}

		// Essays
		if ($this->data->exclude_essays !== TRUE) {
			list($apiUrl) = Koken::load(array('source' => 'essays', 'expand' => 1));
			$items = Koken::api($apiUrl);
			foreach ($items['text'] as $item) {
				$essayUrl  = $this->addUrlChild($urlset, $item);
				if ($images = $this->extractImagesFormText($item['content'])) {
					foreach ($images as $image) {
						$this->addImageChild($essayUrl, $image, $this->data->essay_image_loc_preset);
					}
				}
			}
		}

		// Albums
		if ($this->data->exclude_albums !== TRUE) {
			list($apiUrl) = Koken::load(array('source' => 'albums'));
			$items = Koken::api($apiUrl);
			foreach ($items['albums'] as $item) {
				$albumUrl = $this->addUrlChild($urlset, $item);
				$itemImages = Koken::api('/albums/' . $item['id'] . '/content');
				foreach ($itemImages['content'] as $itemImage) {
					$this->addImageChild($albumUrl, $itemImage, $this->data->album_image_loc_preset);
				}
			}
		}

		// Content
		if ($this->data->exclude_content !== TRUE) {
			list($apiUrl)  = Koken::load(array('source' => 'content'));
			$page = 0;
			do {
				$page++;
				$items = Koken::api($apiUrl . '/page:' . $page);
				foreach ($items['content'] as $item) {
					$imageDetailUrl = $this->addUrlChild($urlset, $item);
					$this->addImageChild($imageDetailUrl, $item, $this->data->detail_image_loc_preset);
				}
			} while ($page < $items['pages']);
		}

		return $urlset;
	}

	/**
	 * Outputs the xml sitemap
	 *
	 * @param SimpleXMLElement $xmlSitemap
	 * @return void
	 */
	protected function outputXmlSitemapAndExit(SimpleXMLElement $xmlSitemap){
		$content = $xmlSitemap->asXML();
		if ($this->data->beautify_xml === TRUE) {
			$dom = new DomDocument();
			$dom->loadXML($content);
			$dom->formatOutput = true;
			$content = $dom->saveXML();
		}

		if ($this->data->disable_caching !== TRUE) {
			Koken::cache($content);
		}

		header("Content-type: text/xml; charset=utf-8");
		echo $content;
		exit;
	}

	/**
	 * Adds an url xml child to a given parent
	 *
	 * @param SimpleXMLElement $parent
	 * @param array $item
	 * @param string $changeFreq
	 * @param string $priority
	 * @return SimpleXMLElement
	 */
	protected function addUrlChild(SimpleXMLElement $parent, $item, $changeFreq = 'weekly', $priority = '1.0') {
		$urlChild = $parent->addChild('url');
		$urlChild->addChild('loc', $item['url']);
		$urlChild->addChild('lastmod', date('Y-m-d', $item['modified_on']['timestamp']));
		$urlChild->addChild('changefreq', $changeFreq);
		$urlChild->addChild('priority', $priority);

		return $urlChild;
	}

	/**
	 * Adds an url image child to a given parent
	 *
	 * @param SimpleXMLElement $parent
	 * @param array $item
	 * @param string $preset;
	 * @return SimpleXMLElement
	 */
	protected function addImageChild(SimpleXMLElement $parent, $item, $preset) {
		$imageChild = $parent->addChild('image:image', null, self::IMAGE_NS);
		$imageChild->addChild('image:loc', $item['presets'][$preset]['url'], self::IMAGE_NS);
		$imageChild->addChild('image:title', $item['title'], self::IMAGE_NS);

		return $imageChild;
	}

	/**
	 * Extracts images from text
	 *
	 * @param $text
	 * @return array|bool|mixed|string
	 */
	protected function extractImagesFormText($text) {
		$images = FALSE;
		preg_match('/filter:id="([^"]*)"/', $text, $matches);

		if ($matches[1]) {
			list($apiUrl) = koken::load(array('source' => 'contents', 'filter:id'=> $matches[1]));
			$images = Koken::api($apiUrl);
			if ($images['content']) {
				$images = $images['content'];
			} else {
				$images = array($images);
			}
		}
		return $images;
	}

	/**
	 * Checks if we are on frontend and not api or backend
	 *
	 * @return bool
	 */
	protected function isFrontend() {
		// @todo Find a better way, this is just a workaround.
		return class_exists('Koken') && !defined('ENVIRONMENT');
	}

	/**
	 * Checks if we are on the sitemap url
	 *
	 * @return bool
	 */
	protected function isSitemapUrl() {
		return $this->data->sitemap_url === Koken::$location['here'];
	}

}