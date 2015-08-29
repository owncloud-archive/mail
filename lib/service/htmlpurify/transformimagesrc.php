<?php

namespace OCA\Mail\Service\HtmlPurify;
use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use HTMLPurifier_URIFilter;
use HTMLPurifier_URIParser;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Adds copies src to data-src on all img tags.
 */
class TransformImageSrc extends HTMLPurifier_AttrTransform {
	/**
	* @type HTMLPurifier_URIParser
	*/
	private $parser;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IURLGenerator $urlGenerator) {
		$this->parser = new HTMLPurifier_URIParser();
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param array $attr
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return array
	 */
	public function transform($attr, $config, $context) {
		if ($context->get('CurrentToken')->name !== 'img' ||
			!isset($attr['src'])) {
			return $attr;
		}

		// Block tracking pixels
		if (isset($attr['width']) && isset($attr['height']) &&
			(int)$attr['width'] < 5 && (int)$attr['height'] < 5){
			$attr['src'] = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAABmJLR0QA/wD/AP+gvaeTAAAADUlEQVQI12NgYGBgAAAABQABXvMqOgAAAABJRU5ErkJggg==';
			return $attr;
		}

		// Do not block tracking pixels
		$url = $this->parser->parse($attr['src']);
		if ($url->host === Util::getServerHostName() && $url->path === $this->urlGenerator->linkToRoute('mail.proxy.proxy')) {
			$attr['data-original-src'] = $attr['src'];
			$attr['src'] = Util::imagePath('mail', 'blocked-image.png');
		}
		return $attr;
	}
}
