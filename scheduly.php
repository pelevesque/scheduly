<?php
/**
 * scheduly - A class that returns a week's hourly timestamps.
 *
 * DST changes of one hour are handled. Other types of DST
 * changes are not handled, but none exist in the world at this time.
 *
 * DST changes mean that some days can be 23 or 24 hours long.
 *
 * @version     1.0
 * @author      Pierre-Emmanuel Lévesque
 * @email       pierre.e.levesque@gmail.com
 * @copyright   Copyright 2009-2014, Pierre-Emmanuel Lévesque
 * @license     MIT License - @see LICENSE.md
 */

namespace Phoenix\Helper;

class scheduly {

	public $year;
	public $year_prev;
	public $year_next;
	public $week;
	public $week_prev;
	public $week_next;
	public $dates;
	public $timestamps;

	/**
	 * Constructor
	 *
	 * @param   string  timezone identifier
	 */
	public function __construct($timezone_identifier = NULL)
	{
		$this->_set_default_timezone($timezone_identifier);
	}

	/**
	 * Calculates the week's timestamps
	 *
	 * If the year and week are not specified, the defaults
	 * will be the current year and the current week.
	 *
	 * @param   int     year
	 * @param   int     week
	 * @param   string  timezone identifier
	 * @return  void
	 */
	public function calculate_week($year = NULL, $week = NULL, $timezone_identifier = NULL)
	{
		// set the timezone
		$this->_set_default_timezone($timezone_identifier);

		// set the year and week
		$this->_set_year_and_week($year, $week);

		// set the bordering years and weeks
		$this->_set_bordering_years_and_weeks();

		// set dates
		$this->_set_dates();

		// set timestamps
		$this->_set_timestamps();
	}

	/**
	 * Sets the default timezone
	 *
	 * @see http://php.net/manual/en/function.timezone-identifiers-list.php
	 *
	 * @param   string  timezone identifier
	 * @return  void
	 */
	protected function _set_default_timezone($timezone_identifier)
	{
		if ( ! empty($timezone_identifier))
		{
			date_default_timezone_set($timezone_identifier);
		}
	}

	/**
	 * Sets the year and week
	 *
	 * @param   int  week
	 * @param   int  year
	 * @return  void
	 */
	protected function _set_year_and_week($year, $week)
	{
		// use the current year and week as defaults
		empty($year) AND $year = date('Y');
		empty($week) AND $week = date('W');

		// remove leading zeros by casting to (int)
		$this->year = (int) $year;
		$this->week = (int) $week;
	}

	/**
	 * Sets the bordering years and weeks
	 *
	 * The 28th is used because it is always
	 * part of the last week of the year.
	 *
	 * @return  void
	 */
	protected function _set_bordering_years_and_weeks()
	{
		if ($this->week == 1)
		{
			$this->week_prev = date("W", mktime(0, 0, 0, 12, 28, $this->year - 1));
			$this->year_prev = $this->year - 1;
			$this->week_next = $this->week + 1;
			$this->year_next = $this->year;
		}
		elseif ($this->week == date("W", mktime(0, 0, 0, 12, 28, $this->year)))
		{
			$this->week_prev = $this->week - 1;
			$this->year_prev = $this->year;
			$this->week_next = 1;
			$this->year_next = $this->year + 1;
		}
		else
		{
			$this->week_prev = $this->week - 1;
			$this->year_prev = $this->year;
			$this->week_next = $this->week + 1;
			$this->year_next = $this->year;
		}
	}

	/**
	 * Sets the dates
	 *
	 * @return  void
	 */
	protected function _set_dates()
	{
		$this->dates = array();

		// pad the week number with a leading 0
		$W = ($this->week < 10) ? '0'.$this->week : $this->week;

		// set the 7 dates and days of the week
		for ($i=0; $i<7; $i++)
		{
			$day = $i + 1;
			$this->dates[$i] = strtotime($this->year . 'W' . $W . $day);
		}

		// add the -1th day for timestamp calculation
		$W = ($this->week_prev < 10) ? '0' . $this->week_prev : $this->week_prev;
		$this->dates[-1] = strtotime($this->year_prev . 'W' . $W . '7');

		// add the 7th day for timestamp calculations
		$W = ($this->week_next < 10) ? '0' . $this->week_next : $this->week_next;
		$this->dates[7] = strtotime($this->year_next . 'W' . $W . '1');
	}

	/**
	 * Sets the timestamps
	 *
	 * @return  void
	 */
	protected function _set_timestamps()
	{
		////////////////////////////////////
		// Set Raw Timestamps
		////////////////////////////////////

		$timestamps_raw = array();

		// set the timestamp 1 hour before the first day
		$min = $this->_min_UTC_offset(23, $this->dates[0]);
		$timestamps_raw[-1] = strtotime(date('Y-m-d 23:$min:00', $this->dates[-1]));

		// set the timestamps for the whole week
		for ($i=0; $i<7; $i++) // loop days
		{
			for ($j=0; $j<24; $j++) // loop hours
			{
				// set the minute offset from UTC
				// using this on every turn ensures an always perfect timestamp
				$min = $this->_min_UTC_offset($j, $this->dates[$i]);
				$timestamps_raw[$j+($i*24)] = strtotime(date("Y-m-d $j:$min:00", $this->dates[$i]));

				// add a NULL timezone for 23 hour days (DST change)
				if ($timestamps_raw[$j+($i*24)] == $timestamps_raw[($j+($i*24))-1])
				{
					$timestamps_raw[($j+($i*24))-1] = NULL;
				}
			}
		}

		// cover the last timestamp comparing it to day 8
		$min = $this->_min_UTC_offset(0, $this->dates[7]);
		$timestamps_raw[7*24] = strtotime(date("Y-m-d 00:$min:00", $this->dates[7]));
		if ($timestamps_raw[7*24] == $timestamps_raw[7*24-1])
		{
			$timestamps_raw[7*24-1] = NULL;
		}

		////////////////////////////////////
		// Prepare TimeStampe For Output
		////////////////////////////////////

		$timestamps = array();

		for ($i=0; $i<24; $i++) // loop hours
		{
			$timestamps_day = array();
	
			for ($j=0; $j<7; $j++) // loop days
			{
				// look for 25 hour days caused by a DST timechange
				if ($timestamps_raw[$i+($j*24)+1] - $timestamps_raw[$i+($j*24)] == 7200)
				{
					$timestamps_day_hour_25 = array();

					for ($k=0; $k<7; $k++) // loop days
					{
						if ($k == $j)
						{
							$timestamps_day_hour_25[] = $timestamps_raw[$i+($j*24)]+3600;
						}
						else
						{
							$timestamps_day_hour_25[] = NULL;
						}
					}
				}

				// save the timestamps for the day
				$timestamps_day[] = $timestamps_raw[$i+($j*24)];
			}

			// add the normal timestamps
			for ($j=0; $j<7; $j++)
			{
				$timestamps[] = $timestamps_day[$j];
			}

			// add timestamps relating to a 25 hour day
			if (isset($timestamps_day_hour_25))
			{
				for ($j=0; $j<7; $j++)
				{
					$timestamps[] = $timestamps_day_hour_25[$j];
				}

				unset($timestamps_day_hour_25);
			}
		}

		$this->timestamps = $timestamps;
	}

	/**
	 * Gets the offset minutes from UTC
	 *
	 * @param   int  hour
	 * @param   int  timestamp
	 * @return   int  min UTC offset
	 */
	protected function _min_UTC_offset($hour, $timestamp)
	{
		// offset in seconds (west of UTC is negative)
		$offset = date('Z', strtotime(date("Y-m-d $hour:00:00", $timestamp)));

		// remove hours
		$offset = round($offset % 3600 / 60);

		// minutes after the hour
		$offset = (60 + $offset) % 60;

		return $offset;
	}

} // End scheduly
