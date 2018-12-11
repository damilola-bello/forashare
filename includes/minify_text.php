<?php
	function minify($text)
	{
		$text = trim($text);
		$str_len = strlen($text);
		if ($str_len > 200) {
			$new_text = substr($text, 0, 147) . "...";
			return $new_text;
		} else {
			return $text;
		}
	}
?>