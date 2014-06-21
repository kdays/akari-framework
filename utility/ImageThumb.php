<?php
Class ImageThumb{
	public static function GetImageSize($srcFile, $srcExt = NULL){
		empty($srcExt) && $srcExt = strtolower(substr(strrchr($srcFile,'.'),1));
		$srcdata = array();
		if (function_exists('read_exif_data') && in_array($srcExt,array('jpg','jpeg','jpe','jfif'))) {
			$datatemp = @read_exif_data($srcFile);
			$srcdata['width'] = $datatemp['COMPUTED']['Width'];
			$srcdata['height'] = $datatemp['COMPUTED']['Height'];
			$srcdata['type'] = 2;
			unset($datatemp);
		}
		!$srcdata['width'] && list($srcdata['width'],$srcdata['height'],$srcdata['type']) = @getimagesize($srcFile);
		if (empty($srcdata) || !$srcdata['type']) {
			return false;
		}
		return $srcdata;
	}

	public static function MakeThumb($srcFile, $dstFile, $dstW, $dstH, $cenTer = null, $sameFile = null, $fixWH = null){
		$minitemp = self::GetThumbInfo($srcFile,$dstW,$dstH,$cenTer);
		list($imagecreate,$imagecopyre) = self::GetImagecreate($minitemp['type']);

		if ((empty($sameFile) && $dstFile === $srcFile) || empty($minitemp) || !$imagecreate) return false;
		!empty($sameFile) && $dstFile = $srcFile;
		$imgwidth  = $minitemp['width'];
		$imgheight = $minitemp['height'];
		$srcX = $srcY = 0;
		if (!empty($cenTer)) {
			if ($imgwidth < $imgheight) {
				$srcY = round(($imgheight - $imgwidth)/2);
				$imgheight = $imgwidth;
			} else {
				$srcX = round(($imgwidth - $imgheight)/2);
				$imgwidth = $imgheight;
			}
		}
		$dstX = $dstY = 0;
		$thumb = $imagecreate($minitemp['dstW'],$minitemp['dstH']);
		$imagecopyre($thumb,$minitemp['source'],$dstX,$dstY,$srcX,$srcY,$minitemp['dstW'],$minitemp['dstH'],$imgwidth,$imgheight);
		self::MakeImage($minitemp['type'],$thumb,$dstFile);
		imagedestroy($thumb);
		return array($minitemp['dstW'],$minitemp['dstH']);
	}

	public static function GetImagecreate($imagetype){
		if ($imagetype!='gif' && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')) {
			return array('imagecreatetruecolor','imagecopyresampled');
		} elseif (function_exists('imagecreate') && function_exists('imagecopyresized')) {
			return array('imagecreate','imagecopyresized');
		} else {
			return array();
		}
	}

	public static function GetThumbInfo($srcFile,$dstW,$dstH,$cenTer = null){
		$imgdata = array();
		$imgdata = self::GetImgInfo($srcFile);
		if (empty($imgdata) || ($imgdata['width']<=$dstW && $imgdata['height']<=$dstH)) return false;
		if (empty($dstW) && $imgdata['height'] > $dstH) {
			if (!empty($cenTer)) {
				$imgdata['dstW'] = $imgdata['dstH'] = $dstH;
			} else {
				$imgdata['dstH'] = $dstH;
				$imgdata['dstW'] = round($dstH/$imgdata['height']*$imgdata['width']);
			}
		} elseif (empty($dstH) && $imgdata['width'] > $dstW) {
			if (!empty($cenTer)) {
				$imgdata['dstW'] = $imgdata['dstH'] = $dstW;
			} else {
				$imgdata['dstW'] = $dstW;
				$imgdata['dstH'] = round($dstW/$imgdata['width']*$imgdata['height']);
			}
		} elseif (!empty($dstW) && !empty($dstH)) {
			if (($imgdata['width']/$dstW) < ($imgdata['height']/$dstH)) {
				if (!empty($cenTer)) {
					$imgdata['dstW'] = $imgdata['dstH'] = $dstH;
				} else {
					$imgdata['dstW'] = round($dstH/$imgdata['height']*$imgdata['width']);
					$imgdata['dstH'] = $dstH;
				}
			} elseif (($imgdata['width']/$dstW) > ($imgdata['height']/$dstH)) {
				if (!empty($cenTer)) {
					$imgdata['dstW'] = $imgdata['dstH'] = $dstW;
				} else {
					$imgdata['dstW'] = $dstW;
					$imgdata['dstH'] = round($dstW/$imgdata['width']*$imgdata['height']);
				}
			} else {
				$imgdata['dstW'] = $dstW;
				$imgdata['dstH'] = $dstH;
			}
		} else {
			$imgdata['dstW'] = $imgdata['width'];
			$imgdata['dstH'] = $imgdata['height'];
		}
		return $imgdata;
	}

	public static function GetImgInfo($srcFile){
		$imgdata = (array)self::GetImageSize($srcFile);
		if ($imgdata['type']==1) {
			$imgdata['type'] = 'gif';
		} elseif ($imgdata['type']==2) {
			$imgdata['type'] = 'jpeg';
		} elseif ($imgdata['type']==3) {
			$imgdata['type'] = 'png';
		} elseif ($imgdata['type']==6) {
			$imgdata['type'] = 'bmp';
		} else {
			return false;
		}
		if (empty($imgdata) || !function_exists('imagecreatefrom'.$imgdata['type'])) {
			return false;
		}
		$imagecreatefromtype = 'imagecreatefrom'.$imgdata['type'];
		$imgdata['source']	 = $imagecreatefromtype($srcFile);
		!$imgdata['width'] && $imgdata['width'] = imagesx($imgdata['source']);
		!$imgdata['height'] && $imgdata['height'] = imagesy($imgdata['source']);
		return $imgdata;
	}


	public static function MakeImage($type, $image, $filename, $quality = 90){
		createdir(dirname($filename));

		$makeimage = 'image'.$type;
		if (!function_exists($makeimage)) {
			return false;
		}
		if ($type == 'jpeg') {
			$makeimage($image,$filename,$quality);
		} else {
			$makeimage($image,$filename);
		}
		return true;
	}
}