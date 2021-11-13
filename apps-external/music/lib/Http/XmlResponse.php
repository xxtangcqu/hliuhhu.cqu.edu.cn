<?php declare(strict_types=1);

/**
 * ownCloud - Music app
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Pauli Järvinen <pauli.jarvinen@gmail.com>
 * @copyright Pauli Järvinen 2019, 2020
 */

namespace OCA\Music\Http;

use OCP\AppFramework\Http\Response;

/**
 * This class creates an XML response out of a passed in associative array,
 * similarly how the class JSONResponse works. The content is described with
 * a recursive array structure, where arrays may have string or integer keys.
 * One array should not mix string and integer keys, that will lead to undefined
 * outcome. Furthermore, array with integer keys is supported only as payload of
 * an array with string keys.
 *
 * Note that this response type has been created to fulfill the needs of the
 * SubsonicController and may not be suitable for all other purposes.
 */
class XmlResponse extends Response {
	private $content;
	private $doc;
	private $attributeKeys;

	/**
	 * @param array $content
	 * @param bool|string[] $attributes If true, then key-value pair is made into attribute if possible.
	 *                                  If false, then key-value pairs are never made into attributes.
	 *                                  If an array, then keys found from the array are made into attributes if possible.
	 */
	public function __construct(array $content, $attributes=true) {
		$this->addHeader('Content-Type', 'application/xml');

		// The content must have exactly one root element, add one if necessary
		if (\count($content) != 1) {
			$content = ['root' => $content];
		}
		$this->content = $content;
		$this->doc = new \DOMDocument('1.0', 'UTF-8');
		$this->doc->formatOutput = true;
		$this->attributeKeys = $attributes;
	}

	public function render() {
		$rootName = \array_keys($this->content)[0];
		$this->addChildElement($this->doc, $rootName, $this->content[$rootName]);
		return $this->doc->saveXML();
	}

	private function addChildElement($parentNode, $key, $value, $allowAttribute=true) {
		if (\is_bool($value)) {
			$value = $value ? 'true' : 'false';
		} elseif (\is_numeric($value)) {
			$value = (string)$value;
		}

		if (\is_string($value)) {
			// key 'value' has a special meaning
			if ($key == 'value') {
				$parentNode->appendChild($this->doc->createTextNode($value));
			} elseif ($allowAttribute && $this->keyMayDefineAttribute($key)) {
				$parentNode->setAttribute($key, $value);
			} else {
				$child = $this->doc->createElement($key);
				$child->appendChild($this->doc->createTextNode($value));
				$parentNode->appendChild($child);
			}
		} elseif (\is_array($value)) {
			if (self::arrayIsIndexed($value)) {
				foreach ($value as $child) {
					$this->addChildElement($parentNode, $key, $child, /*allowAttribute=*/false);
				}
			} else { // associative array
				$element = $this->doc->createElement($key);
				$parentNode->appendChild($element);
				foreach ($value as $childKey => $childValue) {
					$this->addChildElement($element, $childKey, $childValue);
				}
			}
		} elseif ($value instanceof \stdClass) {
			// empty element
			$element = $this->doc->createElement($key);
			$parentNode->appendChild($element);
		} elseif ($value === null) {
			// skip
		} else {
			throw new \Exception("Unexpected value type for key $key");
		}
	}

	private function keyMayDefineAttribute($key) {
		if (\is_array($this->attributeKeys)) {
			return \in_array($key, $this->attributeKeys);
		} else {
			return \boolval($this->attributeKeys);
		}
	}

	/**
	 * Array is considered to be "indexed" if its first element has numerical key.
	 * Empty array is considered to be "indexed".
	 * @param array $array
	 */
	private static function arrayIsIndexed(array $array) {
		\reset($array);
		return empty($array) || \is_int(\key($array));
	}
}
