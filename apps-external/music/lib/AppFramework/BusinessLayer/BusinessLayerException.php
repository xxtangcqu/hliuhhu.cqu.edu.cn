<?php declare(strict_types=1);
/**
 * ownCloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Alessandro Cosentino <cosenal@gmail.com>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright Alessandro Cosentino 2012
 * @copyright Bernhard Posselt 2012, 2014
 */

namespace OCA\Music\AppFramework\BusinessLayer;

class BusinessLayerException extends \Exception {

	/**
	 * Constructor
	 * @param string $msg the error message
	 */
	public function __construct(string $msg) {
		parent::__construct($msg);
	}
}
