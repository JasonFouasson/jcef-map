/**
 * Interactive JCE France map (Leaflet).
 */
(function () {
  'use strict';

  if (typeof L === 'undefined' || typeof jceMapData === 'undefined') {
    return;
  }

  var REGION_FILL = '#1e5a8a';
  var REGION_FILL_HOVER = '#0d7ab5';
  var REGION_FILL_ACTIVE = '#e85d04';
  var REGION_STROKE = '#ffffff';

  function qs(root, sel) {
    return root.querySelector(sel);
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (res) {
      if (!res.ok) {
        throw new Error('HTTP ' + res.status);
      }
      return res.json();
    });
  }

  function filterGeoJson(geojson, allowedCodes) {
    var set = {};
    allowedCodes.forEach(function (c) {
      set[String(c)] = true;
    });
    return {
      type: 'FeatureCollection',
      features: (geojson.features || []).filter(function (f) {
        var code = f.properties && f.properties.code;
        return set[String(code)];
      }),
    };
  }

  function createMapApp(root) {
    var canvas = qs(root, '.jce-map-canvas');
    var insetsEl = qs(root, '.jce-map-insets');
    var panel = qs(root, '.jce-map-panel');
    var panelBody = qs(root, '.jce-map-panel__body');
    var resetBtn = qs(root, '.jce-map-panel__reset');
    var closeBtn = qs(root, '.jce-map-panel__close');
    var initialRegion = root.getAttribute('data-region') || '';

    var i18n = jceMapData.i18n || {};
    var state = {
      locales: [],
      meta: null,
      geojson: null,
      selectedCode: null,
      regionLayers: {},
      markerLayer: null,
      insetMaps: [],
    };

    var map = L.map(canvas, {
      zoomControl: true,
      scrollWheelZoom: true,
      attributionControl: true,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 18,
    }).addTo(map);

    state.markerLayer = L.layerGroup().addTo(map);

    function regionStyle(feature, isActive) {
      return {
        fillColor: isActive ? REGION_FILL_ACTIVE : REGION_FILL,
        weight: 1.5,
        opacity: 1,
        color: REGION_STROKE,
        fillOpacity: isActive ? 0.75 : 0.55,
      };
    }

    function highlightFeature(e) {
      var layer = e.target;
      var code = String(layer.feature.properties.code);
      if (state.selectedCode === code) {
        return;
      }
      layer.setStyle({
        fillColor: REGION_FILL_HOVER,
        fillOpacity: 0.7,
        weight: 2,
      });
      if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
        layer.bringToFront();
      }
    }

    function resetHighlight(e) {
      var layer = e.target;
      var code = String(layer.feature.properties.code);
      layer.setStyle(regionStyle(layer.feature, state.selectedCode === code));
    }

    function onRegionClick(e) {
      var code = String(e.target.feature.properties.code);
      selectRegion(code, true);
    }

    function regionDisplayName(code, feature) {
      var fromMeta =
        state.meta &&
        state.meta.regions &&
        state.meta.regions[code] &&
        state.meta.regions[code].name;
      if (fromMeta) {
        return fromMeta;
      }
      if (feature && feature.properties) {
        return feature.properties.nom || feature.properties.name || code;
      }
      return code;
    }

    function onEachFeature(feature, layer) {
      var code = String(feature.properties.code);
      var name = regionDisplayName(code, feature);
      state.regionLayers[code] = state.regionLayers[code] || [];
      state.regionLayers[code].push(layer);
      layer.bindTooltip(name, {
        permanent: true,
        direction: 'center',
        className: 'jce-region-label',
        opacity: 1,
      });
      layer.on({
        mouseover: highlightFeature,
        mouseout: resetHighlight,
        click: onRegionClick,
      });
    }

    function refreshRegionStyles() {
      Object.keys(state.regionLayers).forEach(function (code) {
        state.regionLayers[code].forEach(function (layer) {
          layer.setStyle(regionStyle(layer.feature, state.selectedCode === code));
        });
      });
      if (insetsEl) {
        insetsEl.querySelectorAll('.jce-map-inset').forEach(function (el) {
          var region = el.getAttribute('data-region') || el.getAttribute('data-code');
          el.classList.toggle('is-active', state.selectedCode === region);
        });
      }
    }

    function localesForRegion(code) {
      if (!code) {
        return state.locales.slice();
      }
      return state.locales.filter(function (loc) {
        return String(loc.region_code) === String(code);
      });
    }

    function renderLocaleCard(loc) {
      var cityLine = [loc.postal_code, loc.city].filter(Boolean).join(' ');
      var html = '<article class="jce-locale-card" data-id="' + escapeHtml(loc.id) + '">';
      html += '<h4 class="jce-locale-card__title">' + escapeHtml(loc.title) + '</h4>';
      if (cityLine || loc.address) {
        html += '<p class="jce-locale-card__place">';
        if (loc.address) {
          html += escapeHtml(loc.address) + '<br>';
        }
        if (cityLine) {
          html += escapeHtml(cityLine);
        }
        html += '</p>';
      }
      if (loc.email) {
        html +=
          '<p class="jce-locale-card__email"><span>' +
          escapeHtml(i18n.email || 'E-mail') +
          ' :</span> <a href="mailto:' +
          escapeHtml(loc.email) +
          '">' +
          escapeHtml(loc.email) +
          '</a></p>';
      }
      html += '<div class="jce-locale-card__actions">';
      if (loc.permalink) {
        html +=
          '<a class="jce-btn jce-btn--primary" href="' +
          escapeHtml(loc.permalink) +
          '">' +
          escapeHtml(i18n.viewPage || 'Voir la fiche') +
          '</a>';
      }
      if (loc.external_url) {
        html +=
          '<a class="jce-btn jce-btn--ghost" href="' +
          escapeHtml(loc.external_url) +
          '" target="_blank" rel="noopener noreferrer">' +
          escapeHtml(i18n.externalSite || 'Site de la locale') +
          '</a>';
      }
      html += '</div></article>';
      return html;
    }

    function openPanel() {
      panel.classList.add('is-open');
      root.classList.add('is-panel-open');
    }

    function closePanel() {
      panel.classList.remove('is-open');
      root.classList.remove('is-panel-open');
    }

    function renderPanel(code) {
      var list = localesForRegion(code);
      var regionMeta = state.meta && state.meta.regions ? state.meta.regions[code] : null;
      var title = regionMeta ? regionMeta.name : code || '';
      var countLabel =
        list.length +
        ' ' +
        (list.length === 1 ? i18n.locale || 'locale' : i18n.locales || 'locales');

      var html = '';
      if (code) {
        html += '<h3 class="jce-map-panel__title">' + escapeHtml(title) + '</h3>';
        html += '<p class="jce-map-panel__count">' + escapeHtml(countLabel) + '</p>';
      } else {
        html +=
          '<p class="jce-map-panel__placeholder">' +
          escapeHtml(i18n.selectRegion || '') +
          '</p>';
      }

      if (code && list.length === 0) {
        html += '<p class="jce-map-panel__empty">' + escapeHtml(i18n.noLocales || '') + '</p>';
      } else if (code) {
        html += '<div class="jce-locale-list">';
        list.forEach(function (loc) {
          html += renderLocaleCard(loc);
        });
        html += '</div>';
      }

      panelBody.innerHTML = html;
      if (resetBtn) {
        resetBtn.hidden = !code;
      }
      if (code) {
        openPanel();
      }
    }

    function updateMarkers(code) {
      state.markerLayer.clearLayers();
      var list = code ? localesForRegion(code) : state.locales;

      list.forEach(function (loc) {
        if (typeof loc.lat !== 'number' || typeof loc.lng !== 'number') {
          return;
        }
        var marker = L.marker([loc.lat, loc.lng]);
        var popup =
          '<strong>' +
          escapeHtml(loc.title) +
          '</strong>' +
          (loc.city ? '<br>' + escapeHtml(loc.city) : '') +
          '<br><a href="' +
          escapeHtml(loc.permalink) +
          '">' +
          escapeHtml(i18n.viewPage || 'Voir la fiche') +
          '</a>';
        marker.bindPopup(popup);
        marker.on('click', function () {
          if (loc.region_code) {
            selectRegion(loc.region_code, false);
            var card = panelBody.querySelector('.jce-locale-card[data-id="' + loc.id + '"]');
            if (card) {
              card.classList.add('is-focused');
              card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
          }
        });
        state.markerLayer.addLayer(marker);
      });
    }

    function fitToRegion(code) {
      var layers = state.regionLayers[code];
      if (!layers || !layers.length) {
        return;
      }
      var group = L.featureGroup(layers);
      map.fitBounds(group.getBounds().pad(0.12));
    }

    function selectRegion(code, fit) {
      state.selectedCode = code ? String(code) : null;
      refreshRegionStyles();
      renderPanel(state.selectedCode);
      updateMarkers(state.selectedCode);
      if (fit && state.selectedCode) {
        var metaType =
          state.meta &&
          state.meta.regions &&
          state.meta.regions[state.selectedCode] &&
          state.meta.regions[state.selectedCode].type;
        if (metaType === 'metropole') {
          fitToRegion(state.selectedCode);
        }
      }
    }

    function resetSelection() {
      state.selectedCode = null;
      refreshRegionStyles();
      renderPanel(null);
      updateMarkers(null);
      if (state.meta) {
        map.setView(state.meta.metropoleCenter, state.meta.metropoleZoom);
      }
      closePanel();
    }

    function insetRegionCode(inset) {
      return String(inset.regionCode || inset.code);
    }

    function buildInsets() {
      if (!state.meta || !state.meta.insets || !insetsEl) {
        return;
      }

      insetsEl.innerHTML = '';
      state.insetMaps = [];

      state.meta.insets.forEach(function (inset) {
        var geoCode = String(inset.code);
        var regionCode = insetRegionCode(inset);
        var wrap = document.createElement('div');
        wrap.className = 'jce-map-inset';
        wrap.setAttribute('data-code', geoCode);
        wrap.setAttribute('data-region', regionCode);
        Object.keys(inset.position || {}).forEach(function (key) {
          wrap.style[key] = inset.position[key];
        });

        var label = document.createElement('div');
        label.className = 'jce-map-inset__label';
        label.textContent = inset.label;
        wrap.appendChild(label);

        var mapDiv = document.createElement('div');
        mapDiv.className = 'jce-map-inset__map';
        wrap.appendChild(mapDiv);
        insetsEl.appendChild(wrap);

        var insetMap = L.map(mapDiv, {
          zoomControl: false,
          attributionControl: false,
          dragging: false,
          scrollWheelZoom: false,
          doubleClickZoom: false,
          boxZoom: false,
          keyboard: false,
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
          subdomains: 'abcd',
          maxZoom: 18,
        }).addTo(insetMap);

        insetMap.setView(inset.center, inset.zoom);

        var feature = (state.geojson.features || []).find(function (f) {
          return String(f.properties.code) === geoCode;
        });

        if (feature) {
          var layer = L.geoJSON(feature, {
            style: function () {
              return regionStyle(feature, state.selectedCode === regionCode);
            },
            onEachFeature: function (feat, lyr) {
              lyr.on('click', function () {
                selectRegion(regionCode, false);
              });
            },
          }).addTo(insetMap);
          try {
            insetMap.fitBounds(layer.getBounds().pad(0.2));
          } catch (err) {
            // keep setView
          }
          state.regionLayers[regionCode] = state.regionLayers[regionCode] || [];
          layer.eachLayer(function (lyr) {
            state.regionLayers[regionCode].push(lyr);
          });
        }

        wrap.addEventListener('click', function () {
          selectRegion(regionCode, false);
        });

        state.insetMaps.push(insetMap);
      });

      setTimeout(function () {
        state.insetMaps.forEach(function (m) {
          m.invalidateSize();
        });
        map.invalidateSize();
      }, 100);
    }

    function mountGeo() {
      // Metropole polygons only on the main map; DROM stay in insets.
      var metroCodes = Object.keys(state.meta.regions || {}).filter(function (code) {
        return state.meta.regions[code].type === 'metropole';
      });
      var metroFeatures = filterGeoJson(state.geojson, metroCodes);

      L.geoJSON(metroFeatures, {
        style: function (feature) {
          return regionStyle(feature, false);
        },
        onEachFeature: onEachFeature,
      }).addTo(map);

      map.setView(state.meta.metropoleCenter, state.meta.metropoleZoom);
      buildInsets();
      updateMarkers(null);

      if (initialRegion) {
        selectRegion(initialRegion, true);
      } else {
        renderPanel(null);
      }
    }

    panelBody.innerHTML =
      '<p class="jce-map-panel__placeholder">' + escapeHtml(i18n.loading || '…') + '</p>';

    Promise.all([
      fetchJson(jceMapData.geojsonUrl),
      fetchJson(jceMapData.metaUrl),
      fetchJson(jceMapData.restUrl),
    ])
      .then(function (results) {
        state.geojson = results[0];
        state.meta = results[1];
        state.locales = Array.isArray(results[2]) ? results[2] : [];
        mountGeo();
      })
      .catch(function () {
        panelBody.innerHTML =
          '<p class="jce-map-panel__empty">' + escapeHtml(i18n.error || 'Error') + '</p>';
      });

    if (resetBtn) {
      resetBtn.addEventListener('click', resetSelection);
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', closePanel);
    }

    window.addEventListener('resize', function () {
      map.invalidateSize();
      state.insetMaps.forEach(function (m) {
        m.invalidateSize();
      });
    });
  }

  function boot() {
    var roots = document.querySelectorAll('.jce-map-app');
    roots.forEach(function (root) {
      if (root.getAttribute('data-jce-ready')) {
        return;
      }
      root.setAttribute('data-jce-ready', '1');
      createMapApp(root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
