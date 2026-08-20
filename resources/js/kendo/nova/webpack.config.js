const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CompressionPlugin = require("compression-webpack-plugin");
const fs = require("fs");
const CopyPlugin = require("copy-webpack-plugin");
const webpack = require("webpack");

const pages = (() => {
    // Lookup all pages/<page-name>.html and corresponding src/<page-name>.js files,
    // and create an entry point javascript with an html template for its page.
    //
    // As fallback, allow code and templates to be placed side by side:
    //  - pages/<page-name>.html and pages/<page-name>.js combinations
    //  - src/<page-name>.html and src/<page-name>.js
    const isHtml = filename => path.parse(filename)?.ext === ".html";
    const isFileInDir = dir => filename => fs.statSync(path.join(dir, filename)).isFile()
    const listHtmlFilesInDir = dir => fs.readdirSync(`./${dir}`).filter(isHtml).filter(isFileInDir(dir));

    const listPagesInDir = dir => listHtmlFilesInDir(dir).map(filename => {
        const name = path.parse(filename).name;
        const entry = fs.existsSync(`./${dir}/${name}.js`) ? `./${dir}/${name}.js` : `./src/${name}.js`;
        const template = `./${dir}/${filename}`;
        return { filename, name, entry, template };
    });

    return [...listPagesInDir("pages"), ...listPagesInDir("src")];
})();

module.exports = {
    mode: "development",
    entry: pages.reduce((config, { name, entry }) => ({ ...config, [name]: entry }), {}),
    output: {
        filename: "js/[name].min.js",
        path: path.resolve(__dirname, "dist"),
    },
    devServer: {
        static: "./dist",
    },
    module: {
        rules: [           
            {
                test: /\.css$/,
                exclude: /node_modules/,
                use: [
                    "style-loader",
                    "css-loader"
                ]
            }
        ]
    },
    plugins: [].concat(
        pages.map(
            ({filename, template, name }) =>
                new HtmlWebpackPlugin({ inject: "head", template, filename, chunks: [name] })
        ),
        new CompressionPlugin({
            algorithm: "gzip",
            test: /\.(js|css)/
        }),
        new CopyPlugin({
            patterns: [{ from: "assets", to: "assets" }]
        }),
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery"
        })
    ),
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                parallel: true,
                extractComments: false,
            }),
        ],
        splitChunks: {
            chunks: 'all',
            minSize: 20000,
        },
    }
};