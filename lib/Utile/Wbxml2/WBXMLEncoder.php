<?php
namespace OCA\EAS\Utile\Wbxml;

use DOMCdataSection;
use DOMDocument;
use DOMNode;

class WBXMLEncoder{

	private $codepages;

	/**
	 * WBXMLDecoder constructor.
	 * @param WBXMLCodePage[] $codepages
	 */
	public function __construct(array $codepages){
		$this->codepages = $codepages;
	}

	/**
	 * @param string $input
	 * @param int $version
	 * @return string|null
	 * @throws WBXMLException
	 */
	public function encode(string $input,$version=0x03): ?string{
		$wbxml = new WBXML;
		$wbxml->setVersion($version);
		$wbxml->setPublicId(0x01);
		$wbxml->setIsIndex(false);
		$wbxml->setCharset(0x6A);
		$wbxml->setStringTable([]);

		$xml = new DOMDocument();
		if(@!$xml->loadXML($input)){
			throw new WBXMLException('Invalid XML');
		}
		$arr = $this->xmlToArray($xml->firstChild,$this->codepages);

		$wbxml->setBody($arr);

		return $wbxml->serialize();
	}

	/**
	 * @param $stream
	 * @return string|null
	 * @throws WBXMLException
	 */
	public function encodeStream($stream): ?string{
		return $this->encode(stream_get_contents($stream));
	}

	private $page = 0;

	private function xmlToArray(DOMNode $node,$codepages): array{
		$arr = [];

		if($node->hasChildNodes()){
			$tag = $this->getTagId($node,$codepages);
			if($this->page!==$tag[0]){
				$this->page = $tag[0];
				$arr[] = [WBXML::SWITCH_PAGE,$this->page];
			}
			$arr[] = [null,$tag[1],'OPEN'];
//			if($node->hasAttributes()){
//				//TODO Attributes
//			}
			foreach($node->childNodes AS $childNode){
				$addArr = $this->xmlToArray($childNode,$codepages);
				foreach($addArr AS $add){
					$arr[] = $add;
				}
			}
			$arr[] = [WBXML::END];
		}else{
			if($node->nodeType===XML_CDATA_SECTION_NODE){
				/**@var DOMCdataSection $node*/
				$str = trim($node->data);
				if($str!==''){
					$arr[] = [WBXML::OPAQUE,$node->data];
				}
			}
			if($node->nodeType===XML_TEXT_NODE){
				$str = trim($node->nodeValue);
				if($str!==''){
					$arr[] = [WBXML::STR_I,$node->nodeValue];
				}
			}else{
				$tag = $this->getTagId($node,$codepages);
				if($this->page!==$tag[0]){
					$this->page = $tag[0];
					$arr[] = [WBXML::SWITCH_PAGE,$this->page];
				}
				$arr[] = [null,$tag[1],'SELF'];
//				if($node->hasAttributes()){
//					//TODO Attributes
//				}
			}
		}

		return $arr;
	}

	/**
	 * @param DOMNode $node
	 * @param WBXMLCodePage[] $codepages
	 * @return array
	 */
	private function getTagId(DOMNode $node,$codepages): array{
		$namespace = $node->namespaceURI;
		$localname = $node->localName;

		foreach($codepages AS $codePage){
			if($codePage->getNamespace()===$namespace){
				foreach($codePage->getCodes() AS $key=>$code){
					if($code===$localname){
						$returnKey = $key;
						if($node->hasAttributes()){
							$returnKey |= 0x80;
						}
						if($node->hasChildNodes()){
							$returnKey |= 0x40;
						}
						return [$codePage->getNumber(),$returnKey];
					}
				}
			}
		}

		return [-1,-1];
	}

}