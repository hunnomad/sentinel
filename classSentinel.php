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

    public function addVehicleObject($groupName,$objectID,$speed,$coords)
    {
        $groupName  = isset($groupName) ? $groupName : null;
        $objectID   = isset($objectID) ? $objectID : null;
        $coords     = isset($coords) ? $coords : null;

        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            if(isset($objectID)and($objectID!=null or $objectID!=""))
            {
                if(isset($speed) AND ($speed!=0 OR $speed!="" OR $speed!=""))
                {
                    if(isset($coords)and($coords!="" or $coords!=null))
                    {
                        $command = "SET ".$groupName." ".$objectID." FIELD speed ".$speed." POINT ".$coords;
                        $value = $this->execute($command);

                        if($value['ok']==1)
                        {
                            return 1;
                        }
                        else
                        {
                            return 0;
                        }
                    }
                    else
                    {
                        echo "Missing coordinates";
                    }
                }
                else
                {
                    echo "Missing speed data";
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
        $value = array();

        $groupName  = isset($groupName) ? $groupName : null;
        if(isset($groupName)and($groupName!="" or $groupName!=null))
        {
            $command    = "SCAN ".$groupName;
            $result     = $this->execute($command);

            if($result['ok']==1)
            {
                foreach($result['objects'] as $r)
                {
                    $id = isset($r['id']) ? $r['id'] : null;
                    $latitude   = isset($r['object']['coordinates'][0]) ? (float)$r['object']['coordinates'][0] : 0.0;
                    $longitude  = isset($r['object']['coordinates'][1]) ? (float)$r['object']['coordinates'][1] : 0.0;

                    $speed = isset($r['fields'][0]) ? $r['fields'][0] : null;
                    if(isset($speed) AND ($speed!=0 OR $speed!="" OR $speed!=""))
                    {
                        $value[] = array("groupName"=>"$groupName","id"=>"$id","speed"=>(int)$speed,"latitude"=>(float)$latitude,"longitude"=>(float)$longitude);
                    }
                    else
                    {
                        $value[] = array("groupName"=>"$groupName","id"=>"$id","latitude"=>(float)$latitude,"longitude"=>(float)$longitude);
                    }
                }
                return $value;
            }
            else
            {
                return 0;
            }
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
                $value = $this->execute($command);

                if($value['ok']==1)
                {
                    return 1;
                }
                else
                {
                    return 0;
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
            $value = $this->execute($command);

            if($value['ok']==1)
            {
                return 1;
            }
            else
            {
                return 0;
            }
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
                    $value = $this->execute($command);

                    if($value['ok']==1)
                    {
                        return $value;
                    }
                    else
                    {
                        return array("errorCode"=>"300","errorMsg"=>"No results");
                    }
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
                $value = $this->execute($command);

                if($value['ok']==1)
                {
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
                    return array("errorCode"=>"300","errorMsg"=>"No results");
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
    * @name Set Webhook service
    * @function setWebhook
    * @param $webhookName string required,$groupName string required,$webhookUrl string required,$latitude float required,$longitude float required,$radius integer default 1000 meters
    * @return array
    */

    public function setWebhook($webhookName,$groupName,$webhookUrl,$latitude,$longitude,$radius=1000)
    {
        if(isset($webhookName)AND($webhookName!="" OR $webhookName!=NULL))
        {
            if(isset($groupName) AND($groupName!="" OR $groupName!=NULL))
            {
                if(isset($webhookUrl)AND($webhookUrl!="" OR $webhookUrl!=NULL))
                {
                    if(isset($latitude)AND($latitude!="" OR $latitude!=NULL))
                    {
                        if(isset($longitude)AND($longitude!="" OR $longitude!=NULL))
                        {
                            $command    = "SETHOOK ".$webhookName." ".$webhookUrl." NEARBY ".$groupName." FENCE POINT ".$latitude." ".$longitude." ".$radius."";
                            $value = $this->execute($command);

                            if($value['ok']==1)
                            {
                                return 1;
                            }
                            else
                            {
                                return 0;
                            }
                        }
                        else
                        {
                            return "Missing longitude";
                        }
                    }
                    else
                    {
                        return "Missing latitude";
                    }
                }
                else
                {
                    return "Missing webhookUrl";
                }
            }
            else
            {
                return "Missing groupName";
            }
        }
        else
        {
            return "Missing webhookName";
        }
    }

    /**
    * @name
    * @function
    * @param
    * @return
    */

    public function delWebhook($webHookName)
    {
        if(isset($webHookName) AND ($webHookName!="" or $webHookName!=NULL))
        {
            $command    = "DELHOOK $webHookName";
            $value      = $this->execute($command);

            if($value['ok']==1)
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            return "2"; // Missing webhook name
        }
    }

    /**
    * @name
    * @function
    * @param
    * @return
    */

    public function getWebhooks()
    {
        $command    = "HOOKS *";
        $value      = $this->execute($command);

        $result = array();
        $webHookdata= $value['hooks'];

        if(is_array($webHookdata))
        {
            foreach($webHookdata AS $r)
            {

                $webhookName    = isset($r['name']) ? $r['name'] : NULL;
                $groupName      = isset($r['key']) ? $r['key'] : NULL;
                $endpoints      = implode(",",$r['endpoints']);
                $watchType      = isset($r['command'][0]) ? $r['command'][0] : NULL;
                $crossType      = isset($r['command'][1]) ? $r['command'][1] : NULL;
                $geoType        = isset($r['command'][2]) ? $r['command'][2] : NULL;
                $latitude       = isset($r['command'][4]) ? $r['command'][4] : NULL;
                $longitude      = isset($r['command'][5]) ? $r['command'][5] : NULL;
                $radius         = isset($r['command'][6]) ? $r['command'][6] : NULL;

                $result[] = array
                (
                    "webHook"   =>"$webhookName",
                    "groupName" =>"$groupName",
                    "endpoints" =>"$endpoints",
                    "watchType" =>"$watchType",
                    "crossType" =>"$crossType",
                    "geoType"   =>"$geoType",
                    "latitude"  =>(float)$latitude,
                    "longitude" =>(float)$longitude,
                    "radius"    =>(int)$radius
                );
            }
        return $result;
        }
        else
        {
            return array("errorCode"=>"300","errorMsg"=>"No results");
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