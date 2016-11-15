/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
(function() {
    initializePieCharts();

    function initializePieCharts() {
        var pie_chart_elements = document.getElementsByClassName('siteadmin-homepage-pie-chart');

        [].forEach.call(pie_chart_elements, function(pie_chart_element) {
            var pie_chart_element_sizes = getSizes(pie_chart_element);
            var options = {
                graph_id: pie_chart_element.id,
                data    : JSON.parse(pie_chart_element.dataset.statistics),
                width   : pie_chart_element_sizes.width,
                height  : pie_chart_element_sizes.height,
                radius  : pie_chart_element_sizes.radius
            };

            var pie_chart = tuleap.admin.statistictsPieChart(options);
            pie_chart();

            window.addEventListener('resize', function() {
                updateSizes(pie_chart, pie_chart_element);
                pie_chart.redraw();
            });
        });
    }

    function getSizes(element) {
        var client_rect_width = element.getBoundingClientRect().width,
            width             = client_rect_width / 2 - 50 < 110 ? 110 : client_rect_width / 2 - 50,
            height            = client_rect_width / 2 - 50 < 110 ? 110 : client_rect_width / 2 - 50,
            radius            = Math.min(width, height);

        return {width: width, height: height, radius: radius};
    }

    function updateSizes(chart, element) {
        var sizes = getSizes(element);

        chart.width(sizes.width);
        chart.height(sizes.height);
        chart.radius(sizes.radius);
    }
} ());