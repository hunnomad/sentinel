## Coordinate System

It's important to note that the coordinate system Tile38 uses is [WGS 84 Web Mercator](https://en.wikipedia.org/wiki/Web_Mercator), also known as EPSG:3857. All distance are in meters and all calculations are done on a spherical surface, not a plane.

## Getting Started

CURL-based class that communicates with the Tile38 server.

### Init Sentinel
```
include_once("classSentinel.php");

$sentinel = new classSentinel('127.0.0.1','9851');
```

### Available methods

- addSimpleObject
Add object the watcher object list, default command, no additional data

```
$sentinel->addObject('group1','00001','47.328611 19.057080');
```

- getAllObject
Get all object, when included the $groupName container

```
$sentinel->getAllObject('group1');
```

- deleteObject
Delete object from group

```
$sentinel->deleteObject('group1','00001');
```

- deleteAllObject
Delete all objects from the group

```
$sentinel->deleteAllObject('group1');
```

- searchNearbyObjects
Finding objects in a group within a given radius

```
$sentinel->searchNearbyObjects('group1','47.339836 19.071414',1800);
```

- searchClosestObject
Find the closest object in the group

```
$sentinel->searchClosestObject('group1','47.339836 19.071414');
```

- distance
Calculate distance between two coordinates

```
$distance   = number_format($this->distance($latitude,$longitude,$officeLat,$officeLon,'K'),2, '.', '');
```