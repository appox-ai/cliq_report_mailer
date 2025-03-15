<?php
include 'config.php';
include 'mailer.php';

// Get users
$usersdata = file_get_contents('users.json');
$users = json_decode($usersdata, false);

// Get checkin and checkout records from csv file 
$cliq_data = file('input/data.csv');
$data = [];
foreach ($cliq_data as $line) {
  $data[] = str_getcsv($line);
}

foreach ($users as $user) {

  $userData = [];
  foreach ($data as $i) {
    if ($i[1] === $user->name) {
      $userData[] = [
        "name" => $i[1],
        "date" => $i[2],
        "checkin" => $i[3],
        "checkout" => $i[4],
        "interval" => $i[5],
      ];
    }
  }

  // total hours worked in the records
  $total = 0;
  foreach ($userData as $interval) {
    if($interval['interval'] === "NaN ") continue;
    $total += $interval['interval'];
  }

  // dates worked
  $dates = [];
  foreach ($userData as $date) {
    $dates[] = $date["date"];
  }
  $dates = array_unique($dates);

  // hours worked by day
  $dailyRecord = [];
  foreach ($dates as $day) {
    $sumDay = 0;
    foreach ($userData as $record) {
      if ($day === $record["date"] && $record['interval'] != "NaN ")
      $sumDay += $record["interval"];
    }
    $dailyRecord[] = [
      "name" => $user->name,
      "date" => $day,
      "hours" => $sumDay
    ];
  }

  $name = explode(" ", $user->name);

  // SEND MAIL
  $subject = $name[0] . ": check-in et check-out sur Cliq";
  $bodyMail = "
  <div style='color: #34495E; padding:10px;'>
      <h2> Allo " . $name[0] . ",</h2>
      <p>On tient &agrave; assurer un bon &eacute;quilibre entre vie pro et perso, et dans cette optique, voici le rapport Cliq de la semaine :</p>

      <h4>Temps accumul&eacute;s par jour</h4>
      <table width='500px'  style='border: 1px solid black; border-collapse: collapse;'>
        <head>
          <tr>
            <th width='40%' style='border: 1px solid black; border-collapse: collapse;'>Date</th>
            <th width='20%' style='border: 1px solid black; border-collapse: collapse;'>Heures</th>
          </tr>
        </head>";

  foreach ($dailyRecord as $i) {
    if ($i["name"] === $user->name) {
      $bodyMail .= "
            <tr>
              <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$i[date]</td>
              <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$i[hours]</td>
            </tr>";
    }
  }
  $bodyMail .= "
         <tr>
           <td align='center' style='border: 1px solid black; border-collapse: collapse;'><b>Total</b></td>
           <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$total heures</td>
         </tr>";
  $bodyMail .= "
      </table>

      <h4>Enregistrements individuels</h4>
      <table width='500px'  style='border: 1px solid black; border-collapse: collapse;'>
        <head>
          <tr>
            <th width='40%' style='border: 1px solid black; border-collapse: collapse;'>Date</th>
            <th width='20%' style='border: 1px solid black; border-collapse: collapse;'>Checkin</th>
            <th width='20%' style='border: 1px solid black; border-collapse: collapse;'>Checkout</th>
            <th width='20%' style='border: 1px solid black; border-collapse: collapse;'>temps</th>
          </tr>
        </head>";

  foreach ($userData as $i) {
    if ($i["name"] === $user->name) {
      $bodyMail .= "
            <tr>
              <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$i[date]</td>
              <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$i[checkin]</td>
              <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$i[checkout]</td>
              <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$i[interval] heures</td>
            </tr>";
    }
  }
  $bodyMail .= "
         <tr>
           <td align='center' style='border: 1px solid black; border-collapse: collapse;'></td>
           <td align='center' style='border: 1px solid black; border-collapse: collapse;'></td>
           <td align='center' style='border: 1px solid black; border-collapse: collapse;'><b>TOTAL</b></td>
           <td align='center' style='border: 1px solid black; border-collapse: collapse;'>$total hours</td>
         </tr>";
  $bodyMail .= "
      </table>
      <br>
      <p>Merci pour ta constance avec Cliq, c'est vraiment appr&eacute;ci&eacute; !</p>
      <p>&Agrave; bient&ocirc;t,</p>
      <p>App<b>OX</b></p>
  </div>";
  mailer($user->mail, $subject, $bodyMail, $conf['mailUsername'], $conf['mailPassword'], $conf['mailHost']);

  echo "Alert mail sent \n";

}
?>
