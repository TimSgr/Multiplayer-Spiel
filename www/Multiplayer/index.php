<?php
    session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="3; url=index.php">
    <meta charset="utf-8">
    <style>
        body {font-family: Verdana; font-size: 10pt;
              color:#b22222; background-color: #e3e3e3}
    </style>
<script>
    function senden(aktion, wert)
    {
        if(aktion == 1)
        {
            if(wert == 2)
            {
                if(!confirm("Vor Beginn des Spiels abmelden?"))
                return;
            }
            else if(wert == 3)
            {
                if(!confirm("Beide vor dem Ende abmelden?"))
                return;
            }
        }

        document.f.aktion.value = aktion;
        document.f.karteGespielt.value = wert;
        document.f.submit();
    }
</script>
<?php
    function anmelden()
    {
        $spieler = simplexml_load_file("spieler.xml");
        $wartender = simplexml_load_file("wartender.xml");

        if(strlen($wartender->ID) <= 1)
            $wartender->ID = session_id();
        else
        {
            $spieler->gegnerID = $wartender->ID;
            $gegner = simplexml_load_file(
                "spieler_" . $spieler->gegnerID . ".xml");
            $gegner->gegnerID = session_id();
            file_put_contents("spieler_" . $spieler->gegnerID
                . ".xml", $gegner->asXML());
            $wartender->ID = " ";
        }

        file_put_contents("spieler_" . session_id()
            . ".xml", $spieler->asXML());
        file_put_contents("wartender.xml", $wartender->asXML());
    }

    function abmelden($id)
    {
        if(!file_exists("spieler_" . $id . ".xml"))
            return;
        
        $spieler = simplexml_load_file("spieler_" . $id . ".xml");
        unlink("spieler_" . $id . ".xml");
        $infoAllgemein = "Sie haben sich abgemeldet";

        if(strlen($spieler->gegnerID) > 1)
            abmelden($spieler->gegnerID);
        else
        {
            $wartender = simplexml_load_file("wartender.xml");
            $wartender->ID = " ";
            file_put_contents("wartender.xml", $wartender->asXML());
        }
    }

    function lesen($attr)
    {
        $spieler = simplexml_load_file(
            "spieler_" . session_id(). ".xml");
        if($attr == "info")
            return $spieler->$attr;
        else
            return intval($spieler->$attr);
    }

    function lesenGegner($attr)
    {
        $spieler = simplexml_load_file("spieler_"
            . session_id() . ".xml");
        $gegner = simplexml_load_file("spieler_"
            . $spieler->gegnerID . ".xml");
        if($attr == "info")
            return $gegner->$attr;
        else    
            return intval($gegner->$attr);
    }

    function schreiben($attr, $wert)
    {
        $spieler = simplexml_load_file("spieler_"
            . session_id() . ".xml");
        $spieler->$attr = $wert;
        file_put_contents("spieler_" . session_id()
            . ".xml", $spieler->asXML());
    }

    function schreibenGegner($attr, $wert)
    {
        $spieler = simplexml_load_file("spieler_"
            .session_id() . ".xml");
        $gegner = simplexml_load_file("spieler_"
            . $spieler->gegnerID . ".xml");
        $gegner->$attr = $wert;
        file_put_contents("spieler_" . $spieler->gegnerID
            . ".xml", $gegner->asXML());
    }

    function erhoehen($attr)
    {
        $spieler = simplexml_load_file("spieler_"
            . session_id(). ".xml");
        $spieler->$attr = $spieler->$attr + 1;
        file_put_contents("spieler_" . session_id()
            . ".xml", $spieler->asXML());
    }

    function erhoehenGegner($attr)
    {
        $spieler = simplexml_load_file("spieler_"
            . session_id() . ".xml");
        $gegner = simplexml_load_file("spieler_"
            . $spieler->gegnerID . ".xml");
        $gegner->$attr = $gegner->$attr + 1;
        file_put_contents("spieler_" . $spieler->gegnerID
            . ".xml", $gegner->asXML());
    }

    if (isset($_POST["aktion"]))
    {
        if($_POST["aktion"] == "0")
            anmelden();
        else if ($_POST["aktion"] == "1")
            abmelden(session_id());
        else if ($_POST["aktion"] == "2")
            schreiben("info",
            "Zwei Spieler müssen sich anmelden. Nach der "
            . "Anmeldung hat jeder Spieler fünf Karten zur "
            . "Verfügung <br> In jeder Runde legt jeder Spieler "
            . "eine Karte. Der Spieler mit der höheren Karte "
            . "erhält einen Punkt. <br> Falls beide Spieler "
            . "dieselbe Karte legen, erhält keiner einen Punkt."
            . " Das Spiel ist nach fünf Runden beendet.");
        else if ($_POST["aktion"] == "3")
        {
            $meine = $_POST["karteGespielt"];
            schreiben("karteLetzte", $meine);
            schreiben("karte$meine", 0);
            erhoehen("runde");

            if(lesenGegner("warten") == 0)
            {
                schreiben("warten", 1);
                schreiben("info", "");
            }
            else
            {
                $karteText[1] = "eine Zehn";
                $karteText[2] = "einen Buben";
                $karteText[3] = "eine Dame";
                $karteText[4] = "ein König";
                $karteText[5] = "ein Ass";

                schreibenGegner("warten", 0);
                $seine = lesenGegner("karteLetzte");
                $meineInfo = "Du hattest $karteText[$meine], "
                    . "der Gegner hatte $karteText[$seine], ";
                $seineInfo = "Du hattest $karteText[$seine], "
                    . "der Gegner hatte $karteText[$meine], ";
            
                if($meine > $seine)
                {
                    $meineInfo .= "Punkt für Dich";
                    $seineInfo .= "Punkt für deinen Gegner";
                    erhoehen("punkte");
                }
                else if($seine > $meine)
                {
                    $meineInfo .= "Punkt für deinen Gegner";
                    $seineInfo .= "Punkt für Dich";
                    erhoehenGegner("punkte");
                }
                else
                {
                    $meineInfo .= "keine Punkte";
                    $seineInfo .= "keine Punkte";
                }

                schreiben("info", $meineInfo);
                schreibenGegner("info", $seineInfo);
            }
        }
    }
?>
</head>
<body>
<form name="f" action="index.php" method="post">
    <input type="hidden" name="aktion">
    <input type="hidden" name="karteGespielt">
<?php   
    if(!file_exists("spieler_" . session_id() . ".xml"))
        echo "<p><input type='button' value='Anmelden' "
            . "onclick='senden(0,0);'>";
    else
    {
        $status = 1;
        $wartender = simplexml_load_file("wartender.xml");
        
        if($wartender->ID == session_id())
            $status = 2;
        else
        {
            $runde = lesen("runde");
            $rundeGegner = lesenGegner("runde");
            if($runde < 5 || $rundeGegner < 5)
                $status = 3;
        }

        echo "<p><input type='button' value='Abmelden' "
            . "onclick='senden(1,$status);'> ";
        echo "<input type='button' value='Hilfe' "
            . "onclick='senden(2, $status);'></p>";
    }

    if(file_exists("spieler_" . session_id() . ".xml"))
    {
        $wartender = simplexml_load_file("wartender.xml");
        if($wartender->ID == session_id())
            echo "<p>Warte auf Anmeldung eines Gegners</p>";
        else if(lesen("warten") == 1)
            echo "<p>Warte auf eine Karte des Gegners</p>";
        else
        {
            echo "<p>Stand: " . lesen("punkte") . ":"
                . lesenGegner("punkte");
            if(lesen("runde") == 0)
                echo ", es geht los";
            echo "</p>";

            if(lesen("runde") < 5)
            {
                echo "<p><table><tr>";
                for($i=1; $i<=5; $i++)
                {
                    if(lesen("karte$i") == 1)
                        echo "<td>"
                            . "<a href='javascript:senden(3,$i);'>"
                            . "<img src='bilder/karteBild$i.png' "
                            . "border='0'></a></td>";
                else 
                    echo "<td>"
                        . "<img src='bilder/rueckseite.png'>"
                        . "</td>";
                }
                echo "</tr></table></p>";
            }
            else
            {
                schreiben("info", "");
                schreibenGegner("info", "");
                if(lesen("punkte") > lesenGegner("punkte"))
                    echo "<p>Sie haben gewonnen!";
                else if(lesenGegner("punkte") > lesen("punkte"))
                    echo "<p>Sie haben leider verloren.";
                else
                    echo "<p>Es gab ein Unentschieden.";
                echo " Bitte melden Sie sich ab.</p>";
            }
        }
    }

    if(file_exists("spieler_" . session_id() . ".xml"))
    {
        $infoPersoenlich = lesen("info");
        if($infoPersoenlich != "")
            echo "<p>$infoPersoenlich</p>";
    }

    if(isset($infoAllgemein))
        echo "<p>$infoAllgemein</p>";
?>
</form>
</body>
</html>