<?php
	function set_title($type, $is_like, $liked) {
		$str = '';
		if($liked) {
			$str = "Remove $is_like";
		} else {
			$str = "I $is_like this $type";
		}
		return $str;
	}
?>