<?php
	define('MINUTE', 60);
	define('HOUR', 3600);
	define('DAY', 86400);
	define('YESTERDAY', 172800);
	define('WEEK', 604800);
	define('MONTH', 2592000);
	define('YEAR', 31536000);
	function calculate($seconds, $open, $close, $year, $this_year, $today, $this_day, $short = false) {
		//convert to integer
		$seconds = abs(intval($seconds));
		$str = '';
		
		//return 'a second' if the $seconds is 0
		if(empty($seconds) || $seconds == 0 || $seconds == "0") { return "1 second ago"; }

		switch ($seconds) {
			//seconds
			case ($seconds < MINUTE):
				$str = (($seconds == 1) ? "1 second" : "$seconds seconds") . " ago";
				break;

				//minutes
			case ($seconds == MINUTE):
				$str = "1 minute ago";
				break;
			case ($seconds < HOUR):
				$n = floor($seconds/MINUTE);
				$str = (($n == 1) ? "1 minute" : "$n minutes") . " ago";
				break;

				//hours
			case ($seconds == HOUR):
				$str = "1 hour ago";
				break;
			case ($seconds < DAY):
			 $n = floor($seconds/HOUR);
			 if($this_day == $today) {
				$str = (($n == 1) ? "1 hour" : "$n hours") . " ago"; 	
			 } else {
			 	$str = "Yesterday $close";
			 }
				break;

			/*//days
			case ($seconds == DAY):
				$str = "1 day ago";
				break;
			case ($seconds < WEEK):
				$n = floor($seconds/DAY);
				$str = (($n == 1) ? "1 day" : "$n days") . "ago";
				break;

				//weeks
			case ($seconds == WEEK):
				$str = "1 week";
				break;
			case ($seconds < MONTH):
			//2592000 - based on the average number of days in a month 30.4 (number of seconds in 30.4 days approx. 1 month)
				$n = floor($seconds/WEEK);
				$str = ($n == 1) ? "1 week" : "$n weeks";
				break;

			//months
			case ($seconds == MONTH):
				$str = "1 month";
				break;
			case ($seconds < YEAR):
				$n = floor($seconds/MONTH);
				$str = ($n == 1) ? "1 month" : "$n months";
				break;

			//years
			case ($seconds == YEAR):
				$str = "1 year";
				break;
		 default:
				$n = floor($seconds/YEAR);
				$str = ($n == 1) ? "1 year" : "$n years";
				break;*/
			default:
				if($this_year == $year) {
					$str = "$open $year $close";
				} else {
					$str = "$open $close";
				}
				break;
		}

		return $str;
	}
?>