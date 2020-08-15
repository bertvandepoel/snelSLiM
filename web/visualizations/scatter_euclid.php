<?php
/*
 * snelSLiM - Interface for quick Stable Lexical Marker Analysis
 * Copyright (c) 2017-2020 Bert Van de Poel
 * Under superivison of Prof. Dr. Dirk Speelman
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>. 
 */


if(!isset($_GET['forcemds']) AND substr_count($c1frag, "\n") + substr_count($c2frag, "\n") > 500) {
	$uri = explode('?', $_SERVER['REQUEST_URI']);
	$uri = '?' . $uri[1];
	$uri .= '&forcemds=';
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-info"><strong>Info</strong> The target and reference corpus have a combined size of over 500 files. This may result in your browser taking a long time to load this visualization and may not actually succeed. Click <a href="' . $uri . '">here</a> if you would still like to generate this visualization.</div></div></div>';
}
else {
?>
<div class="col-md-12"><h4>Legend: &nbsp; <span class="label label-primary" style="background-color: #4682b4aa;">corpus A</span> <span class="label label-primary" style="background-color: #d4c64daa;">corpus B</span><?php if(isset($_GET['fragvis'])){ echo ' <span class="label label-primary" style="background-color: #ff0000aa;">selected file</span>'; }?></h4></div>
<div id="scatter_euclid"></div>
<script>
<?php echo file_get_contents('../data/reports/' . $report['id'] . '/distancematrix.js'); ?>

var mdserror = false;
try {
	var coordinates = mds.classic(distancematrix);
} catch (e) {
	mdserror = true;
}
if(mdserror) {
	document.getElementById('scatter_euclid').innerHTML = '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-warning"><strong>Warning</strong> This graph could not be generated because the euclidean distances don\'t converge to 2D space for this combination of corpora and report parameters.</div></div></div>';
}
else {
	var euclid_values = []
	for (var i = 0; i < distancelabels.length; i++) {
		euclid_values.push({"label": distancelabels[i].filename, "xcoor": coordinates[i][0], "ycoor": coordinates[i][1], "corpus": distancelabels[i].corpus})
	}
	var spec2 = {
	  "$schema": "https://vega.github.io/schema/vega/v5.json",
	  "width": 1100,
	  "height": 500,
	  "padding": 2.5,
	  "autosize": "pad",

	  "data": [
		{
		  "name": "euclid",
		  "values": euclid_values,
		  
		},
		{
		  "name": "corpusA_euclid",
		  "source": "euclid",
		  "transform": [
		    { "type": "filter", "expr": "datum.corpus == 'A'" }
		  ]
		},
		{
		  "name": "corpusB_euclid",
		  "source": "euclid",
		  "transform": [
		    { "type": "filter", "expr": "datum.corpus == 'B'" }
		  ]
		}<?php
			if(isset($_GET['fragvis'])) {
				echo ',{
		  "name": "selected_file",
		  "source": "euclid",
		  "transform": [
		    { "type": "filter", "expr": "datum.label == \'' . $_GET['fragvis'] . '\'" }
		  ]
		}';
			}
		?>
	  ],
	  "axes": [
		{
		  "scale": "x",
		  "grid": true,
		  "domain": false,
		  "orient": "bottom",
		  "labels": false,
		  "ticks": false
		},
		{
		  "scale": "y",
		  "grid": true,
		  "domain": false,
		  "orient": "left",
		  "labels": false,
		  "ticks": false
		}
	  ],
	  "scales": [
		{
		  "name": "x",
		  "type": "linear",
		  "domain": {"data": "euclid", "field": "xcoor"},
		  "range": "width",
		  "nice": true
		},
		{
		  "name": "y",
		  "type": "linear",
		  "domain": {"data": "euclid", "field": "ycoor"},
		  "range": "height",
		  "nice": true
		},
		{
		  "name": "color",
		  "type": "ordinal",
		  "domain": ["A", "B"],
		  "range": ["#4682b4", "#d4c64d"]
		},
	  ],
	  "marks": [
		{
		  "name": "corpusB_marks",
		  "type": "symbol",
		  "from": {"data": "corpusB_euclid"},
		  "encode": {
		    "enter": {
		      "tooltip": {"signal": "datum.label"}
		    },
		    "update": {
		      "x": {"scale": "x", "field": "xcoor"},
		      "y": {"scale": "y", "field": "ycoor"},
		      "shape": {"value": "circle"},
		      "strokeWidth": {"value": 2},
		      "opacity": {"value": 0.5},
		      "stroke": {"scale": "color", "field": "corpus"},
		      "fill": {"scale": "color", "field": "corpus"}
		    },
		    "hover": {
		      "stroke": {"value": "#f00"}
		    }
		  }
		},
		{
		  "name": "corpusA_marks",
		  "type": "symbol",
		  "from": {"data": "corpusA_euclid"},
		  "encode": {
		    "enter": {
		      "href": {"signal": "'?reportid=<?php echo $report['id']; ?>&fragvis='+datum.label+'<?php echo $report['linksuffix']; ?>'"},
		      "cursor": {"value": "pointer"},
		      "tooltip": {"signal": "datum.label"}
		    },
		    "update": {
		      "x": {"scale": "x", "field": "xcoor"},
		      "y": {"scale": "y", "field": "ycoor"},
		      "shape": {"value": "circle"},
		      "strokeWidth": {"value": 2},
		      "opacity": {"value": 0.5},
		      "stroke": {"scale": "color", "field": "corpus"},
		      "fill": {"scale": "color", "field": "corpus"}
		    },
		    "hover": {
		      "stroke": {"value": "#f00"}
		    }
		  }
		}<?php
			if(isset($_GET['fragvis'])) {
				echo ',{
		  "name": "selected_mark",
		  "type": "symbol",
		  "from": {"data": "selected_file"},
		  "encode": {
		    "enter": {
		      "tooltip": {"signal": "datum.label"}
		    },
		    "update": {
		      "x": {"scale": "x", "field": "xcoor"},
		      "y": {"scale": "y", "field": "ycoor"},
		      "shape": {"value": "circle"},
		      "strokeWidth": {"value": 2},
		      "opacity": {"value": 0.5},
		      "stroke": {"value": "#f00"},
		      "fill": {"value": "#f00"}
		    }
		  }
		}';
			}
		?>
	  ]
	}

	var handler = new vegaTooltip.Handler();
	var view = new vega.View(vega.parse(spec2), {
	  loader: vega.loader({target: '_blank'}),
	  logLevel: vega.Warn,
	  renderer: 'svg',
	  tooltip: handler.call
	}).initialize('#scatter_euclid').hover().run();
}
</script>
<?php
}
