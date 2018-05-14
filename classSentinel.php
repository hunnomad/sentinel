<?php
# Developer Stage error report on/off ----------------------------------------------------
error_reporting(E_ALL);
ini_set("display_errors", 1);
# Developer Stage error report on/off ----------------------------------------------------

/*
 * Coordinate-Based Surveillance System Based on Tile 38 Server
 *
 * @author Zsolt Boszormenyi
 * @email hunnomad@gmail.com
 * @version 1.0
 * @website http://www.hunnomad.hu
 * @facebbok https://www.facebook.com/zsolt.boszormenyi
 * @linkedin https://www.linkedin.com/in/zsolt-boszormenyi/
 */

class classSentinel
{
    private $serverUrl;
    private $port;
    private $tileServer;

    function __construct($serverUrl,$port)
    {
        $this->serverUrl    = $serverUrl;
        $this->port         = $port;
        $this->tileServer = "http://".$this->serverUrl.":".$this->port."";
    }

    /**
     * @name Add object the watcher object list, default command, no additional data
     * @function addSimpleObject
     * @param $groupName - string required
     * @param $objectID - string required
     * @param $coords - string (WGS84 decimal,without coma) required
     * @return array
     */

    public function addSimpleObject($groupName,$objectID,$coords)
    {
        $groupName  = isset($groupName) ? $groupName : null;
        $objectID   = isset($objectID) ? $objectID : null;
        $coords     = isset($coords) ? $coords : null;

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            if(isset($objectID)and($objectID!=null or $objectID!=""))
            {
                if(isset($coords)and($coords!="" or $coords!=null))
                {
                    $command = "SET ".$groupName." ".$objectID." POINT ".$coords;
                    $this->execute($command);
                }
                else
                {
                    echo "Missing coordinates";
                }
            }
            else
            {
                echo "Missing objectID parameter";
            }
        }
        else
        {
            echo "Missing groupName parameter";
        }
    }

    /**
     * @name Get all object, when included the $groupName container
     * @function getAllObject
     * @param $groupName - string required
     * @return array
     */

    public function getAllObject($groupName)
    {
        $groupName  = isset($groupName) ? $groupName : null;

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            $command    = "SCAN ".$groupName;
            $this->execute($command);
        }
        else
        {
            echo "Missing groupName parameter";
        }
    }

    /**
     * @name Delete object from group
     * @function deleteObject
     * @param - $groupName string required
     * @param - objectID - string required
     * @return
     */

    public function deleteObject($groupName,$objectID)
    {
        $groupName  = isset($groupName) ? $groupName : null;
        $objectID   = isset($objectID) ? $objectID : null;

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            if(isset($objectID)and($objectID!="" or $objectID!=null))
            {
                $command = "DEL ".$groupName." ".$objectID;
                $this->execute($command);
            }
            else
            {
                echo "Missing objectID parameter";
            }
        }
        else
        {
            echo "Missing groupName parameter";
        }
    }

    /**
     * @name Delete all objects from the group
     * @function deleteAllObject
     * @param - $groupName string required
     * @return array
     */

    public function deleteAllObject($groupName)
    {
        $groupName  = isset($groupName) ? $groupName : null;

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            $command = "DROP ".$groupName;
            $this->execute($command);
        }
        else
        {
            echo "Missing groupName parameter";
        }
    }

    /**
     * @name Finding objects in a group within a given radius
     * @function searchNearbyObjects
     * @param - $groupName  string required
     * @param - $coords float (WGS84 decimal,without coma) required
     * @param - $radius integer (meters)
     * @return array
     */

    public function searchNearbyObjects($groupName,$coords,$radius=1000)
    {
        $groupName  = isset($groupName) ? $groupName : null;
        $coords     = isset($coords) ? $coords : null;
        $radius     = isset($radius) ? $radius : null;

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            if(isset($coords)and($coords!="" or $coords!=null))
            {
                if(isset($radius)and($radius!="" or $coords!=null))
                {
                    $command    = "NEARBY ".$groupName." POINT ".$coords." ".$radius;
                    $this->execute($command);
                }
                else
                {
                    echo "Missing radius value";
                }
            }
            else
            {
                echo "Missing coordinates";
            }
        }
        else
        {
            echo "Missing groupName parameter";
        }
    }

    /**
     * @name Find the closest object in the group
     * @function searchClosestObject
     * @param - $groupName  string required
     * @param $coords - string (WGS84 decimal,without coma) required
     * @return array, JSON
     */

    public function searchClosestObject($groupName,$coords)
    {
        $groupName  = isset($groupName) ? $groupName : null;
        $coords     = isset($coords) ? $coords : null;
        $rawCoord   = explode(' ',$coords);
        $latitude   = isset($rawCoord[0]) ? $rawCoord[0] : "0.0";
        $longitude  = isset($rawCoord[1]) ? $rawCoord[1] : "0.0";

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            if(isset($coords)and($coords!="" or $coords!=null))
            {
                $command = "NEARBY ".$groupName." LIMIT 1 POINT ".$coords;
                $result = $this->execute($command);

                $officeID   = $result['objects'][0]['id'];
                $officeLon  = $result['objects'][0]['object']['coordinates'][0];
                $officeLat  = $result['objects'][0]['object']['coordinates'][1];

                $distance   = number_format($this->distance($latitude,$longitude,$officeLat,$officeLon,'K'),2, '.', '');
                #echo $officeID." ".$officeLat.",".$officeLon."|".$distance;

                $data = array
                (
                    'officeID'=>(int)$officeID,
                    'latitude'=>floatval($officeLat),
                    'longitude'=>floatval($officeLon),
                    'distance'=>floatval($distance)
                );
                return $data;
            }
            else
            {
                echo "Missing coordinates";
            }
        }
        else
        {
            echo "Missing groupName parameter";
        }
    }

    /* ---------------------------------------------------------------------------------*/

    /**
     * @name Calculate distance between two coordinates
     * @function distance
     * @param $latitude1 - float required
     * @param $longitude1 - float required
     * @param $latitude2 - float required
     * @param $longitude2 - float required
     * @param $unit - string (M - Miles,K - Kilometers,N - Nautical Miles) required
     * @return float
     */

    public function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K")
        {
            return ($miles * 1.609344);
        }
        else if ($unit == "N")
        {
            return ($miles * 0.8684);
        }
        else
        {
            return $miles;
        }
    }

    /**
     * @name Tile35 Server command execute
     * @function execute
     * @param $command string required
     * @return array
     */

    private function execute($command)
    {
        #echo $command;
        if(isset($command)and($command!="" or $command!=null))
        {
            $DataUrl = $this->tileServer;
            $ch = curl_init($DataUrl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
            $response = curl_exec($ch);

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if($status!=200)
            {
                $returnValue = array("error_code" =>"Call to URL $DataUrl failed with status $status.");
                echo "<pre>";
                print_r($returnValue);
                echo "</pre>";
            }
            else
            {
                /* Developrt stage - start*/
                /*
                $answer = json_decode($response,true);
                echo "<pre>";
                print_r($answer);
                echo "</pre>";
                */
                /* Developrt stage - end*/
                return json_decode($response,true);
            }
        }
        else
        {
            echo "Missing command parameter";
            exit;
        }
    }
# End of class
}

/* Examples */
# Init Sentinel
# $sentinel = new classSentinel('127.0.0.1','9851');

# Add obcjet
# $sentinel->addObject('group1','00001','47.328611 19.057080');

# Get all object
# $sentinel->getAllObject('group1');

# Delete spicified object
# $sentinel->deleteObject('group1','00001');

# Delete all object
# $sentinel->deleteAllObject('group1');

# Search all nearby objects in x radius
# $sentinel->searchNearbyObjects('group1','47.339836 19.071414',1800);

# Find the closest object in the group
# $sentinel->searchClosestObject('group1','47.339836 19.071414');

?>