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
				'href' => $view->baseUrl('/www/images/favicon/favicon.ico')
			), 'PREPEND')
			->headLink(array(
				'rel' => 'manifest',
				'href' => $view->baseUrl('/www/images/favicon/manifest.json')
			), 'PREPEND')
			->headLink(array(
				'rel' => 'shortcut icon',
				'href' => $view->baseUrl('/www/images/favicon/favicon-16x16.png'),
				'type' => 'image/png',
				'sizes' => '16x16'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'shortcut icon',
				'href' => $view->baseUrl('/www/images/favicon/favicon-32x32.png'),
				'type' => 'image/png',
				'sizes' => '32x32'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => $view->baseUrl('/www/images/favicon/favicon-72x72.png'),
				'sizes' => '72x72'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => $view->baseUrl('/www/images/favicon/favicon-60x60.png'),
				'sizes' => '60x60'
			), 'PREPEND')
			->headLink(array(
				'rel' => 'apple-touch-icon',
				'href' => $view->baseUrl('/www/images/favicon/favicon-57x57.png'),
				'sizes' => '57x57'
			), 'PREPEND');
	}
}
