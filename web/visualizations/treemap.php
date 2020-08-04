<?php
/*
 * snelSLiM - Interface for quick Stable Lexical Marker Analysis
 * Copyright (c) 2017-2019 Bert Van de Poel
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


//calculate height
$numfiles = substr_count($c1frag, "\n");
$height = $numfiles * 15;
if($height < 300) {
	$height = 300;
}

?>
<div id="treemapbindings" class="form-group col-md-4"></div><div class="col-md-offset-8"></div>
<div class="col-md-12"><h4><span class="label label-primary" style="background-color: #4b4bff;">Attraction</span> <span class="label label-danger">Repulsion</span> <span class="label label-default">Balanced</span></h4></div>
<div id="treemap"></div>
<script>
var spec = {
  "$schema": "https://vega.github.io/schema/vega/v5.json",
  "width": 1100,
  "height": <?php echo $height; ?>,
  "padding": 2.5,
  "autosize": "pad",
  
  "signals": [
    {
      "name": "sizefield", 
      "value": "Total amount of markers",
      "bind": {
      	"name": "What is the size of elements based on?",
        "input": "select",
        "options": ["Total amount of markers", "Number of unique markers", "Percentage of the file that is a markers", "Ratio of unique markers against the total size"]
      },
      "update": "scale('translate_sizefield',sizefield)"
    },
    {
      "name": "association", 
      "value": "Total amount of markers per category",
      "bind": {
      	"name": "What is the categorisation (color) of files based on?",
        "input": "select",
        "options": ["Total amount of markers per category", "Number of unique markers per category"]
      },
      "update": "scale('translate_association',association)"
    }
  ],

  "data": [
    {
      "name": "tree",
      "url": "?json=&reportid=<?php echo $report['id'] . $report['linksuffix']; ?>",
      "transform": [
        {
          "type": "stratify", 
          "key": "id",
          "parentKey": {"signal": "association"}
        },
        {
          "type": "treemap",
          "field": {"signal": "sizefield"},
          "sort": {"field": "value"},
          "round": true,
          "method": "binary",
          "size": [{"signal": "width"}, {"signal": "height"}]
        }
      ]
    },
    {
      "name": "nodes",
      "source": "tree",
      "transform": [{ "type": "filter", "expr": "datum.children" }]
    },
    {
      "name": "leaves",
      "source": "tree",
      "transform": [{ "type": "filter", "expr": "!datum.children" }]
    }
  ],

  "scales": [
    {
      "name": "color",
      "type": "ordinal",
      "domain": {"data": "nodes", "field": "name"}, 
      "range": [
        "white", "#4b4bff", "#ff4b4b", "#999999"
      ]
    },
    {
      "name": "size",
      "type": "linear",
      "domain": [0, 1, 2],
      "range": [0, 0, 13]
    },
    {
      "name": "translate_sizefield",
      "type": "ordinal",
      "domain": ["Total amount of markers", "Number of unique markers", "Percentage of the file that is a markers", "Ratio of unique markers against the total size"],
      "range": ["size_keyword_total", "size_keyword_unique", "size_keyword_percentage_total", "size_keyword_percentage_unique"]
    },
    {
      "name": "translate_association",
      "type": "ordinal",
      "domain": ["Total amount of markers per category", "Number of unique markers per category"],
      "range": ["parent_total", "parent_unique"]
    }
  ],

  "marks": [
    {
      "type": "rect",
      "from": {"data": "nodes"},
      "interactive": false,
      "encode": {
        "update": {
          "x": {"field": "x0"},
          "y": {"field": "y0"},
          "x2": {"field": "x1"},
          "y2": {"field": "y1"},
          "fill": {"scale": "color", "field": "name"}
        }
      }
    },
    {
      "name": "leafrects",
      "type": "rect",
      "from": {"data": "leaves"},
      "encode": {
        "enter": {
          "stroke": {"value": "#fff"},
          "href": {"signal": "'?reportid=<?php echo $report['id']; ?>&fragvis='+datum.name+'<?php echo $report['linksuffix']; ?>'"},
          "cursor": {"value": "pointer"},
          "tooltip": {"signal": "datum.name"}
        },
        "update": {
          "x": {"field": "x0"},
          "y": {"field": "y0"},
          "x2": {"field": "x1"},
          "y2": {"field": "y1"},
          "fill": {"value": "transparent"}
        },
        "hover": {
          "fill": {"value": "#d07fff"}
        }
      }
    },
    {
      "type": "text",
      "from": {"data": "leaves"},
      "interactive": false,
      "encode": {
        "enter": {
          "clip": {"value": true},
          "font": {"value": "Source Sans Pro, Helvetica Neue, Helvetica, Arial, sans-serif"},
          "align": {"value": "center"},
          "baseline": {"value": "middle"},
          "fill": {"value": "white"},
          "text": {"field": "name"},
          "fontSize": {"scale": "size", "field": "depth"}
        },
        "update": {
          "x": {"signal": "0.5 * (datum.x0 + datum.x1)"},
          "y": {"signal": "0.5 * (datum.y0 + datum.y1)"},
          "limit": {"signal": "datum.x1 - datum.x0"},
        }
      }
    }
  ]
}

var view = new vega.View(vega.parse(spec), {
  loader: vega.loader({target: '_blank'}),
  logLevel: vega.Warn,
  renderer: 'svg'
}).initialize('#treemap', '#treemapbindings').hover().run();
</script>

