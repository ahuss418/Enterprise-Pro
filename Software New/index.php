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
	if (!$loggedInUser['verified'] && !$loggedInUser['admin']) {
		flash('error', "You can't access this page as your account has not been verified yet");
	}
}

require_once('includes/header.php');

$categoriesQuery = "SELECT * FROM `categories`";
$categoriesResult = $mysql->query($categoriesQuery);
$categories = [];
while ($cat = $categoriesResult->fetch_assoc()) {
	$categories[] = $cat;
}

$markers = array();

$query = "
    SELECT DISTINCT 
        ad.*, 
        uad.name AS asset_table_display_name, 
        uad.table_name AS asset_table_id, 
        uad.uploaded_by, 
        uad.category AS asset_category_id,
        c.category_name AS asset_category_name,
        c.icon AS asset_category_icon
    FROM 
        asset_data AS ad
    LEFT JOIN 
        upload_asset_data AS uad ON ad.table_id = uad.id
    LEFT JOIN 
        categories AS c ON uad.category = c.id;
";

$result = $mysql->query($query);

// These will be the markers plotted to the map
if ($result) {
	while ($row = $result->fetch_assoc()) {
		// Only add if longitude and latitude are present
		if (!empty($row['longitude']) && !empty($row['latitude'])) {
			$markers[] = [
				'id' => $row['id'],
				'name' => $row['asset_table_display_name'],
				'table_id' => $row['asset_table_id'],
				'uploaded_by' => $row['uploaded_by'],
				'category_id' => $row['asset_category_id'],
				'category_name' => $row['asset_category_name'],
				'icon' => !empty($row['asset_category_icon'])
					? $row['asset_category_icon']
					: null, // If icon not available
				'position' => array(
					'lat' => floatval($row['latitude']), // Convert to float
					'lng' => floatval($row['longitude']) // Convert to float
				),
			];
		}
	}
} else {
	die("Query failed: " . $mysql->error);
}
?>

<!-- Add section for buttons that visible or hide items -->

<!-- Main content area with a sidebar layout -->
<div class="grid-with-sidebar">
	<div class="block block--sections" id="assetTable">
		<!-- Header for the point of interest information -->
		<div class="block__header" id="poi-info">
			Asset Details
		</div>
		<!-- Body for adding filter options -->

		<div id="assetDetails">
			<!-- Key-value pairs will be inserted here dynamically -->
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
				<?php foreach ($categories as $category) { ?>
					<div>
						<input type="checkbox" name="toggle" value="<?php echo $category['id'] ?>" class="toggle-checkbox-input" checked>
						<?php echo $category['category_name'] ?>
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
	let markers = <?php echo json_encode($markers); ?>;
	let markersData = [];
	var icon_directory = "/static/images/icons/"


	async function loadAsset(assetId, dataset, marker) {
		try {
			const response = await fetch(`/asset-query.php?dataset=${dataset}&asset_id=${assetId}`);
			const result = await response.json();

			if (result.success) {
				const data = result.data;

				// Get the container for asset details
				const assetDetails = document.getElementById("assetDetails");
				assetDetails.innerHTML = ""; // Clear previous content

				// Display Category (if available)
				if (marker.category) {
					const categoryContainer = document.createElement("div");
					categoryContainer.className = "asset-detail";

					const categoryKey = document.createElement("div");
					categoryKey.className = "asset-key";
					categoryKey.textContent = "Category";
					categoryContainer.appendChild(categoryKey);

					const categoryValue = document.createElement("div");
					categoryValue.className = "asset-value";
					categoryValue.textContent = marker.category;
					categoryContainer.appendChild(categoryValue);

					const spacer = document.createElement("hr");
					categoryContainer.appendChild(spacer);

					assetDetails.appendChild(categoryContainer);
				}

				// Display Dataset Name (if available)
				if (marker.title) {
					const datasetContainer = document.createElement("div");
					datasetContainer.className = "asset-detail";

					const datasetKey = document.createElement("div");
					datasetKey.className = "asset-key";
					datasetKey.textContent = "Dataset";
					datasetContainer.appendChild(datasetKey);

					const datasetValue = document.createElement("div");
					datasetValue.className = "asset-value";
					datasetValue.textContent = marker.title;
					datasetContainer.appendChild(datasetValue);

					const spacer = document.createElement("hr");
					datasetContainer.appendChild(spacer);

					assetDetails.appendChild(datasetContainer);
				}

				// Add key-value pairs dynamically
				for (const [key, value] of Object.entries(data)) {
					// Create container for each pair
					const detailContainer = document.createElement("div");
					detailContainer.className = "asset-detail";

					// Key (bold)
					const keyElement = document.createElement("div");
					keyElement.className = "asset-key";
					keyElement.textContent = key;
					detailContainer.appendChild(keyElement);

					// Value
					const valueElement = document.createElement("div");
					valueElement.className = "asset-value";
					valueElement.textContent = value || "N/A";
					detailContainer.appendChild(valueElement);

					// Add line spacer between pairs
					const spacer = document.createElement("hr");
					detailContainer.appendChild(spacer);

					// Add to container
					assetDetails.appendChild(detailContainer);
				}
				document.querySelector(".grid-with-sidebar").classList.add("show-sidebar");
				document.getElementById("assetTable").classList.add("show");
			} else {
				console.error('Failed to load asset:', result.error);
				alert(`Error: ${result.error}`);
			}
		} catch (error) {
			console.error('Error loading asset:', error);
			alert('Failed to load asset.');
		}
	}

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
		markers.forEach(function(data) {
			let markerIcon = null;
			if (data.icon != null) {
				let iconPath = icon_directory + data.icon;
				markerIcon = {
					url: iconPath,
					scaledSize: new google.maps.Size(30, 30)
				};
			}
			let marker = new google.maps.Marker({
				asset_id: data.id,
				table_id: data.table_id,
				position: data.position,
				map: map,
				title: data.name,
				category: data.category_name,
				category_id: data.category_id,
				uploaded_by: data.uploaded_by,
				icon: markerIcon,
				visible: true
			});

			marker.addListener('mouseover', function() {
				infowindow.setContent(`
        <div class='infowindow-container' style="
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
        "> 
            <!-- QR Code Container -->
            <div id='qrcode' style="width: 100px; height: 100px; flex-shrink: 0;"></div>
            
            <!-- Buttons -->
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <button class="button button--success button--small" 
                    onclick="getDirections(${marker.position.lat()}, ${marker.position.lng()})">
                    Directions
                </button>
                <button class="button button--error button--small" 
                    onclick="reportAsset('${marker.table_id}', '${marker.asset_id}')">
                    Report
                </button>
            </div>
        </div>
    `);

				infowindow.open(map, marker);

				// Create QR Code (smaller size)
				const qrcodeContainer = document.getElementById('qrcode');
				if (qrcodeContainer) {
					const qrcode = new QRCode(qrcodeContainer, {
						width: 100, // Reduced size
						height: 100
					});
					qrcode.makeCode(`https://www.google.com/maps/dir/?api=1&destination=${marker.position.lat()},${marker.position.lng()}`);
				}
			});

			marker.addListener('click', () => {
				// Load data into the table when marker is clicked
				loadAsset(marker.asset_id, marker.table_id, {
					category: marker.category || null,
					title: marker.title || null,
				});
			});

			markersData.push(marker);
		});
		initCategoryFilters();
		window.initMap = initMap;
	}

	function initCategoryFilters() {
		const checkboxes = document.querySelectorAll('.toggle-checkbox-input');

		checkboxes.forEach(checkbox => {
			checkbox.addEventListener('change', () => {
				const selectedCategories = Array.from(checkboxes)
					.filter(cb => cb.checked)
					.map(cb => cb.value);

				markersData.forEach(function(marker) {
					if (selectedCategories.includes(String(marker.category_id))) {
						marker.setVisible(true); // Show marker if category matches
					} else {
						marker.setVisible(false); // Hide marker if category doesn't match
					}
				});
			});
		});
	}

	function getDirections(lat, lng) {
		const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
		window.open(url, '_blank');
	}

	function reportAsset(dataset, assetId) {
		if (confirm("Do you want to report this asset?")) {
			window.location.href = `/report-asset.php?dataset=${dataset}&id=${assetId}`;
		}
	}

	document.addEventListener('DOMContentLoaded', () => {
		const searchInput = document.querySelector('.input__control[name="query"]');

		if (searchInput) {
			searchInput.addEventListener('input', () => {
				const query = searchInput.value.toLowerCase();

				// Loop through markers and update visibility
				markersData.forEach(function(marker) {
					const titleMatch = marker.title.toLowerCase().includes(query);
					const categoryMatch = marker.category.toLowerCase().includes(query);

					if (titleMatch || categoryMatch) {
						marker.setVisible(true);
					} else {
						marker.setVisible(false);
					}
				});
			});
		}
	});
</script>

<script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDnCU8gvcSLMKBjXGHVSNe2UByYALr98tA&callback=initMap&libraries=maps,marker&v=beta" defer></script>

</body>

</html>