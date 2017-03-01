<?php
  define("DATABASE", "shuttle_signups");
  define("MAX_OCCUPANCY", 20);
  define("RIDE_DATE", "2017-03-04");
  define("READABLE_DATE", "Saturday, March 4, 2017");

  $departureTimeOptions = [
    "6:00 pm", "7:00 pm", "8:00 pm", "9:00 pm"
  ];

  $arrivalTimeOptions = [
    "6:30 pm", "7:30 pm", "8:30 pm", "9:30 pm",
    "12:30 am (Sunday, March 5)"
  ];

  $fullDepartures = [];
  $fullReturns = [];

  $connection = new PDO("mysql:host=localhost", "root", "[pass]");
  $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $connection->exec("CREATE DATABASE IF NOT EXISTS `" . DATABASE . "`");
  $connection->exec("USE " . DATABASE);
  $connection->exec("CREATE TABLE IF NOT EXISTS `submissions` (
      `id` INT NOT NULL AUTO_INCREMENT,
      `rcs_id` VARCHAR(45) NOT NULL,
      `first_name` VARCHAR(45) NOT NULL,
      `last_name` VARCHAR(45) NOT NULL,
      `ride_date` DATE NOT NULL,
      `departure_time` VARCHAR(45) NOT NULL,
      `return_time` VARCHAR(45) NOT NULL,
      `timestamp` TIMESTAMP NOT NULL,
      `comments` TEXT NULL,
    PRIMARY KEY (`id`));");

  if($_SERVER['REQUEST_METHOD'] === 'POST') {
    print_r($_POST);
    $statement = $connection->prepare("INSERT INTO `shuttle_signups`.`submissions`
      (`rcs_id`, `first_name`, `last_name`, `ride_date`,
       `departure_time`, `return_time`, `comments`)
      VALUES
      (:rcs_id, :first_name, :last_name, :ride_date,
        :departure_time, :return_time, :comments)");

    $statement->bindValue(":rcs_id", $_POST['rcsId']);
    $statement->bindValue(":first_name", $_POST['firstName']);
    $statement->bindValue(":last_name", $_POST['lastName']);
    $statement->bindValue(":ride_date", RIDE_DATE);
    $statement->bindValue(":departure_time", $_POST['departureTime']);
    $statement->bindValue(":return_time", $_POST['returnTime']);
    $statement->bindValue(":comments", isset($_POST['comments']) ? $_POST['comments'] : 'NULL');

    $statement->execute();

    $name = $_POST['firstName'] . ' ' . $_POST['lastName'];
    $selectedDeparture = $_POST['departureTime'];
    $selectedReturn = $_POST['returnTime'];
    $headers = "From: shuttles@union.lists.rpi.edu" . "\r\n";
    $confirmationMessage = "Hi $name!\n\nThank you for signing up for the Mall Shuttle Program for " . READABLE_DATE . "!\n\n"
      . "Please show this to the shuttle driver (either on your phone or on a piece of paper), as this will serve as your ticket.\n\n"
      . "You are leaving the Union Horseshoe at <strong>$selectedDeparture</strong> and are leaving the mall at: <strong>$selectedReturn<\strong>\n\n"
      . "To ensure that you do not miss your bus, please arrive AT LEAST FIVE MINUTES PRIOR to when you signed up to depart. Due to limited space, "
      . "it is not guaranteed that if you miss your bus, you will be able to ride the next one. The SHUTTLE TO THE MALL will pick you up at the Union "
      . "Horseshoe and drop you off between Dick's Sporting Goods and Dave and Buster's. The SHUTTLE FROM THE MALL will pick you up between Dick's "
      . "Sporting Goods and Dave and Buster's and drop you off at the Union Horseshoe. Total commute time is about 25 minutes. When getting on the bus, "
      . "please show this message, as it serves as your ticket.\nThank you for participating in the Mall Shuttle Program! We hope that you have a great time!\n\n"
      . "Thank you!  If you have any questions, feel free to email shuttles@union.lists.rpi.edu.";
    $confirmationMessage = wordwrap($confirmationMessage, 80);
    mail($_POST['rcsId'] . '@rpi.edu', "Crossgates Mall Shuttle Signup Confirmation - " . READABLE_DATE, $confirmationMessage, $headers);
  }

  $statement = $connection->prepare("SELECT departure_time, COUNT(departure_time) AS total FROM submissions WHERE `ride_date` = :ride_date GROUP BY departure_time");
  $statement->bindValue(":ride_date", RIDE_DATE);
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);

  foreach ($result as $r) {
    if($r['total'] >= MAX_OCCUPANCY) {
      $fullDepartures[] = $r['departure_time'];
    }
  }

  $statement = $connection->prepare("SELECT return_time, COUNT(return_time) AS total FROM submissions WHERE `ride_date` = :ride_date GROUP BY return_time");
  $statement->bindValue(":ride_date", RIDE_DATE);
  $statement->execute();
  $result = $statement->fetchAll(PDO::FETCH_ASSOC);

  foreach ($result as $r) {
    if($r['total'] >= MAX_OCCUPANCY) {
      $fullReturns[] = $r['return_time'];
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Shuttle Signups</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons"
          rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body class="grey lighten-4">
    <div class="container">
      <div class="row">
        <div class="col l8 offset-l2 m12 offset-m0">
          <div class="card-panel" style="margin-top: 2rem;">
            <h2>Mall Shuttle Pre-Registration</h2>
            <h5><?=READABLE_DATE ?></h5>
            <p>
              Let's go to the mall!
              A collaboration between Auxiliary Services and Student Senate.  Thanks to Northeast Shuttle for providing the buses.
            </p>

            <form method="POST" action="index.php">
              <div class="row">
                <div class="input-field col s12">
                  <input type="text" class="validate" name="rcsId" required>
                  <label for="rcsId">RCS ID <sup class="red-text">*</sup></label>
                </div>
                <div class="input-field col s12">
                  <input type="text" class="validate" name="firstName" required>
                  <label for="firstName">First Name <sup class="red-text">*</sup></label>
                </div>
                <div class="input-field col s12">
                  <input type="text" class="validate" name="lastName" required>
                  <label for="lastName">Last Name <sup class="red-text">*</sup></label>
                </div>
              </div>
              <div class="row">
                <div class="col s12">
                  <p class="grey-text">Departure time (leaving the Union for Crossgates): <sup class="red-text">*</sup></p>
                  <?php
                    foreach ($departureTimeOptions as $i => $time) {
                      $disabled = false;
                      if(($k = array_search($time, $fullDepartures)) !== false) {
                        $disabled = true;
                      }
                      echo "<p><input name=\"departureTime\" type=\"radio\" class=\"with-gap\" id=\"departureTime$i\" value=\"$time\" required" . ($disabled ? " disabled " : " ") . "/>";
                      echo "<label for=\"departureTime$i\">$time" . ($disabled ? " (FULL)" : "") . "</label></p>";
                    }
                  ?>
                </div>
              </div>
              <div class="row">
                <div class="col s12">
                  <p class="grey-text">Return time (leaving the Mall for the Union): <sup class="red-text">*</sup></p>
                  <?php
                    foreach ($arrivalTimeOptions as $i => $time) {
                      echo "<p><input name=\"returnTime\" type=\"radio\" class=\"with-gap\" id=\"returnTime$i\" value=\"$time\" required />";
                      echo "<label for=\"returnTime$i\">$time</label></p>";
                    }
                  ?>
                </div>
              </div>
              <div class="row">
                <div class="input-field col s12">
                  <textarea class="materialize-textarea" name="additionalComments"></textarea>
                  <label for="additionalComments">Additional Comments</label>
                </div>
              </div>
              <div class="row">
                <div class="col s12 right-align">
                  <button class="btn waves-effect waves-light" type="submit" name="action">Submit
                    <i class="material-icons right">send</i>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.98.0/js/materialize.min.js"></script>
  </body>
</html>
