<?php
/**
 * Layout helper class.
 */
class My_Layout
{
	/**
	 * @var string
	 */
	protected static $pasePath = null;

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
				'href' => self::assetUrl('www/images/favicon/favicon.ico')
			), 'PREPEND')
			->headLink(array(
				'rel' => 'manifest',
				'href' => $view->baseUrl('/www/images/favicon/manifest.json')
			), 'PREPEND')
			->headLink(array(
				'rel' => 'shortcut icon',
				'href' => self::assetUrl('www/images/favicon/favicon-16x16.png'),
				'type' => 'image/png',
				'sizes' => '16x16'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'shortcut icon',
				'href' => self::assetUrl('www/images/favicon/favicon-32x32.png'),
				'type' => 'image/png',
				'sizes' => '32x32'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => self::assetUrl('www/images/favicon/apple-touch-icon-72x72.png'),
				'sizes' => '72x72'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => self::assetUrl('www/images/favicon/apple-touch-icon-60x60.png'),
				'sizes' => '60x60'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => self::assetUrl('www/images/favicon/apple-touch-icon-57x57.png'),
				'sizes' => '57x57'
			), 'PREPEND');
	}

	/**
	 * Returns media asset url.
	 *
	 * @param string $url
	 * @return string
	 */
	public static function assetUrl($url='')
	{
		if (self::$pasePath === null)
		{
			$config = Zend_Registry::get('config_global');

			self::$pasePath = $config->resources->frontController->baseUrl;

			if ($config->media->version !== '')
			{
				self::$pasePath .= 'assets-' . $config->media->version . '/';
			}
		}

		return self::$pasePath . $url;
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
			return '<script type="text/javascript">' .
				implode($view->asyncScripts) . '</script>';
		}
	}

	/**
	 * Async load stylesheet file.
	 *
	 * @param	string $href
	 * @param	Zend_View $view
	 * @reutrn	Zend_View_Helper_HeadScript
	 */
	public static function appendAsyncStylesheet($href, $view)
	{
		return $view->headScript()->appendScript(<<<JS
var cb = function() {
  var l = document.createElement('link'); l.rel = 'stylesheet';
  l.href = '{$href}';
  var h = document.getElementsByTagName('head')[0]; h.parentNode.insertBefore(l, h);
};
var raf = requestAnimationFrame || mozRequestAnimationFrame ||
  webkitRequestAnimationFrame || msRequestAnimationFrame;
if (raf) raf(cb);
else window.addEventListener('load', cb);
JS
		);
	}
}
