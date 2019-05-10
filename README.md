# scheduly

## About

scheduly is a PHP class which returns all the hourly timestamps for a particular week. It's useful for creating schedules. It handles DST changes, so you'll get one 23 hour day and one 25 hour day a year in timezones with DST.

## Usage

### Initialization

    include("scheduly.php");
    $scheduly = new scheduly();

If date_default_timezone_set is not set by your program, or if you want to change it, you can set it during the initialization process.

    $scheduly = new scheduly("Africa/Conakry");

### Generating the hourly timestamps for an entire week

    // Without arguments the current year and week are used.
    $scheduly->calculate_week();

    // You can also generate a particular week.
    // In this case, the 13th week of 2014.
    $scheduly->calculate_week(2014, 13);

    // You can reset date_default_timezone_set with the 3rd argument.
    $scheduly->calculate_week(2014, 13, "Europe/Busingen");

### Returning values

Once you have calculated the week, you can retrieve the values from the following variables.

    // Get the current year.
    $scheduly->year

    // Get the current week.
    $scheduly->week

The previous and next years/weeks are useful if you want to provide a back and forward button on the calendar.

    // Get the previous year.
    $scheduly->year_prev

    // Get the next year.
    $scheduly->year_next

    // Get the previous week.
    $scheduly->week_prev

    // Get the next week.
    $scheduly->week_next

You can get the timestamps for the 7 days of the week, and the previous and next days.

    // Get the timestamps for the 7 days of the week.
    for ($i=0; $i<7; $i++)
    {
        echo "<br/>" . date('l jS \of F Y h:i:s A', $scheduly->dates[$i]);
    }

    // Get the timestamps for the 7 days, and the previous and next days.
    for ($i=-1; $i<8; $i++)
    {
        echo "<br/>" . date('l jS \of F Y h:i:s A', $scheduly->dates[$i]);
    }

And, of course, you can get the hourly timestamps for the 7 days of the week.

    // Get all the hourly timestamps for the week.
    $scheduly->timestamps;

The timestamps are organized by hours, then days. To get an idea of the output, you can run something like this.

    foreach ($scheduly->timestamps as $timestamp)
    {
        echo "<br/>" . date('l jS \of F Y h:i:s A', $timestamp);
    }
