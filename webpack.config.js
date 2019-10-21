const { join, basename } = require( 'path' );

const baseDir = __dirname;

module.exports = function( env = { environment: "production", watch: false, buildTarget: false } ) {
	if ( ! env.watch ) {
		env.watch = false;
	}

    const mode = env.environment

    var externals = {
		react: 'React',
		'react-dom': 'ReactDOM',
		tinymce: 'tinymce',
		//moment: 'moment',
		jquery: 'jQuery',
		lodash: 'lodash',
		'lodash-es': 'lodash',
		/*'@wordpress/hooks': {
			this: [ 'wp', 'hooks' ],
		}*/
	};

	config = {
		mode,

		entry: {
            'license-field': join( baseDir, `js/settings/licenses.jsx` ),
        },
        output: {
            filename: '[name].js',
            path: join( baseDir, `/js/dist` ),
        },
		externals,
		resolve: {
            extensions: ['.ts', '.js', '.jsx'],
			modules: [
				baseDir,
				'node_modules',
			]
        },
        module: {
			rules: [
                {
                    test: /\.jsx?$/,
                    //exclude: /node_modules/,
                    use: [
                      {
                        loader: 'babel-loader',
                        options: {
                          presets: ['@babel/react']
                        }
                      }
                    ],
				},
				{
                    test: /\.css?$/,
                    use:['style-loader','css-loader']
				},
				{
					test: /\.scss$/,
					use: [
						"style-loader", // creates style nodes from JS strings
						"css-loader", // translates CSS into CommonJS
						"sass-loader" // compiles Sass to CSS, using Node Sass by default
					]
				}
			],
		},
		plugins: [
		],
		stats: {
			children: false,
		},

		watch: env.watch,
	};

	if ( config.mode !== 'production' ) {
		config.devtool = process.env.SOURCEMAP || 'source-map';
	}

    config.optimization = {
        minimize: false
    };

	return config;
};
