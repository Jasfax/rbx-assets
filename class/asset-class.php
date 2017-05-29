<?php
class rbxAsset {
	
	public static function getModel( $ID ) {
		// fetch asset hash
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://assetgame.roblox.com/Asset-Thumbnail-3d/Json?assetId=' . $ID);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$asset_url = json_decode(curl_exec($ch))->{'Url'};
		curl_close($ch);
		
		$asset = [];
		
		// get info from asset hash
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $asset_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$assetInfo = json_decode(curl_exec($ch));
		curl_close($ch);

		// put some NEAT info into the asset array
		$asset['x'] = $assetInfo->{'camera'}->{'position'}->{'x'};
		$asset['y'] = $assetInfo->{'camera'}->{'position'}->{'y'};
		$asset['z'] = $assetInfo->{'camera'}->{'position'}->{'z'};
		$asset['obj_hash'] = $assetInfo->{'obj'};
		$asset['mtl_hash'] = $assetInfo->{'mtl'};
		
		// get obj & mtl contents
		$ch_obj = curl_init(self::getHashUrl($asset['obj_hash']));
		$ch_mtl = curl_init(self::getHashUrl($asset['mtl_hash']));
		curl_setopt($ch_obj, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch_obj, CURLOPT_HEADER, 0);
		curl_setopt($ch_mtl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch_mtl, CURLOPT_HEADER, 0);
		
		$mh = curl_multi_init();
		curl_multi_add_handle($mh, $ch_obj);
		curl_multi_add_handle($mh, $ch_mtl);
		
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);
		
		curl_multi_remove_handle($mh, $ch_obj);
		curl_multi_remove_handle($mh, $ch_mtl);
		curl_multi_close($mh);
		
		$asset['obj'] = curl_multi_getcontent($ch_obj);
		$asset['mtl'] = curl_multi_getcontent($ch_mtl);
		return $asset;
	}
	
	public static function getImage ( $ID ) {
		
		$image = file_get_contents('https://www.roblox.com/Thumbs/Asset.ashx?width=512&height=512&assetId='.$ID);
		
		if (md5($image) == '9975a2d4dc1ecf81f7e49916099c8c55') {
			
			// try again
			$image = file_get_contents('https://www.roblox.com/Thumbs/Asset.ashx?width=512&height=512&assetId='.$ID);
			if (md5($image) == '9975a2d4dc1ecf81f7e49916099c8c55') {
				return false;
			}
		}
		
		return $image;
		
	}
	
	public static function getHashUrl ( $hashedString ) {
		
		if (strlen($hashedString) != 32) {
			return false;
		}
		
		$bitNumber = 31; // Default is 31
		$index = 0; // Starts at 0
		
		for ($index; $index < strlen($hashedString); $index++) {         // Just iterates through the hashed string
			$bitNumber ^= ord($hashedString[$index]); // Performs a bitwise XOR assignment
		};

		return "https://t".($bitNumber % 8) . ".rbxcdn.com/" . $hashedString;
		
	}
	
}
