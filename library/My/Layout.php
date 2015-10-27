<?php
/**
 * Layout helper class.
 */
class My_Layout
{
	/**
	 * Renders favicon header data.
	 *
	 * @param	Zend_View $view
	 * @return	void
	 */
	public static function renderFavicon(Zend_View $view)
	{
		$view->doctype('XHTML1_RDFA');
		$view->headMeta()
			->setName('msapplication-TileColor', '#da532c')
			->setName('msapplication-config', $view->baseUrl('/www/images/favicon/browserconfig.xml'))
			->setName('theme-color', '#ffffff');

		$view->headLink()
			->headLink(array(
				'rel' => 'icon',
				'href' => self::assetUrl('www/images/favicon/favicon.ico', $view)
			), 'PREPEND')
			->headLink(array(
				'rel' => 'manifest',
				'href' => $view->baseUrl('/www/images/favicon/manifest.json')
			), 'PREPEND')
			->headLink(array(
				'rel' => 'shortcut icon',
				'href' => self::assetUrl('www/images/favicon/favicon-16x16.png', $view),
				'type' => 'image/png',
				'sizes' => '16x16'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'shortcut icon',
				'href' => self::assetUrl('www/images/favicon/favicon-32x32.png', $view),
				'type' => 'image/png',
				'sizes' => '32x32'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => self::assetUrl('www/images/favicon/favicon-72x72.png', $view),
				'sizes' => '72x72'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => self::assetUrl('www/images/favicon/favicon-60x60.png', $view),
				'sizes' => '60x60'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => self::assetUrl('www/images/favicon/favicon-57x57.png', $view),
				'sizes' => '57x57'
			), 'PREPEND');
	}

	/**
	 * Returns media asset url.
	 *
	 * @param	string $url
	 * @param	Zend_View $view
	 * @reutrn	string
	 */
	public static function assetUrl($url, Zend_View $view)
	{
		$config = Zend_Registry::get('config_global');

		if (trim($config->mediaversion) !== '')
		{
			$url = 'assets-' . $config->mediaversion . '/' . $url;
		}

		return $view->baseUrl($url);
	}

	/**
	 * Defer load css files.
	 *
	 * @param	string $href
	 * @reutrn	string
	 */
	public static function deferLoadCss($href)
	{
		return 'var cb=function(){var e=document.createElement("link");' .
			'e.rel="stylesheet",' .
			'e.href="' . $href . '";' .
			'var a=document.getElementsByTagName("head")[0];' .
			'a.appendChild(e)},' .
			'raf=requestAnimationFrame||mozRequestAnimationFrame||' .
			'webkitRequestAnimationFrame||msRequestAnimationFrame;' .
			'raf?raf(cb):window.addEventListener("load",cb);';
	}

	/**
	 * Async load javascript file.
	 *
	 * @param	string $src
	 * @param	Zend_View $view
	 * @reutrn	string
	 */
	public static function appendAsyncScript($src, $view)
	{
		if (!isset($view->asyncScripts))
		{
			$view->asyncScripts = array();
		}

		$view->asyncScripts[$src] = <<<JS
(function(){
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.async = true;
	script.src = '{$src}';
	(document.getElementsByTagName('head')[0]||document.getElementsByTagName('body')[0]).appendChild(script);
})();
JS;
	}

	/**
	 * Returns async javascript code.
	 *
	 * @param	Zend_View $view
	 * @reutrn	string
	 */
	public static function asyncScript($view)
	{
		if (isset($view->asyncScripts))
		{
			return '<script type="text/javascript" async="async">' .
				implode($view->asyncScripts) . '</script>';
		}
	}
}
