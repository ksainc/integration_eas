<?php
//declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\EAS\Store;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OC\DB\QueryBuilder\Literal;
use OCP\IDBConnection;

class CoreUtile {

	private IDBConnection $DataStore;

	public function __construct(IDBConnection $db) {
		$this->DataStore = $db;
	}

	/**
	 * retrieve correlations for specific user from the data store
	 * 
	 * @since Release 1.0.0
	 * 
	 * @return array
	 */
	public function listCorrelationsEstablished(string $uid, string $type) : array {

		// construct data store command
		$cmd = $this->DataStore->getQueryBuilder();
		$cmd->select('CRS.id', 'CRS.roid', 'CRS.type',  'CRS.hperformed', 'CLS.color AS color', new Literal('TRUE AS enabled'))
			->from('eas_correlations', 'CRS')
			->leftJoin('CRS', 'eas_collections', 'CLS', $cmd->expr()->eq('CRS.loid', 'CLS.id'))
			->where($cmd->expr()->eq('CRS.uid', $cmd->createNamedParameter($uid)))
			->andWhere($cmd->expr()->eq('CRS.type', $cmd->createNamedParameter($type)));
		// execute command
		$rs = $cmd->executeQuery()->fetchAll();
		$cmd->executeQuery()->closeCursor();
		// return result or null
		if (is_array($rs) && count($rs) > 0) {
			return $rs;
		}
		else {
			return [];
		}
		
	}

}
