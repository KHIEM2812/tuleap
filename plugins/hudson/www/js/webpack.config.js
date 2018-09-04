/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

const path = require("path");
const webpack_configurator = require("../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../../../../src/www/assets/hudson/scripts");
const manifest_plugin = webpack_configurator.getManifestPlugin();

module.exports = {
    entry: {
        "test-results-pie": "./test-results-pie-chart.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    resolve: {
        modules: [path.resolve(__dirname, "node_modules")],
        alias: {
            "charts-builders": path.resolve(
                __dirname,
                "../../../../src/www/scripts/charts-builders/"
            )
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};
