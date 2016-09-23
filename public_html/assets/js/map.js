

function initMap() {
    var styles = [
    {
        "featureType": "administrative",
        "elementType": "labels.text.fill",
        "stylers": [
            {
                "color": "#444444"
            }
        ]
    },
    {
        "featureType": "landscape",
        "elementType": "all",
        "stylers": [
            {
                "color": "#f2f2f2"
            }
        ]
    },
    {
        "featureType": "poi",
        "elementType": "all",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "road",
        "elementType": "all",
        "stylers": [
            {
                "saturation": -100
            },
            {
                "lightness": 45
            }
        ]
    },
    {
        "featureType": "road.highway",
        "elementType": "all",
        "stylers": [
            {
                "visibility": "simplified"
            }
        ]
    },
    {
        "featureType": "road.arterial",
        "elementType": "labels.icon",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "transit",
        "elementType": "all",
        "stylers": [
            {
                "visibility": "off"
            }
        ]
    },
    {
        "featureType": "water",
        "elementType": "all",
        "stylers": [
            {
                "color": "#28b8da"
            },
            {
                "visibility": "on"
            }
        ]
    }
];

if(latitude == '' || longitude == ''){
    return;
} else if(isNaN(latitude) || isNaN(longitude)){
    return
}

latLng = new google.maps.LatLng(latitude, longitude);
map = new google.maps.Map(document.getElementById('map'), {
    center: {lat: parseFloat(latitude), lng: parseFloat(longitude)},
    zoom: 10,
    styles: styles
  });
var marker = new google.maps.Marker({
      position: latLng,
      title:marker,
      visible: true
  });

  marker.setMap(map);
}
// fix for hidden maps in div
$('a[href="#tab_map"]').on('click', function() {
      setTimeout(function(){
        if(typeof(map == 'undefined')){
            return;
        }
        center = map.getCenter();
        google.maps.event.trigger(map, 'resize');
         map.setCenter(center);
    },500)
});

