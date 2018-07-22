<?php use Fisharebest\Webtrees\Functions\FunctionsEdit; ?>
<?php use Fisharebest\Webtrees\I18N; ?>
<?php use Fisharebest\Webtrees\View; ?>

<?= view('components/breadcrumbs', ['links' => $breadcrumbs]) ?>

<h1><?= $title ?></h1>

<div class="form-group row">
    <div class="col-sm-10 offset-sm-1">
        <div id="osm-map" class="wt-ajax-load col-sm-12 osm-admin-map"></div>
    </div>
</div>

<form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="place_id" value="<?= $place_id ?>">
    <input type="hidden" name="level" value="<?= count($hierarchy) ?>">
    <input type="hidden" name="place_long" value="<?= $lng ?>">
    <input type="hidden" name="place_lati" value="<?= $lat ?>">

    <div class="form-group row">
        <label class="col-form-label col-sm-1" for="new_place_name">
            <?= I18N::translate('Place') ?>
        </label>
        <div class="col-sm-5">
            <input type="text" id="new_place_name" name="new_place_name" value="<?= e($location->getPlace()) ?>"
            class="form-control" required>
        </div>
        <label class="col-form-label col-sm-1" for="icon">
            <?= I18N::translate('Flag') ?>
        </label>
        <div class="col-sm-4">
            <div class="input-group" dir="ltr">
                <?= FunctionsEdit::formControlFlag(
                    $location->getIcon(),
                    ['name' => 'icon', 'id' => 'icon', 'class' => 'form-control']
                )
                ?>
            </div>
        </div>
    </div>

    <div class="form-group row">
        <label class="col-form-label col-sm-1">
            <?= I18N::translate('Latitude') ?>
        </label>
        <div class="col-sm-3">
            <div class="input-group">
                <input type="text" id="new_place_lati" class="editable form-control" name="new_place_lati" required
                placeholder="<?= I18N::translate('degrees') ?>" value="<?= $lat ?>"
                >
            </div>
        </div>

        <label class="col-form-label col-sm-1">
            <?= I18N::translate('Longitude') ?>
        </label>
        <div class="col-sm-3">
            <div class="input-group">
                <input type="text" id="new_place_long" class="editable form-control" name="new_place_long" required
                placeholder="<?= I18N::translate('degrees') ?>" value="<?= $lng ?>"
                >
            </div>
        </div>
        <label class="col-form-label col-sm-1" for="new_zoom_factor">
            <?= I18N::translate('Zoom') ?>
        </label>
        <div class="col-sm-2">
            <input type="text" id="new_zoom_factor" name="new_zoom_factor" value="<?= $location->getZoom() ?>"
            class="form-control" required readonly>
        </div>
    </div>

    <div class="form-group row">
        <div class="col-sm-10 offset-sm-1">
            <button class="btn btn-primary" type="submit">
                <?= /* I18N: A button label. */
                I18N::translate('save')
                ?>
            </button>
            <a class="btn btn-secondary" href="<?= e(route('map-data', ['parent_id' => $parent_id])) ?>">
                <?= I18N::translate('cancel') ?>
            </a>
        </div>
    </div>
</form>

<?php View::push('styles') ?>
<style>
    .osm-wrapper, .osm-user-map {
        height: 45vh
    }

    .osm-admin-map {
        height: 55vh;
        border: 1px solid darkGrey
    }

    .osm-sidebar {
        height: 100%;
        overflow-y: auto;
        padding: 0;
        margin: 0;
        border: 0;
        display: none;
        font-size: small;
    }

    .osm-sidebar .gchart {
        margin: 1px;
        padding: 2px
    }

    .osm-sidebar .gchart img {
        height: 15px;
        width: 25px
    }

    .osm-sidebar .border-danger:hover {
        cursor: not-allowed
    }

    [dir=rtl] .leaflet-right {
        right: auto;
        left: 0
    }

    [dir=rtl] .leaflet-right .leaflet-control {
        margin-right: 0;
        margin-left: 10px
    }

    [dir=rtl] .leaflet-left {
        left: auto;
        right: 0
    }

    [dir=rtl] .leaflet-left .leaflet-control {
        margin-left: 0;
        margin-right: 10px
    }
</style>
<?php View::endpush() ?>

<?php View::push('javascript') ?>
<script type="application/javascript">
  "use strict";

  window.WT_OSM_ADMIN = (function () {
    let baseData = {
      minZoom:         2,
      providerName:    "OpenStreetMap.Mapnik",
      providerOptions: [],
      I18N:            {
        zoomInTitle: <?= json_encode(I18N::translate('Zoom in')) ?>,
        zoomOutTitle: <?= json_encode(I18N::translate('Zoom out')) ?>,
        reset: <?= json_encode(I18N::translate('Reset to initial map state')) ?>,
        noData: <?= json_encode(I18N::translate('No mappable items')) ?>,
        error: <?= json_encode(I18N::translate('An unknown error occurred')) ?>
      }
    };

    let map      = null;
    let marker   = L.marker([0, 0], {
      draggable: true,
    });
    /**
     *
     * @private
     */
    let _drawMap = function () {
      map = L.map("osm-map", {
          center:      [0, 0],
          minZoom:     baseData.minZoom, // maxZoom set by leaflet-providers.js
          zoomControl: false, // remove default
        },
      );
      L.tileLayer.provider(baseData.providerName, baseData.providerOptions).addTo(map);
      L.control.zoom({ // Add zoom with localised text
        zoomInTitle:  baseData.I18N.zoomInTitle,
        zoomOutTitle: baseData.I18N.zoomOutTitle,
      }).addTo(map);

      marker
        .on("dragend", function (e) {
          let coords = marker.getLatLng();
          map.panTo(coords);
          _update_Controls({
            place:  "",
            coords: coords,
            zoom:   map.getZoom(),
          });
        })
        .addTo(map);
      let searchControl = new window.GeoSearch.GeoSearchControl({
        provider:        new window.GeoSearch.OpenStreetMapProvider(),
        retainZoomLevel: true,
        autoClose:       true,
        showMarker:      false,
      });

      map
        .addControl(searchControl)
        .on("geosearch/showlocation", function (result) {
          let lat   = result.location.y;
          let lng   = result.location.x;
          let place = result.location.label.split(",", 1);

          marker.setLatLng([lat, lng]);
          map.panTo([lat, lng]);

          _update_Controls({
            place:  place.shift(),
            coords: {
              "lat": lat,
              "lng": lng,
            },
            zoom:   map.getZoom(),
          });
        })
        .on("zoomend", function (e) {
          $("#new_zoom_factor").val(map.getZoom());
          map.panTo(marker.getLatLng());
        });
    };

    /**
     *
     * @param id
     * @private
     */
    let _addLayer = function (id) {
      $.getJSON("index.php?route=admin-module", {
        module: "openstreetmap",
        action: "AdminMapData",
        id:     id,
      })
        .done(function (data, textStatus, jqXHR) {
          marker.setLatLng(data.coordinates);
          map.setView(data.coordinates, data.zoom);
        })

        .fail(function (jqXHR, textStatus, errorThrown) {
          console.log(jqXHR, textStatus, errorThrown);
        })
        .always(function (data_jqXHR, textStatus, jqXHR_errorThrown) {
          switch (jqXHR_errorThrown.status) {
            case 200: // Success
              break;
            case 204: // No data
              map.fitWorld();
              break;
            default: // Anything else
              map.fitWorld();
          }
        });
    };

    /**
     *
     * @param newData
     * @private
     */
    let _update_Controls = function (newData) {
      let placeEl = $("#new_place_name");
      if (!placeEl.val().length && newData.place.length) {
        placeEl.val(newData.place);
      }
      $("#new_place_lati").val(Number(newData.coords.lat).toFixed(5)); // 5 decimal places (about 1 metre accuracy)
      $("#new_place_long").val(Number(newData.coords.lng).toFixed(5));
      $("#new_zoom_factor").val(Number(newData.zoom));
    };

    $(function () {
      $(".editable").on("change", function (e) {
        let lat = $("#new_place_lati").val();
        let lng = $("#new_place_long").val();
        marker.setLatLng([lat, lng]);
        map.panTo([lat, lng]);
      });
    });

    /**
     *
     * @param id
     */
    let initialize = function (id) {
      _drawMap();
      _addLayer(id);
    };

    return {
      /**
       *
       * @param id
       */
      drawMap: function (id) {
        initialize(id);
      },
    };
  })();

  WT_OSM_ADMIN.drawMap(<?= json_encode($ref) ?>);
</script>
<?php View::endpush() ?>