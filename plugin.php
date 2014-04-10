<?php
class PulonairTest extends KokenPlugin {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->require_setup = true;
	}


	public function set_data($data) {
		parent::set_data($data);
		if ($this->isFrontend() && $this->isSitemapUrl()) {

			$urlset  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset ' .
				'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
				'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" />' .
				'<!--?xml version="1.0" encoding="UTF-8"?-->');

			$albums = Koken::api('/albums');
			foreach ($albums['albums'] as $album) {
				$url = $this->addUrlChild($urlset, $album);
				$albumImages = Koken::api('/albums/'. $album['id'] . '/content');
				foreach ($albumImages['content'] as $albumImage) {
					$this->addUrlChild($urlset, $albumImage);
					$this->addImageChild($url,$albumImage);
				}
			}

/*
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
			header("Content-type: text/xml; charset=utf-8");
			$dom = new DomDocument();
			$dom->loadXML($urlset->asXML());
			$dom->formatOutput = true;
			echo $dom->saveXML();
		}

	}

	/**
	 * @param $parent
	 * @param $item
	 * @param string $changeFreq
	 * @param string $priority
	 * @return mixed
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
	 * @param $parent
	 * @param $item
	 * @param string $preset
	 * @return mixed
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