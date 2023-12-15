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

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

/**
 * @method getid(): int
 * @method getuid(): string
 * @method setuid(string $uid): void
 * @method gettype(): string
 * @method settype(string $type): void
 * @method getaid(): string
 * @method setaid(string $aid): void
 * @method getloid(): string
 * @method setloid(string $loid): void
 * @method getlosignature(): string
 * @method setlosignature(string $token): void
 * @method getlcid(): string
 * @method setlcid(string $lcid): void
 * @method getroid(): string
 * @method setroid(string $roid): void
 * @method getrosignature(): string
 * @method setrosignature(string $token): void
 * @method getrcid(): string
 * @method setrcid(string $rcid): void
 * @method gethlock(): int
 * @method sethlock(int $status): void
 * @method gethlockhd(): int
 * @method sethlockhd(int $id): void
 * @method gethlockhb(): int
 * @method sethlockhb(int $timestamp): void
 * @method gethaltered(): int
 * @method sethaltered(int $timestamp): void
 * @method gethperformed(): int
 * @method sethperformed(int $timestamp): void
 */
class Correlation extends Entity implements JsonSerializable {
	protected string $uid = '';
	protected string $type = '';
	protected ?int $aid = null;
	protected string $lid = '';
	protected ?string $lpid = null;
	protected string $loid = '';
	protected ?string $lcid = null;
	protected ?string $losignature = null;
	protected string $rid = '';
	protected ?string $rpid = null;
	protected string $roid = '';
	protected ?string $rcid = null;
	protected ?string $rosignature = null;
	protected int $hlock = 0;
	protected int $hlockhd = 0;
	protected int $hlockhb = 0;
	protected ?int $haltered = null;
	protected ?int $hperformed = null;
		
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'type' => $this->type,
			'aid' => $this->aid,
			'loid' => $this->loid,
			'lcid' => $this->lcid,
			'losignature' => $this->losignature,
			'roid' => $this->roid,
			'rcid' => $this->rcid,
			'rosignature' => $this->rosignature,
			'hlock' => $this->hlock,
			'hlockhd' => $this->hlockhd,
			'hlockhb' => $this->hlockhb,
			'haltered' => $this->haltered,
			'hperformed' => $this->hperformed,
		];
	}
}
