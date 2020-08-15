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


?>
<div class="col-md-12"><h4>Legend: &nbsp; <span class="label label-primary" style="background-color: #4682b4aa;">corpus A</span> <span class="label label-primary" style="background-color: #d4c64daa;">corpus B</span><?php if(isset($_GET['fragvis'])){ echo ' <span class="label label-primary" style="background-color: #ff0000aa;">selected file</span>'; }?></h4></div>
<?php
$prototype_coordinates = file_get_contents('../data/reports/' . $report['id'] . '/visuals/prototype_coordinates.json');
if(substr_count($prototype_coordinates, '"filename":')/2 <= substr_count($prototype_coordinates, 'corpus":"error"')) {
	echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-warning"><strong>Warning</strong> This graph could not be generated because the resulting numbers were too small to express. This is usually caused by very sparse matrices stemming from issues with corpus quality.</div></div></div>';
}
else {
	if(substr_count($prototype_coordinates, 'corpus":"error"') > 0) {
		echo '<div class="row"><div class="col-md-6 col-md-offset-3"><div class="alert alert-info"><strong>Info</strong> For some files, but not a majority, it was impossible to generate coordinates because the resulting numbers were too small to express. This is usually caused by very sparse matrices stemming from issues with corpus quality. It may be necessary to review those files that were omitted from this graph.</div></div></div>';
	}
	?>
	<div id="scatter_prototype"></div>
	<script>
	var spec3 = {
	  "$schema": "https://vega.github.io/schema/vega/v5.json",
	  "width": 1100,
	  "height": 500,
	  "padding": 2.5,
	  "autosize": "pad",

	  "data": [
		{
		  "name": "prototype",
		  "values": <?php echo $prototype_coordinates; ?>,
		},
		{
		  "name": "corpusA_prototype",
		  "source": "prototype",
		  "transform": [
			{ "type": "filter", "expr": "datum.corpus == 'A'" }
		  ]
		},
		{
		  "name": "corpusB_prototype",
		  "source": "prototype",
		  "transform": [
			{ "type": "filter", "expr": "datum.corpus == 'B'" }
		  ]
		}<?php
			if(isset($_GET['fragvis'])) {
				echo ',{
		  "name": "selected_file",
		  "source": "prototype",
		  "transform": [
			{ "type": "filter", "expr": "datum.filename == \'' . $_GET['fragvis'] . '\'" }
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
		  "title": "Prototypically corpus B"
		},
		{
		  "scale": "y",
		  "grid": true,
		  "domain": false,
		  "orient": "left",
		  "title": "Prototypically corpus A"
		}
	  ],
	  "scales": [
		{
		  "name": "x",
		  "type": "linear",
		  "domain": {"data": "prototype", "field": "xcoor"},
		  "range": "width",
		  "nice": true
		},
		{
		  "name": "y",
		  "type": "linear",
		  "domain": {"data": "prototype", "field": "ycoor"},
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
		  "from": {"data": "corpusB_prototype"},
		  "encode": {
			"enter": {
			  "tooltip": {"signal": "datum.filename"}
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
		  "from": {"data": "corpusA_prototype"},
		  "encode": {
			"enter": {
			  "href": {"signal": "'?reportid=<?php echo $report['id']; ?>&fragvis='+datum.filename+'<?php echo $report['linksuffix']; ?>'"},
			  "cursor": {"value": "pointer"},
			  "tooltip": {"signal": "datum.filename"}
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
			  "tooltip": {"signal": "datum.filename"}
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

	var view = new vega.View(vega.parse(spec3), {
	  loader: vega.loader({target: '_blank'}),
	  logLevel: vega.Warn,
	  renderer: 'svg',
	  tooltip: handler.call
	}).initialize('#scatter_prototype').hover().run();
	</script>
	<?php
}
