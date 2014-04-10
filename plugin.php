<?php
class PulonairXmlSitemapTest extends KokenPlugin {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->require_setup = true;
	}

	/**
	 * Sets the plugin data.
	 * We do highjack this function since it is the last opportuny hock in before we get redirected
	 * to the 404 error page for a non existing url. Hopefully the will be hook in future koken versions.
	 *
	 * @param array $data
	 * @return void
	 */
	public function set_data($data) {
		parent::set_data($data);
		if ($this->isFrontend() && $this->isSitemapUrl()) {

			$urlset  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset ' .
				'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
				'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" />' .
				'<!--?xml version="1.0" encoding="UTF-8"?-->');

			// Pages
			list($apiUrl)  = Koken::load(array('source' => 'pages'));
			$items = Koken::api($apiUrl);
			foreach ($items['text'] as $item) {
				$this->addUrlChild($urlset, $item);
			}

			// Essays
			list($apiUrl) = Koken::load(array('source' => 'essays'));
			$items = Koken::api($apiUrl);
			foreach ($items['text'] as $item) {
				$this->addUrlChild($urlset, $item);
			}

			// Albums
			list($apiUrl) = Koken::load(array('source' => 'albums'));
			$items = Koken::api($apiUrl);
			foreach ($items['albums'] as $item) {
				$url = $this->addUrlChild($urlset, $item);
				$itemImages = Koken::api('/albums/'. $item['id'] . '/content');
				foreach ($itemImages['content'] as $itemImage) {
					$this->addUrlChild($urlset, $itemImage);
					$this->addImageChild($url,$itemImage);
				}
			}

			/*
				var_dump($items);



				echo '<pre>';
				$albums = Koken::api('/albums');
				//var_dump($albums);
				//var_dump(Koken::api('/albums/1/content'));
				$dom = new DomDocument();
				$dom->loadXML($urlset->asXML());
				$dom->formatOutput = true;
				echo $dom->saveXML();
				//var_dump($urlset->asXML());

				echo '</pre>';
				exit;
			*/
			$dom = new DomDocument();
			$dom->loadXML($urlset->asXML());
			$dom->formatOutput = true;
			$content = $dom->saveXML();

			Koken::cache($content);

			header("Content-type: text/xml; charset=utf-8");
			echo $content;
		}

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
	protected function addUrlChild($parent, $item, $changeFreq = 'daily', $priority = '1.0') {
		$urlChild = $parent->addChild('url');
		$urlChild->addChild('loc', $item['url']);
		$urlChild->addChild('lastmod', date('Y-m-d', $item['modified_on']['timestamp']));
		$urlChild->addChild('changefreq', 'weekly');
		$urlChild->addChild('priority', '1.0');

		return $urlChild;
	}

	/**
	 * Adds an url image child to a given parent
	 *
	 * @param SimpleXMLElement $parent
	 * @param array $item
	 * @param string $preset
	 * @return SimpleXMLElement
	 */
	protected function addImageChild($parent, $item, $preset = 'large') {
		$imageChild = $parent->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
		$imageChild->addChild('image:loc', $item['presets'][$preset]['url'],
			'http://www.google.com/schemas/sitemap-image/1.1');
		$imageChild->addChild('image:title',$item['title'], 'http://www.google.com/schemas/sitemap-image/1.1');

		return $imageChild;
	}


	/**
	 * Checks if we are on frontend and not api or backend
	 *
	 * @return bool
	 */
	protected function isFrontend() {
		return !defined('ENVIRONMENT');
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