<?php
require_once('includes/setup.php');

$page = 'index';
$title = 'Home';

$asset_usages = [
	"roads" => ['usage' => 'Roads'],
	"public_spaces" => ['usage' => 'Public Spaces'],
	"building" => ['usage' => 'Buildings'],
	"waste_cleanliness" => ['usage' => 'Waste & Cleanliness'],
	"safety" => ['usage' => 'Safety']

];

if ($loggedInUser) {
	if (!$loggedInUser['email_confirmed']) {
		flash('error', 'Please verify your email to gain full access to the site.');
	}
}
require_once('includes/header.php');
?>

<!-- Add section for buttons that visible or hide items -->

<!-- Main content area with a sidebar layout -->
<div class="grid-with-sidebar">

	<div class="block block--sections">
		<!-- Header for the point of interest information -->
		<div class="block__header" id="poi-info">
			Asset Name
		</div>
		<!-- Body for adding filter options -->
		<div class="block__body">
			<div class="filters">
				<div id="poi-type">Test</div>
				<div id="poi-usage">Test</div>
			</div>
			<!-- Add filters here -->
		</div>
	</div>
	<!-- Container for the map -->
	<div class="map-container">
		<!-- <div class="map-search">
			<input type="text" id="search-input" placeholder="Search...">
		</div> -->
		<div id="map"></div>
		<!-- Container for asset filters -->
		<div class="asset-filters-container">
			<div class="filter__title">
				Categories
			</div>
			<div class="asset__filters" id="asset_filters">
				<?php foreach ($asset_usages as $usage) { ?>
					<div>
						<input type="checkbox" name="toggle" value="<?php echo $usage['usage'] ?>" class="toggle-checkbox-input">
						<?php echo $usage['usage'] ?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>

</div>
</div>

</div>
</main>
<footer class="footer">
	<div class="footer_half">© 2025 City of Bradford Metropolitan District Council</div>
</footer>

<script src="/static/main.js"></script>
<script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
<script>
	function updateInfoWindowPosition(infoWindow, markerPosition, map) {
		// Calculate pixel coordinates of the marker position
		var overlay = new google.maps.OverlayView();
		overlay.draw = function() {};
		overlay.setMap(map);
		var pixelPosition = overlay.getProjection().fromLatLngToContainerPixel(markerPosition);

		// Set the position of the info window above the clicked marker
		infoWindow.style.left = (pixelPosition.x - (infoWindow.offsetWidth / 2)) + 'px'; // Center horizontally
		infoWindow.style.top = (pixelPosition.y - infoWindow.offsetHeight - 10) + 'px'; // 10px offset above the marker
	}

	function initMap() {
		var yorkshireBounds = {
			north: 54.559322,
			south: 53.708983,
			east: -0.354813,
			west: -2.707663
		};
		const map = new google.maps.Map(document.getElementById("map"), {
			zoom: 15,
			center: {
				lat: 53.7924,
				lng: -1.7533
			},
			streetViewControl: true,
			mapTypeControl: true,
			styles: [{
				featureType: 'poi',
				elementType: 'labels',
				stylers: [{
					visibility: 'off'
				}]
			}],
			zoomControlOptions: {
				position: google.maps.ControlPosition.LEFT_BOTTOM // Move zoom control to the left top
			},
			restriction: {
				latLngBounds: yorkshireBounds,
				strictBounds: false
			}
		});
		let infowindow = new google.maps.InfoWindow();
	}
	window.initMap = initMap;
</script>

<script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDnCU8gvcSLMKBjXGHVSNe2UByYALr98tA&callback=initMap&libraries=maps,marker&v=beta" defer></script>

</body>

</html>