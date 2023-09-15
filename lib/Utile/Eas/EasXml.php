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

namespace OCA\EAS\Utile\Eas;

class EasXml {

	// Implemented tokens by MS-ASWBXML
	public const VERSION_V10 = 0x00;
	public const VERSION_V11 = 0x01;
	public const VERSION_V12 = 0x02;
	public const VERSION_V13 = 0x03;
	public const IDENTIFIER = 0x01;			// Only public unknown identifier supported by MS-ASWBXML
	public const ENCODING = 0x6A; 			// Only UTF-8 is supported by MS-ASWBXML
	public const CODESPACE = 0x00;
	public const NODE_CONTENTS  = 0x40;
	public const NODE_ATTRIBUTES  = 0x80;
	public const NODE_END = 0x01;
	public const STRING_INLINE = 0x03;
	public const STRING_COMPLETION = 0x00;
	public const DATA = 0xC3;
	// Unimplemented tokens by MS-ASWBXML
	public const ENTITY = 0x02;
	public const LITERAL = 0x04;
	public const EXT_I_0 = 0x40;
	public const EXT_I_1 = 0x41;
	public const EXT_I_2 = 0x42;
	public const PI = 0x43;
	public const LITERAL_C = 0x44;
	public const EXT_T_0 = 0x80;
	public const EXT_T_1 = 0x81;
	public const EXT_T_2 = 0x82;
	public const STR_T = 0x83;
	public const LITERAL_A = 0x84;
	public const EXT_0 = 0xC0;
	public const EXT_1 = 0xc1;
	public const EXT_2 = 0xC2;
	public const LITERAL_AC = 0xC4;

}