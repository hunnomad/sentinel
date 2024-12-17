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
 * @version 1.1
 * @website https://www.crisisinfo.eu && https://www.idemc.org
 * @facebook https://www.facebook.com/Boszormenyi69/
 * @linkedin https://www.linkedin.com/in/zsolt-boszormenyi/
 */

class classSentinel
{
    private $serverUrl;
    private $port;
    private $tileServer;

    public function __construct($serverUrl, $port)
    {
        $this->serverUrl = $serverUrl;
        $this->port = $port;
        $this->tileServer = "https://{$this->serverUrl}:{$this->port}";
    }

    // Helper to validate parameters
    private function validateParam($param, $name)
    {
        if (empty($param)) {
            throw new InvalidArgumentException("Missing or invalid parameter: $name");
        }
    }

    private function execute($command)
    {
        $this->validateParam($command, 'command');
        $ch = curl_init($this->tileServer);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $command,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Expect:']
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            throw new RuntimeException("Server error: HTTP $status");
        }
        return json_decode($response, true);
    }

    /**
     * @name Add object the watcher object list, default command, no additional data
     * @function addSimpleObject
     * @param $groupName - string required
     * @param $objectID - string required
     * @param $coords - string (WGS84 decimal,without coma) required
     * @return array
     */

    public function addSimpleObject($groupName, $objectID, $coords)
    {
        $this->validateParam($groupName, 'groupName');
        $this->validateParam($objectID, 'objectID');
        $this->validateParam($coords, 'coordinates');
        $command = "SET $groupName $objectID POINT $coords";
        return $this->execute($command);
    }

    public function addVehicleObject($groupName, $objectID, $speed, $coords)
    {
        $this->validateParam($groupName, 'groupName');
        $this->validateParam($objectID, 'objectID');
        $this->validateParam($speed, 'speed');
        $this->validateParam($coords, 'coordinates');

        $command = "SET $groupName $objectID FIELD speed $speed POINT $coords";
        $result = $this->execute($command);
        return isset($result['ok']) && $result['ok'] == 1;
    }

    /**
     * @name Get all object, when included the $groupName container
     * @function getAllObject
     * @param $groupName - string required
     * @return array
     */

    public function getAllObject($groupName)
    {
        $this->validateParam($groupName, 'groupName');
        $command = "SCAN $groupName";
        $result = $this->execute($command);

        if ($result['ok'] != 1) {
            return [];
        }
        
        return array_map(function ($r) use ($groupName) {
            $id = $r['id'] ?? null;
            $lat = $r['object']['coordinates'][0] ?? 0.0;
            $lon = $r['object']['coordinates'][1] ?? 0.0;
            $speed = $r['fields'][0] ?? null;
            
            $data = [
                "groupName" => $groupName,
                "id" => $id,
                "latitude" => (float)$lat,
                "longitude" => (float)$lon
            ];
            if ($speed !== null) {
                $data['speed'] = (int)$speed;
            }
            return $data;
        }, $result['objects'] ?? []);
    }

    /**
     * @name Delete object from group
     * @function deleteObject
     * @param - $groupName string required
     * @param - objectID - string required
     * @return
     */

    public function deleteObject($groupName, $objectID)
    {
        $this->validateParam($groupName, 'groupName');
        $this->validateParam($objectID, 'objectID');
        $command = "DEL $groupName $objectID";
        $result = $this->execute($command);
        return isset($result['ok']) && $result['ok'] == 1;
    }

    /**
     * @name Finding objects in a group within a given radius
     * @function searchNearbyObjects
     * @param - $groupName  string required
     * @param - $coords float (WGS84 decimal,without coma) required
     * @param - $radius integer (meters)
     * @return array
     */

    public function searchNearbyObjects($groupName, $coords, $radius = 1000)
    {
        $this->validateParam($groupName, 'groupName');
        $this->validateParam($coords, 'coordinates');
        $this->validateParam($radius, 'radius');
        $command = "NEARBY $groupName POINT $coords $radius";
        return $this->execute($command);
    }

    /**
     * @name Find the closest object in the group
     * @function searchClosestObject
     * @param - $groupName  string required
     * @param $coords - string (WGS84 decimal,without coma) required
     * @return array, JSON
     */

    public function searchClosestObject($groupName, $coords)
    {
        $this->validateParam($groupName, 'groupName');
        $this->validateParam($coords, 'coordinates');
        $command = "NEARBY $groupName LIMIT 1 POINT $coords";
        $result = $this->execute($command);

        if ($result['ok'] != 1 || empty($result['objects'])) {
            return null;
        }

        $object = $result['objects'][0];
        return [
            'id' => $object['id'] ?? null,
            'latitude' => $object['object']['coordinates'][0] ?? 0.0,
            'longitude' => $object['object']['coordinates'][1] ?? 0.0
        ];
    }

    /**
    * @name Set Webhook service
    * @function setWebhook
    * @param $webhookName string required,$groupName string required,$webhookUrl string required,$latitude float required,$longitude float required,$radius integer default 1000 meters
    * @return array
    */

    public function setWebhook($webhookName, $groupName, $webhookUrl, $latitude, $longitude, $radius = 1000)
    {
        foreach (compact('webhookName', 'groupName', 'webhookUrl', 'latitude', 'longitude') as $param => $value) {
            $this->validateParam($value, $param);
        }
        $command = "SETHOOK $webhookName $webhookUrl NEARBY $groupName FENCE POINT $latitude $longitude $radius";
        return $this->execute($command);
    }

    /**
    * @delWebhook
    * @function
    * @param string webHookName
    * @return integer
    */

    public function delWebhook($webhookName)
    {
        $this->validateParam($webhookName, 'webhookName');
        $command = "DELHOOK $webhookName";
        $result = $this->execute($command);
        return isset($result['ok']) && $result['ok'] == 1;
    }

    /**
    * @getWebhooks
    * @function
    * @param void
    * @return string
    */

    public function getWebhooks()
    {
        $command = "HOOKS *";
        return $this->execute($command);
    }
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
