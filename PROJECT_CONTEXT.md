# Laravel School ERP - Unified Architectural Context
*Generated on: Tue Oct  6 10:18:45 CAT 2026*\n
## 1. Directory Structure
```
.
./school-system-frontend
./school-system-frontend/tsconfig.node.json
./school-system-frontend/index.html
./school-system-frontend/tsconfig.app.json
./school-system-frontend/dist
./school-system-frontend/dist/index.html
./school-system-frontend/dist/assets
./school-system-frontend/dist/icons.svg
./school-system-frontend/dist/favicon.svg
./school-system-frontend/node_modules
./school-system-frontend/node_modules/pkce-challenge
./school-system-frontend/node_modules/queue-microtask
./school-system-frontend/node_modules/citty
./school-system-frontend/node_modules/is-docker
./school-system-frontend/node_modules/vscode-uri
./school-system-frontend/node_modules/tinyglobby
./school-system-frontend/node_modules/callsites
./school-system-frontend/node_modules/is-inside-container
./school-system-frontend/node_modules/stringify-object
./school-system-frontend/node_modules/tapable
./school-system-frontend/node_modules/d3-format
./school-system-frontend/node_modules/striptags
./school-system-frontend/node_modules/zod
./school-system-frontend/node_modules/ts-morph
./school-system-frontend/node_modules/reusify
./school-system-frontend/node_modules/jsesc
./school-system-frontend/node_modules/tldts
./school-system-frontend/node_modules/perfect-debounce
./school-system-frontend/node_modules/d3-contour
./school-system-frontend/node_modules/@types
./school-system-frontend/node_modules/murmurhash-js
./school-system-frontend/node_modules/d3-shape
./school-system-frontend/node_modules/is-regexp
./school-system-frontend/node_modules/geojson
./school-system-frontend/node_modules/@dotenvx
./school-system-frontend/node_modules/vee-validate
./school-system-frontend/node_modules/browserslist
./school-system-frontend/node_modules/shebang-regex
./school-system-frontend/node_modules/eventsource
./school-system-frontend/node_modules/path-is-absolute
./school-system-frontend/node_modules/json-parse-even-better-errors
./school-system-frontend/node_modules/is-wsl
./school-system-frontend/node_modules/hookable
./school-system-frontend/node_modules/tailwind-merge
./school-system-frontend/node_modules/remove-accents
./school-system-frontend/node_modules/jiti
./school-system-frontend/node_modules/@emnapi
./school-system-frontend/node_modules/csstype
./school-system-frontend/node_modules/toidentifier
./school-system-frontend/node_modules/stdin-discarder
./school-system-frontend/node_modules/ts-api-utils
./school-system-frontend/node_modules/path-type
./school-system-frontend/node_modules/@rolldown
./school-system-frontend/node_modules/stylis
./school-system-frontend/node_modules/mimic-fn
./school-system-frontend/node_modules/strip-ansi
./school-system-frontend/node_modules/ast-kit
./school-system-frontend/node_modules/lightningcss-darwin-arm64
./school-system-frontend/node_modules/content-type
./school-system-frontend/node_modules/systeminformation
./school-system-frontend/node_modules/@redocly
./school-system-frontend/node_modules/eventsource-parser
./school-system-frontend/node_modules/flatted
./school-system-frontend/node_modules/@volar
./school-system-frontend/node_modules/es-errors
./school-system-frontend/node_modules/js-levenshtein
./school-system-frontend/node_modules/babel-plugin-macros
./school-system-frontend/node_modules/is-obj
./school-system-frontend/node_modules/agent-base
./school-system-frontend/node_modules/@tanstack
./school-system-frontend/node_modules/confbox
./school-system-frontend/node_modules/earcut
./school-system-frontend/node_modules/bundle-name
./school-system-frontend/node_modules/mimic-function
./school-system-frontend/node_modules/he
./school-system-frontend/node_modules/quickselect
./school-system-frontend/node_modules/ms
./school-system-frontend/node_modules/strip-final-newline
./school-system-frontend/node_modules/content-disposition
./school-system-frontend/node_modules/math-intrinsics
./school-system-frontend/node_modules/lodash-es
./school-system-frontend/node_modules/prelude-ls
./school-system-frontend/node_modules/tinyqueue
./school-system-frontend/node_modules/node-releases
./school-system-frontend/node_modules/dotenv
./school-system-frontend/node_modules/escape-string-regexp
./school-system-frontend/node_modules/has-tostringtag
./school-system-frontend/node_modules/lru-cache
./school-system-frontend/node_modules/imurmurhash
./school-system-frontend/node_modules/supercluster
./school-system-frontend/node_modules/d3-array
./school-system-frontend/node_modules/eslint-scope
./school-system-frontend/node_modules/type-fest
./school-system-frontend/node_modules/commander
./school-system-frontend/node_modules/cli-progress
./school-system-frontend/node_modules/punycode
./school-system-frontend/node_modules/proxy-addr
./school-system-frontend/node_modules/is-what
./school-system-frontend/node_modules/depd
./school-system-frontend/node_modules/atomically
./school-system-frontend/node_modules/atob
./school-system-frontend/node_modules/estree-walker
./school-system-frontend/node_modules/autoprefixer
./school-system-frontend/node_modules/postcss-styl
./school-system-frontend/node_modules/escalade
./school-system-frontend/node_modules/@ts-morph
./school-system-frontend/node_modules/ip-address
./school-system-frontend/node_modules/uri-js-replace
./school-system-frontend/node_modules/range-parser
./school-system-frontend/node_modules/vue-metamorph
./school-system-frontend/node_modules/side-channel-list
./school-system-frontend/node_modules/fast-json-stable-stringify
./school-system-frontend/node_modules/error-ex
./school-system-frontend/node_modules/throttle-debounce
./school-system-frontend/node_modules/detect-libc
./school-system-frontend/node_modules/balanced-match
./school-system-frontend/node_modules/path-exists
./school-system-frontend/node_modules/d3-polygon
./school-system-frontend/node_modules/resolve
./school-system-frontend/node_modules/resolve-protobuf-schema
./school-system-frontend/node_modules/bytes
./school-system-frontend/node_modules/@internationalized
./school-system-frontend/node_modules/@eslint
./school-system-frontend/node_modules/index-to-position
./school-system-frontend/node_modules/call-bind-apply-helpers
./school-system-frontend/node_modules/gonzales-pe
./school-system-frontend/node_modules/css-tree
./school-system-frontend/node_modules/html-encoding-sniffer
./school-system-frontend/node_modules/decimal.js
./school-system-frontend/node_modules/define-lazy-prop
./school-system-frontend/node_modules/parse-json
./school-system-frontend/node_modules/tldts-core
./school-system-frontend/node_modules/nanoid
./school-system-frontend/node_modules/acorn
./school-system-frontend/node_modules/css
./school-system-frontend/node_modules/ast-types
./school-system-frontend/node_modules/file-entry-cache
./school-system-frontend/node_modules/rolldown
./school-system-frontend/node_modules/is-potential-custom-element-name
./school-system-frontend/node_modules/pkg-types
./school-system-frontend/node_modules/maplibre-gl
./school-system-frontend/node_modules/express
./school-system-frontend/node_modules/sax
./school-system-frontend/node_modules/postcss-less
./school-system-frontend/node_modules/d3-scale
./school-system-frontend/node_modules/d3-scale-chromatic
./school-system-frontend/node_modules/@nodelib
./school-system-frontend/node_modules/encodeurl
./school-system-frontend/node_modules/signal-exit
./school-system-frontend/node_modules/@mapbox
./school-system-frontend/node_modules/d3-axis
./school-system-frontend/node_modules/once
./school-system-frontend/node_modules/gensync
./school-system-frontend/node_modules/prettier
./school-system-frontend/node_modules/get-own-enumerable-keys
./school-system-frontend/node_modules/object-treeify
./school-system-frontend/node_modules/typescript-eslint
./school-system-frontend/node_modules/d3-color
./school-system-frontend/node_modules/proxy-from-env
./school-system-frontend/node_modules/ignore
./school-system-frontend/node_modules/sisteransi
./school-system-frontend/node_modules/debounce-fn
./school-system-frontend/node_modules/@lucide
./school-system-frontend/node_modules/d3-dispatch
./school-system-frontend/node_modules/esrecurse
./school-system-frontend/node_modules/find-root
./school-system-frontend/node_modules/merge-descriptors
./school-system-frontend/node_modules/reserved-identifiers
./school-system-frontend/node_modules/@hono
./school-system-frontend/node_modules/tslib
./school-system-frontend/node_modules/@tailwindcss
./school-system-frontend/node_modules/magic-string
./school-system-frontend/node_modules/ajv-formats
./school-system-frontend/node_modules/argparse
./school-system-frontend/node_modules/deep-diff
./school-system-frontend/node_modules/picomatch
./school-system-frontend/node_modules/d3-geo
./school-system-frontend/node_modules/exsolve
./school-system-frontend/node_modules/d3-timer
./school-system-frontend/node_modules/lodash.truncate
./school-system-frontend/node_modules/function-bind
./school-system-frontend/node_modules/is-glob
./school-system-frontend/node_modules/npm-run-path
./school-system-frontend/node_modules/@vee-validate
./school-system-frontend/node_modules/postcss-scss
./school-system-frontend/node_modules/ee-first
./school-system-frontend/node_modules/is-fullwidth-code-point
./school-system-frontend/node_modules/env-paths
./school-system-frontend/node_modules/typescript
./school-system-frontend/node_modules/ora
./school-system-frontend/node_modules/flat-cache
./school-system-frontend/node_modules/yargs-parser
./school-system-frontend/node_modules/baseline-browser-mapping
./school-system-frontend/node_modules/ast-walker-scope
./school-system-frontend/node_modules/stylus
./school-system-frontend/node_modules/vue
./school-system-frontend/node_modules/reka-ui
./school-system-frontend/node_modules/d3-time-format
./school-system-frontend/node_modules/inherits
./school-system-frontend/node_modules/jsonfile
./school-system-frontend/node_modules/c12
./school-system-frontend/node_modules/elkjs
./school-system-frontend/node_modules/iconv-lite
./school-system-frontend/node_modules/color-name
./school-system-frontend/node_modules/es-define-property
./school-system-frontend/node_modules/nypm
./school-system-frontend/node_modules/eslint-plugin-vue
./school-system-frontend/node_modules/ufo
./school-system-frontend/node_modules/@swc
./school-system-frontend/node_modules/d3-interpolate
./school-system-frontend/node_modules/chokidar
./school-system-frontend/node_modules/postcss
./school-system-frontend/node_modules/p-locate
./school-system-frontend/node_modules/ohash
./school-system-frontend/node_modules/dom-serializer
./school-system-frontend/node_modules/fresh
./school-system-frontend/node_modules/d3-zoom
./school-system-frontend/node_modules/undici
./school-system-frontend/node_modules/get-intrinsic
./school-system-frontend/node_modules/keyv
./school-system-frontend/node_modules/path-browserify
./school-system-frontend/node_modules/d3-random
./school-system-frontend/node_modules/@typescript-eslint
./school-system-frontend/node_modules/zod-to-json-schema
./school-system-frontend/node_modules/@tybys
./school-system-frontend/node_modules/qs
./school-system-frontend/node_modules/js-yaml
./school-system-frontend/node_modules/eslint-visitor-keys
./school-system-frontend/node_modules/whatwg-url
./school-system-frontend/node_modules/jackspeak
./school-system-frontend/node_modules/call-bound
./school-system-frontend/node_modules/node-fetch-native
./school-system-frontend/node_modules/yocto-spinner
./school-system-frontend/node_modules/destr
./school-system-frontend/node_modules/unplugin-utils
./school-system-frontend/node_modules/css-select
./school-system-frontend/node_modules/parent-module
./school-system-frontend/node_modules/@humanwhocodes
./school-system-frontend/node_modules/function-timeout
./school-system-frontend/node_modules/yaml-ast-parser
./school-system-frontend/node_modules/combined-stream
./school-system-frontend/node_modules/pluralize
./school-system-frontend/node_modules/dunder-proto
./school-system-frontend/node_modules/d3-time
./school-system-frontend/node_modules/path-to-regexp
./school-system-frontend/node_modules/hasown
./school-system-frontend/node_modules/safer-buffer
./school-system-frontend/node_modules/side-channel-weakmap
./school-system-frontend/node_modules/d3-selection
./school-system-frontend/node_modules/is-promise
./school-system-frontend/node_modules/deepmerge
./school-system-frontend/node_modules/run-parallel
./school-system-frontend/node_modules/p-limit
./school-system-frontend/node_modules/diff
./school-system-frontend/node_modules/nprogress
./school-system-frontend/node_modules/mime-types
./school-system-frontend/node_modules/undici-types
./school-system-frontend/node_modules/wsl-utils
./school-system-frontend/node_modules/tiny-invariant
./school-system-frontend/node_modules/webidl-conversions
./school-system-frontend/node_modules/json-schema-traverse
./school-system-frontend/node_modules/@isaacs
./school-system-frontend/node_modules/fuzzysort
./school-system-frontend/node_modules/natural-compare
./school-system-frontend/node_modules/type-is
./school-system-frontend/node_modules/minimist
./school-system-frontend/node_modules/xml-name-validator
./school-system-frontend/node_modules/@unovue
./school-system-frontend/node_modules/fraction.js
./school-system-frontend/node_modules/consola
./school-system-frontend/node_modules/code-block-writer
./school-system-frontend/node_modules/fast-diff
./school-system-frontend/node_modules/pkg-up
./school-system-frontend/node_modules/is-stream
./school-system-frontend/node_modules/muggle-string
./school-system-frontend/node_modules/universalify
./school-system-frontend/node_modules/onetime
./school-system-frontend/node_modules/@asamuzakjp
./school-system-frontend/node_modules/esutils
./school-system-frontend/node_modules/@vue
./school-system-frontend/node_modules/find-up
./school-system-frontend/node_modules/chalk
./school-system-frontend/node_modules/giget
./school-system-frontend/node_modules/@vitejs
./school-system-frontend/node_modules/@csstools
./school-system-frontend/node_modules/ansi-regex
./school-system-frontend/node_modules/enhanced-resolve
./school-system-frontend/node_modules/aria-hidden
./school-system-frontend/node_modules/jose
./school-system-frontend/node_modules/make-asynchronous
./school-system-frontend/node_modules/supports-color
./school-system-frontend/node_modules/@exodus
./school-system-frontend/node_modules/fdir
./school-system-frontend/node_modules/vary
./school-system-frontend/node_modules/delaunator
./school-system-frontend/node_modules/fs-extra
./school-system-frontend/node_modules/supports-preserve-symlinks-flag
./school-system-frontend/node_modules/three
./school-system-frontend/node_modules/color-convert
./school-system-frontend/node_modules/path-key
./school-system-frontend/node_modules/merge-stream
./school-system-frontend/node_modules/super-regex
./school-system-frontend/node_modules/readdirp
./school-system-frontend/node_modules/unpipe
./school-system-frontend/node_modules/brace-expansion
./school-system-frontend/node_modules/@vuedx
./school-system-frontend/node_modules/foreground-child
./school-system-frontend/node_modules/fill-range
./school-system-frontend/node_modules/jsdom
./school-system-frontend/node_modules/json-stable-stringify-without-jsonify
./school-system-frontend/node_modules/css-what
./school-system-frontend/node_modules/leaflet
./school-system-frontend/node_modules/astral-regex
./school-system-frontend/node_modules/util-deprecate
./school-system-frontend/node_modules/word-wrap
./school-system-frontend/node_modules/saxes
./school-system-frontend/node_modules/path-parse
./school-system-frontend/node_modules/time-span
./school-system-frontend/node_modules/json-schema-typed
./school-system-frontend/node_modules/has-symbols
./school-system-frontend/node_modules/clsx
./school-system-frontend/node_modules/kind-of
./school-system-frontend/node_modules/picocolors
./school-system-frontend/node_modules/gl-matrix
./school-system-frontend/node_modules/protocol-buffers-schema
./school-system-frontend/node_modules/ansi-colors
./school-system-frontend/node_modules/ieee754
./school-system-frontend/node_modules/@napi-rs
./school-system-frontend/node_modules/geojson-vt
./school-system-frontend/node_modules/json-buffer
./school-system-frontend/node_modules/change-case
./school-system-frontend/node_modules/default-browser-id
./school-system-frontend/node_modules/d3-drag
./school-system-frontend/node_modules/raw-body
./school-system-frontend/node_modules/scule
./school-system-frontend/node_modules/lines-and-columns
./school-system-frontend/node_modules/semver
./school-system-frontend/node_modules/unplugin
./school-system-frontend/node_modules/ini
./school-system-frontend/node_modules/hono
./school-system-frontend/node_modules/topojson-client
./school-system-frontend/node_modules/http-errors
./school-system-frontend/node_modules/rfdc
./school-system-frontend/node_modules/d3-sankey
./school-system-frontend/node_modules/vite
./school-system-frontend/node_modules/rc9
./school-system-frontend/node_modules/d3-delaunay
./school-system-frontend/node_modules/minimatch
./school-system-frontend/node_modules/robust-predicates
./school-system-frontend/node_modules/vue-sonner
./school-system-frontend/node_modules/d3-hierarchy
./school-system-frontend/node_modules/@modelcontextprotocol
./school-system-frontend/node_modules/postcss-value-parser
./school-system-frontend/node_modules/d3-fetch
./school-system-frontend/node_modules/accepts
./school-system-frontend/node_modules/class-variance-authority
./school-system-frontend/node_modules/estraverse
./school-system-frontend/node_modules/boolbase
./school-system-frontend/node_modules/superjson
./school-system-frontend/node_modules/ansi-styles
./school-system-frontend/node_modules/is-core-module
./school-system-frontend/node_modules/vt-pbf
./school-system-frontend/node_modules/mlly
./school-system-frontend/node_modules/fast-uri
./school-system-frontend/node_modules/cookie-signature
./school-system-frontend/node_modules/forwarded
./school-system-frontend/node_modules/js-tokens
./school-system-frontend/node_modules/table
./school-system-frontend/node_modules/xmlchars
./school-system-frontend/node_modules/d3-geo-projection
./school-system-frontend/node_modules/negotiator
./school-system-frontend/node_modules/body-parser
./school-system-frontend/node_modules/acorn-jsx
./school-system-frontend/node_modules/recast-x
./school-system-frontend/node_modules/tr46
./school-system-frontend/node_modules/@babel
./school-system-frontend/node_modules/data-urls
./school-system-frontend/node_modules/tw-animate-css
./school-system-frontend/node_modules/is-number
./school-system-frontend/node_modules/d3
./school-system-frontend/node_modules/fs.realpath
./school-system-frontend/node_modules/@humanfs
./school-system-frontend/node_modules/express-rate-limit
./school-system-frontend/node_modules/levn
./school-system-frontend/node_modules/get-east-asian-width
./school-system-frontend/node_modules/yocto-queue
./school-system-frontend/node_modules/vue-tsc
./school-system-frontend/node_modules/postcss-selector-parser
./school-system-frontend/node_modules/d3-collection
./school-system-frontend/node_modules/mdn-data
./school-system-frontend/node_modules/d3-interpolate-path
./school-system-frontend/node_modules/side-channel
./school-system-frontend/node_modules/concat-map
./school-system-frontend/node_modules/pinia
./school-system-frontend/node_modules/json5
./school-system-frontend/node_modules/ofetch
./school-system-frontend/node_modules/cors
./school-system-frontend/node_modules/@formkit
./school-system-frontend/node_modules/get-stream
./school-system-frontend/node_modules/yoctocolors
./school-system-frontend/node_modules/update-browserslist-db
./school-system-frontend/node_modules/lodash.sortedlastindex
./school-system-frontend/node_modules/restore-cursor
./school-system-frontend/node_modules/serve-static
./school-system-frontend/node_modules/potpack
./school-system-frontend/node_modules/optionator
./school-system-frontend/node_modules/convert-source-map
./school-system-frontend/node_modules/uri-js
./school-system-frontend/node_modules/is-arrayish
./school-system-frontend/node_modules/@unovis
./school-system-frontend/node_modules/prompts
./school-system-frontend/node_modules/object-assign
./school-system-frontend/node_modules/d3-path
./school-system-frontend/node_modules/whatwg-mimetype
./school-system-frontend/node_modules/vue-eslint-parser
./school-system-frontend/node_modules/is-identifier
./school-system-frontend/node_modules/get-proto
./school-system-frontend/node_modules/form-data
./school-system-frontend/node_modules/slice-ansi
./school-system-frontend/node_modules/package-json-from-dist
./school-system-frontend/node_modules/yaml
./school-system-frontend/node_modules/d3-transition
./school-system-frontend/node_modules/delayed-stream
./school-system-frontend/node_modules/cross-spawn
./school-system-frontend/node_modules/p-event
./school-system-frontend/node_modules/domelementtype
./school-system-frontend/node_modules/convert-hrtime
./school-system-frontend/node_modules/eslint-config-prettier
./school-system-frontend/node_modules/asynckit
./school-system-frontend/node_modules/d3-force
./school-system-frontend/node_modules/ipaddr.js
./school-system-frontend/node_modules/espree
./school-system-frontend/node_modules/eslint
./school-system-frontend/node_modules/glob
./school-system-frontend/node_modules/esquery
./school-system-frontend/node_modules/import-fresh
./school-system-frontend/node_modules/kdbush
./school-system-frontend/node_modules/cookie
./school-system-frontend/node_modules/fast-levenshtein
./school-system-frontend/node_modules/to-regex-range
./school-system-frontend/node_modules/source-map
./school-system-frontend/node_modules/default-browser
./school-system-frontend/node_modules/magic-string-ast
./school-system-frontend/node_modules/d3-chord
./school-system-frontend/node_modules/openapi-typescript
./school-system-frontend/node_modules/@floating-ui
./school-system-frontend/node_modules/cosmiconfig
./school-system-frontend/node_modules/gopd
./school-system-frontend/node_modules/is-unicode-supported
./school-system-frontend/node_modules/escape-html
./school-system-frontend/node_modules/run-applescript
./school-system-frontend/node_modules/statuses
./school-system-frontend/node_modules/https-proxy-agent
./school-system-frontend/node_modules/d3-brush
./school-system-frontend/node_modules/local-pkg
./school-system-frontend/node_modules/string-width
./school-system-frontend/node_modules/is-interactive
./school-system-frontend/node_modules/symbol-tree
./school-system-frontend/node_modules/minipass
./school-system-frontend/node_modules/enquirer
./school-system-frontend/node_modules/execa
./school-system-frontend/node_modules/yallist
./school-system-frontend/node_modules/parseurl
./school-system-frontend/node_modules/@jridgewell
./school-system-frontend/node_modules/etag
./school-system-frontend/node_modules/cssesc
./school-system-frontend/node_modules/@bramus
./school-system-frontend/node_modules/log-symbols
./school-system-frontend/node_modules/follow-redirects
./school-system-frontend/node_modules/node-html-parser
./school-system-frontend/node_modules/p-try
./school-system-frontend/node_modules/identifier-regex
./school-system-frontend/node_modules/micromatch
./school-system-frontend/node_modules/wrappy
./school-system-frontend/node_modules/@emotion
./school-system-frontend/node_modules/fast-glob
./school-system-frontend/node_modules/decode-uri-component
./school-system-frontend/node_modules/resolve-from
./school-system-frontend/node_modules/d3-dsv
./school-system-frontend/node_modules/postcss-sass
./school-system-frontend/node_modules/tailwindcss
./school-system-frontend/node_modules/send
./school-system-frontend/node_modules/global-prefix
./school-system-frontend/node_modules/is-extglob
./school-system-frontend/node_modules/tinyexec
./school-system-frontend/node_modules/fastq
./school-system-frontend/node_modules/conf
./school-system-frontend/node_modules/shadcn-vue
./school-system-frontend/node_modules/w3c-xmlserializer
./school-system-frontend/node_modules/finalhandler
./school-system-frontend/node_modules/pathe
./school-system-frontend/node_modules/defu
./school-system-frontend/node_modules/ast-types-x
./school-system-frontend/node_modules/cli-cursor
./school-system-frontend/node_modules/parse5
./school-system-frontend/node_modules/cli-spinners
./school-system-frontend/node_modules/csscolorparser
./school-system-frontend/node_modules/caniuse-lite
./school-system-frontend/node_modules/validate-npm-package-name
./school-system-frontend/node_modules/merge2
./school-system-frontend/node_modules/domutils
./school-system-frontend/node_modules/@oxc-project
./school-system-frontend/node_modules/deep-is
./school-system-frontend/node_modules/es-set-tostringtag
./school-system-frontend/node_modules/alien-signals
./school-system-frontend/node_modules/colorette
./school-system-frontend/node_modules/tough-cookie
./school-system-frontend/node_modules/quansync
./school-system-frontend/node_modules/kleur
./school-system-frontend/node_modules/vue-router
./school-system-frontend/node_modules/d3-ease
./school-system-frontend/node_modules/speakingurl
./school-system-frontend/node_modules/webpack-virtual-modules
./school-system-frontend/node_modules/axios
./school-system-frontend/node_modules/d3-quadtree
./school-system-frontend/node_modules/@vueuse
./school-system-frontend/node_modules/braces
./school-system-frontend/node_modules/which
./school-system-frontend/node_modules/side-channel-map
./school-system-frontend/node_modules/ajv
./school-system-frontend/node_modules/emoji-regex
./school-system-frontend/node_modules/open
./school-system-frontend/node_modules/object-inspect
./school-system-frontend/node_modules/bidi-js
./school-system-frontend/node_modules/nth-check
./school-system-frontend/node_modules/web-worker
./school-system-frontend/node_modules/pbf
./school-system-frontend/node_modules/@eslint-community
./school-system-frontend/node_modules/@vue-macros
./school-system-frontend/node_modules/dedent
./school-system-frontend/node_modules/type-check
./school-system-frontend/node_modules/resolve-pkg-maps
./school-system-frontend/node_modules/locate-path
./school-system-frontend/node_modules/graceful-fs
./school-system-frontend/node_modules/on-finished
./school-system-frontend/node_modules/entities
./school-system-frontend/node_modules/copy-anything
./school-system-frontend/node_modules/birpc
./school-system-frontend/node_modules/human-signals
./school-system-frontend/node_modules/fsevents
./school-system-frontend/node_modules/p-timeout
./school-system-frontend/node_modules/fast-deep-equal
./school-system-frontend/node_modules/shebang-command
./school-system-frontend/node_modules/electron-to-chromium
./school-system-frontend/node_modules/require-from-string
./school-system-frontend/node_modules/rw
./school-system-frontend/node_modules/debug
./school-system-frontend/node_modules/dot-prop
./school-system-frontend/node_modules/glob-parent
./school-system-frontend/node_modules/domhandler
./school-system-frontend/node_modules/lightningcss
./school-system-frontend/node_modules/internmap
./school-system-frontend/node_modules/source-map-js
./school-system-frontend/node_modules/media-typer
./school-system-frontend/node_modules/mitt
./school-system-frontend/node_modules/mime-db
./school-system-frontend/node_modules/isexe
./school-system-frontend/node_modules/es-object-atoms
./school-system-frontend/node_modules/get-tsconfig
./school-system-frontend/node_modules/@juggle
./school-system-frontend/node_modules/inflight
./school-system-frontend/node_modules/source-map-resolve
./school-system-frontend/node_modules/path-scurry
./school-system-frontend/node_modules/router
./school-system-frontend/node_modules/setprototypeof
./school-system-frontend/README.md
./school-system-frontend/public
./school-system-frontend/public/icons.svg
./school-system-frontend/public/favicon.svg
./school-system-frontend/package-lock.json
./school-system-frontend/package.json
./school-system-frontend/scripts
./school-system-frontend/scripts/verify-profile-access.mjs
./school-system-frontend/scripts/verify-student-wizard.mjs
./school-system-frontend/scripts/verify-student-save.mjs
./school-system-frontend/components.json
./school-system-frontend/tsconfig.json
./school-system-frontend/eslint.config.js
./school-system-frontend/vite.config.ts
./school-system-frontend/src
./school-system-frontend/src/App.vue
./school-system-frontend/src/types
./school-system-frontend/src/composables
./school-system-frontend/src/app
./school-system-frontend/src/main.ts
./school-system-frontend/src/plugins
./school-system-frontend/src/stores
./school-system-frontend/src/components
./school-system-frontend/src/layouts
./school-system-frontend/src/style.css
./school-system-frontend/src/lib
./school-system-frontend/src/modules
./school-system-frontend/src/assets
./school-system-frontend/src/services
./school-system-frontend/src/router
./stubs
./stubs/agent-middleware.stub
./stubs/tool.stub
./stubs/agent.stub
./stubs/structured-agent.stub
./PROJECT_CONTEXT.md
./README copy.md
./database
./database/database.sqlite
./database/migrations
./database/migrations/2026_01_27_000013_create_notification_queue_table.php
./database/migrations/2026_07_26_210000_add_library_member_id_to_library_loans_table.php
./database/migrations/2026_01_28_000001_add_permission_ids_to_roles_table.php
./database/migrations/2026_01_24_000001_create_schools_table.php
./database/migrations/2026_07_19_114900_add_fee_category_id_to_fee_structures_table.php
./database/migrations/2026_01_27_000004_create_grading_scales_table.php
./database/migrations/2026_06_20_202240_add_two_factor_columns_to_users_table.php
./database/migrations/2024_01_20_100003_create_payments_table.php
./database/migrations/2024_01_20_000000_create_personal_access_tokens_table.php
./database/migrations/2026_07_28_160000_add_workflow_fields_to_budgets.php
./database/migrations/2026_06_23_000001_add_avatar_url_to_users_table.php
./database/migrations/2026_01_27_000019_add_missing_foreign_key_constraints.php
./database/migrations/2026_07_20_112600_add_profile_fields_to_schools_table.php
./database/migrations/2026_01_27_000017_create_tests_table.php
./database/migrations/2024_01_23_100003_create_parent_student_table.php
./database/migrations/2026_06_22_000008_create_enterprise_erp_modules.php
./database/migrations/2024_01_23_100001_create_announcements_table.php
./database/migrations/2026_07_28_170000_create_webhook_deliveries_and_secrets.php
./database/migrations/0001_01_01_000000_create_users_table.php
./database/migrations/2026_01_27_000003_create_rooms_table.php
./database/migrations/2026_06_21_000002_create_leave_requests_table.php
./database/migrations/2024_01_20_100009_create_transactions_table.php
./database/migrations/2026_06_20_000001_fix_attendance_unique_index.php
./database/migrations/2026_01_24_000004_create_payroll_table.php
./database/migrations/2026_06_20_000001_create_school_settings_table.php
./database/migrations/2026_07_28_140000_create_help_desk_tickets_table.php
./database/migrations/2026_01_27_000005_create_teacher_assignments_table.php
./database/migrations/2026_01_24_000007_add_foreign_keys_to_users.php
./database/migrations/2026_06_21_000001_add_deferred_foreign_key_constraints.php
./database/migrations/0001_01_01_000001_create_cache_table.php
./database/migrations/2026_01_24_000003_create_subjects_table.php
./database/migrations/2024_01_20_100001_create_teachers_table.php
./database/migrations/2026_01_27_000016_create_exam_results_table.php
./database/migrations/2026_01_27_000018_create_test_results_table.php
./database/migrations/2026_07_19_000001_add_platform_terms_acceptance_to_users_table.php
./database/migrations/2024_01_20_100007_create_timetable_table.php
./database/migrations/2026_01_23_195455_create_departments_table.php
./database/migrations/2026_06_22_000004_create_parent_portal_tables.php
./database/migrations/2026_07_28_120000_create_school_domains_table.php
./database/migrations/0001_01_01_000002_create_jobs_table.php
./database/migrations/2026_06_22_000003_create_holiday_program_tables.php
./database/migrations/2026_07_19_000002_create_school_trip_tables.php
./database/migrations/2026_01_27_000015_create_exams_table.php
./database/migrations/2024_01_23_100002_create_assignments_table.php
./database/migrations/2026_06_22_000007_create_advanced_platform_features.php
./database/migrations/2026_06_22_000001_add_student_lifecycle_columns.php
./database/migrations/2026_01_27_000007_create_guardian_student_table.php
./database/migrations/2026_07_19_120000_add_permission_ids_to_users_table.php
./database/migrations/2026_01_28_000004_add_amount_paid_to_payroll_table.php
./database/migrations/2026_01_28_000003_add_assessment_links_to_grades_table.php
./database/migrations/2024_01_20_100000_create_students_table.php
./database/migrations/2026_06_22_000009_create_school_license_keys.php
./database/migrations/2026_06_20_000004_create_terminology_mappings_table.php
./database/migrations/2026_01_25_000001_create_report_templates_table.php
./database/migrations/2026_07_28_140100_add_photo_url_to_students_table.php
./database/migrations/2026_07_26_205000_add_accounting_heads_to_transactions_table.php
./database/migrations/2026_06_22_000005_create_audit_tables.php
./database/migrations/2024_01_20_100008_create_fee_structures_table.php
./database/migrations/2026_06_20_000003_create_custom_field_values_table.php
./database/migrations/2026_06_22_000006_create_school_erp_modules.php
./database/migrations/2026_10_06_095715_add_preskool_ui_parity_fields_to_tables.php
./database/migrations/2024_01_23_100000_create_enrollment_applications_table.php
./database/migrations/2026_01_23_195542_create_fee_categories_table.php
./database/migrations/2026_07_28_130100_create_school_backups_table.php
./database/migrations/2024_01_01_000000_create_schools_table.php
./database/migrations/2026_07_28_150000_create_recruitment_tables.php
./database/migrations/2026_01_22_123134_create_settings_table.php
./database/migrations/2026_07_28_160000_create_school_user_memberships_table.php
./database/migrations/2024_01_20_100002_create_classes_table.php
./database/migrations/2026_07_28_150000_add_revenue_fields_to_license_keys.php
./database/migrations/2024_01_20_100005_create_grades_table.php
./database/migrations/2026_06_21_000003_create_student_documents_table.php
./database/migrations/2026_07_26_200000_add_edudash_parity_modules.php
./database/migrations/2026_07_26_203000_add_discount_fields_to_invoices_table.php
./database/migrations/2024_01_20_100004_create_invoices_table.php
./database/migrations/2024_01_20_100006_create_attendance_table.php
./database/migrations/2026_07_28_130000_add_verification_token_to_school_domains.php
./database/migrations/2026_06_22_000002_create_inventory_tables.php
./database/migrations/2026_06_20_202241_create_passkeys_table.php
./database/migrations/2026_06_22_120000_add_finance_performance_indexes.php
./database/migrations/2026_07_19_014200_add_student_placement_lifecycle_tables.php
./database/migrations/2026_06_20_203743_create_agent_conversations_table.php
./database/migrations/2026_01_24_000002_create_roles_table.php
./database/migrations/2026_01_27_000014_create_terms_table.php
./database/migrations/2026_01_27_000006_create_guardians_table.php
./database/migrations/2026_07_23_000001_create_teacher_portal_tables.php
./database/migrations/2026_07_19_000001_add_disbursement_fields_to_purchase_requisitions.php
./database/migrations/2026_07_26_204000_create_school_languages_table.php
./database/migrations/2026_01_23_114727_create_enrollments_table.php
./database/migrations/2026_06_20_000002_create_custom_fields_table.php
./database/migrations/2026_01_28_000002_add_status_to_users_table.php
./database/migrations/2026_01_27_000002_create_grade_levels_table.php
./database/database.sqlite-wal
./database/database.sqlite-shm
./database/schema
./database/schema/sqlite-schema.sql
./database/seeders
./database/seeders/TransactionSeeder.php
./database/seeders/EnrollmentSeeder.php
./database/seeders/StudentSeeder.php
./database/seeders/TestResultSeeder.php
./database/seeders/ExamResultSeeder.php
./database/seeders/ClassSeeder.php
./database/seeders/FeeStructureSeeder.php
./database/seeders/SEEDERS_README.md
./database/seeders/SubjectsSeeder.php
./database/seeders/RoleSeeder.php
./database/seeders/TeacherSeeder.php
./database/seeders/AnnouncementSeeder.php
./database/seeders/TeacherAssignmentSeeder.php
./database/seeders/RoomSeeder.php
./database/seeders/InvoiceSeeder.php
./database/seeders/TimetableSeeder.php
./database/seeders/AttendanceSeeder.php
./database/seeders/DemoUserSeeder.php
./database/seeders/WorkflowSeeder.php
./database/seeders/AssignmentSeeder.php
./database/seeders/SchoolTripSeeder.php
./database/seeders/GuardianSeeder.php
./database/seeders/SubjectSeeder.php
./database/seeders/ErpModulesSeeder.php
./database/seeders/PayrollSeeder.php
./database/seeders/GradeSeeder.php
./database/seeders/UserSeeder.php
./database/seeders/ParentStudentSeeder.php
./database/seeders/TestSeeder.php
./database/seeders/AuditTrailSeeder.php
./database/seeders/PaymentSeeder.php
./database/seeders/StudentPortalSeeder.php
./database/seeders/DemoDataSeeder.php
./database/seeders/SchoolSeeder.php
./database/seeders/TeacherPortalSeeder.php
./database/seeders/TermSeeder.php
./database/seeders/OperationsSeeder.php
./database/seeders/EnrollmentApplicationSeeder.php
./database/seeders/PlatformFeaturesSeeder.php
./database/seeders/GradingScaleSeeder.php
./database/seeders/GradeLevelSeeder.php
./database/seeders/LicenseSeeder.php
./database/seeders/FeeCategoriesSeeder.php
./database/seeders/ReportTemplateSeeder.php
./database/seeders/EnterpriseModulesSeeder.php
./database/seeders/ExamSeeder.php
./database/seeders/DepartmentsSeeder.php
./database/seeders/Helpers
./database/seeders/CoreReferenceSeeder.php
./database/seeders/RelationIntegritySeeder.php
./database/seeders/DatabaseSeeder.php
./database/seeders/Concerns
./database/seeders/SettingSeeder.php
./database/seeders/EduDashParityDefaultsSeeder.php
./database/factories
./database/factories/GradeLevelFactory.php
./database/factories/GuardianFactory.php
./database/factories/ExamFactory.php
./database/factories/PaymentFactory.php
./database/factories/TeacherFactory.php
./database/factories/DepartmentFactory.php
./database/factories/FeeStructureFactory.php
./database/factories/SchoolFactory.php
./database/factories/StudentFactory.php
./database/factories/RoomFactory.php
./database/factories/TimetableFactory.php
./database/factories/GradeFactory.php
./database/factories/SubjectFactory.php
./database/factories/InvoiceFactory.php
./database/factories/UserFactory.php
./database/factories/ClassModelFactory.php
./database/factories/AttendanceFactory.php
./database/factories/TermFactory.php
./composer.lock
./phpunit.xml
./bootstrap
./bootstrap/app.php
./bootstrap/cache
./bootstrap/cache/services.php
./bootstrap/cache/packages.php
./bootstrap/providers.php
./app
./app/Contracts
./app/Contracts/TeachingAssistant.php
./app/Providers
./app/Providers/AppServiceProvider.php
./app/Providers/FortifyServiceProvider.php
./app/Enums
./app/Enums/LicensePlanType.php
./app/Enums/LicenseKeyStatus.php
./app/Enums/UserRole.php
./app/Models
./app/Models/StudentTimelineEvent.php
./app/Models/GradeLevel.php
./app/Models/RevenueRecognitionRule.php
./app/Models/HostelAllocation.php
./app/Models/WebhookSubscription.php
./app/Models/IncomeHead.php
./app/Models/LeaveRequest.php
./app/Models/SubjectPrerequisite.php
./app/Models/AssessmentCategory.php
./app/Models/SchoolTrip.php
./app/Models/TeachingResource.php
./app/Models/GradebookRule.php
./app/Models/HelpDeskTicket.php
./app/Models/Teacher.php
./app/Models/Transaction.php
./app/Models/Refund.php
./app/Models/LibraryBook.php
./app/Models/Visitor.php
./app/Models/ConsentForm.php
./app/Models/TestResult.php
./app/Models/TerminologyMapping.php
./app/Models/InventoryItem.php
./app/Models/SyllabusTopic.php
./app/Models/InstalmentScheduleItem.php
./app/Models/JobPosting.php
./app/Models/CurriculumVersion.php
./app/Models/Enrollment.php
./app/Models/ClassParticipationRecord.php
./app/Models/PaymentGatewayConfig.php
./app/Models/AcademicCalendarEntry.php
./app/Models/School.php
./app/Models/ContinuousAssessment.php
./app/Models/RecordVersion.php
./app/Models/Certificate.php
./app/Models/HolidayEnrollment.php
./app/Models/Policy.php
./app/Models/SecureDocumentAccessLog.php
./app/Models/WorkflowInstance.php
./app/Models/FeeGroup.php
./app/Models/ComplianceRequirement.php
./app/Models/QuestionBankItem.php
./app/Models/MessageCampaign.php
./app/Models/WorkflowApproval.php
./app/Models/Payroll.php
./app/Models/CbtExamSession.php
./app/Models/Vendor.php
./app/Models/TimetableChangeRequest.php
./app/Models/BankStatementLine.php
./app/Models/Grade.php
./app/Models/Guardian.php
./app/Models/SchoolUserMembership.php
./app/Models/AdmissionScore.php
./app/Models/TransportRoute.php
./app/Models/PaymentGatewayTransaction.php
./app/Models/AssetDisposal.php
./app/Models/IncidentReport.php
./app/Models/SchoolEvent.php
./app/Models/JournalEntry.php
./app/Models/InstalmentPlan.php
./app/Models/JournalLine.php
./app/Models/Room.php
./app/Models/StaffContract.php
./app/Models/Term.php
./app/Models/StudentIntervention.php
./app/Models/Designation.php
./app/Models/Attendance.php
./app/Models/User.php
./app/Models/WebhookDelivery.php
./app/Models/Role.php
./app/Models/InventoryMovement.php
./app/Models/ClinicVisit.php
./app/Models/SchoolTripEnrollment.php
./app/Models/CustomFieldValue.php
./app/Models/StaffTask.php
./app/Models/SecureDocument.php
./app/Models/LearningOutcomeRecord.php
./app/Models/CertificateTemplate.php
./app/Models/HolidayAttendance.php
./app/Models/SignableDocument.php
./app/Models/House.php
./app/Models/SchoolSetting.php
./app/Models/ReportTemplate.php
./app/Models/StaffCertification.php
./app/Models/BehaviorPoint.php
./app/Models/StudentTransportAllocation.php
./app/Models/WorkflowDefinition.php
./app/Models/CommunicationThread.php
./app/Models/HubMessageDelivery.php
./app/Models/Driver.php
./app/Models/InventorySaleItem.php
./app/Models/Payment.php
./app/Models/Timetable.php
./app/Models/ClassSubstitution.php
./app/Models/Stream.php
./app/Models/PromotionRule.php
./app/Models/Student.php
./app/Models/Scholarship.php
./app/Models/FeePenalty.php
./app/Models/PurchaseRequisition.php
./app/Models/StaffFeedPost.php
./app/Models/ApiClient.php
./app/Models/TeacherNotification.php
./app/Models/ReportCardNarrative.php
./app/Models/CrossSchoolTransfer.php
./app/Models/Test.php
./app/Models/DisciplinaryRecord.php
./app/Models/ParentNotification.php
./app/Models/FeeDiscount.php
./app/Models/SchoolDomain.php
./app/Models/FeePenaltyRule.php
./app/Models/DocumentSignature.php
./app/Models/NotificationQueue.php
./app/Models/ExamResult.php
./app/Models/Invoice.php
./app/Models/ClassModel.php
./app/Models/LoginHistory.php
./app/Models/Employee.php
./app/Models/EnrollmentApplication.php
./app/Models/StudentCategory.php
./app/Models/ArchivedRecord.php
./app/Models/StaffAttendance.php
./app/Models/SchoolLanguage.php
./app/Models/ConsentResponse.php
./app/Models/StudentDocument.php
./app/Models/AssetMaintenanceLog.php
./app/Models/FeeCategory.php
./app/Models/Department.php
./app/Models/GradingScale.php
./app/Models/AttendanceSession.php
./app/Models/SchoolCurrency.php
./app/Models/AlumniRecord.php
./app/Models/LicenseKey.php
./app/Models/FeeStructure.php
./app/Models/PerformanceReview.php
./app/Models/HolidayProgram.php
./app/Models/PolicyRule.php
./app/Models/InventorySale.php
./app/Models/HubMessage.php
./app/Models/StudentMedicalProfile.php
./app/Models/RemarkRequest.php
./app/Models/Announcement.php
./app/Models/ExamAntiCheatLog.php
./app/Models/TeacherAssignment.php
./app/Models/GroupPolicy.php
./app/Models/WorkflowDelegation.php
./app/Models/Hostel.php
./app/Models/StaffFeedComment.php
./app/Models/LibraryLoan.php
./app/Models/CustomField.php
./app/Models/ExchangeRate.php
./app/Models/RetentionPolicy.php
./app/Models/OnlineLesson.php
./app/Models/GoodsReceipt.php
./app/Models/CommunicationMessage.php
./app/Models/LeaveType.php
./app/Models/ExpenseHead.php
./app/Models/Assignment.php
./app/Models/Exam.php
./app/Models/ExamSchedule.php
./app/Models/AuditLog.php
./app/Models/StudentStatusEvent.php
./app/Models/Budget.php
./app/Models/LibraryMember.php
./app/Models/AssignmentSubmission.php
./app/Models/Scopes
./app/Models/WorkflowDefinitionStep.php
./app/Models/JobApplication.php
./app/Models/Setting.php
./app/Models/AbacPolicy.php
./app/Models/DataMaskingRule.php
./app/Models/SchoolBackup.php
./app/Models/Vehicle.php
./app/Models/CbtResponse.php
./app/Models/HostelBed.php
./app/Models/ChartOfAccount.php
./app/Models/HostelRoom.php
./app/Models/GradeLevelSubject.php
./app/Models/ScholarshipApplication.php
./app/Models/Concerns
./app/Models/IntegrationConnector.php
./app/Models/LessonPlan.php
./app/Models/OperationsAlert.php
./app/Models/Subject.php
./app/Models/PurchaseRequisitionItem.php
./app/Models/Asset.php
./app/Exceptions
./app/Exceptions/DomainException.php
./app/Policies
./app/Policies/TeacherPolicy.php
./app/Policies/StudentPolicy.php
./app/Policies/SchoolPolicy.php
./app/Policies/CustomFieldPolicy.php
./app/Policies/Concerns
./app/Support
./app/Support/Csv
./app/Support/AuditLabels.php
./app/Http
./app/Http/Middleware
./app/Http/Traits
./app/Http/Resources
./app/Http/Responses
./app/Http/Requests
./app/Http/Controllers
./app/Http/Concerns
./app/Ai
./app/Ai/Tools
./app/Ai/Agents
./app/Actions
./app/Actions/Fortify
./app/Rules
./app/Rules/ZimbabweMobileNumber.php
./app/Jobs
./app/Jobs/Middleware
./app/Jobs/DispatchEnterpriseWebhookJob.php
./app/Jobs/ProcessSchoolNotificationsJob.php
./app/Jobs/Concerns
./app/Services
./app/Services/CustomFieldService.php
./app/Services/Assistant
./app/Services/StudentResolutionService.php
./app/Services/AttendanceNotificationService.php
./app/Services/CashFlowService.php
./app/Services/SchoolConfigurationService.php
./app/Services/PermissionService.php
./app/Services/StaffNumberService.php
./app/Services/FinancialLedgerService.php
./app/Services/Enterprise
./app/Services/Auth
./app/Services/LicenseService.php
./app/Services/Tenancy
./app/Services/TeacherLeaveStatusService.php
./app/Services/Platform
./app/Services/EnrollmentApprovalService.php
./app/Services/StudentPlacementService.php
./app/Services/TeacherResolutionService.php
./app/Services/FeeDiscountService.php
./app/Services/ParentNotificationService.php
./app/Services/GuardianService.php
./app/Services/SchoolSettingsService.php
./app/Services/InventoryService.php
./app/Services/ParentAccessService.php
./app/Services/StudentAdmissionService.php
./app/Services/SchoolTripService.php
./app/Services/Ai
./app/Services/StudentLifecycleActionService.php
./app/Services/SchoolProvisioningService.php
./app/Services/TimetableGeneratorService.php
./app/Services/WorkflowService.php
./app/Services/Export
./app/Services/GuardianResolutionService.php
./app/Services/Import
./app/Services/Domain
./app/Services/Messaging
./app/Services/ExamAccessService.php
./app/Services/HolidayProgramService.php
./app/Services/AuditService.php
./app/Services/TerminologyService.php
./app/Services/CommunicationMessagingService.php
./app/Services/StudentPromotionService.php
./app/Services/FileUploadService.php
./app/Services/TimetableConflictService.php
./app/Console
./app/Console/Commands
./_ide_helper_models.php
./config
./config/sanctum.php
./config/fortify.php
./config/auth.php
./config/app.php
./config/mail.php
./config/school.php
./config/tenancy.php
./config/permissions.php
./config/permission_capabilities.php
./config/services.php
./config/audit.php
./config/database.php
./config/cache.php
./config/session.php
./config/platform_terms.php
./config/queue.php
./config/teacher_permissions.php
./config/license.php
./config/platform_privacy.php
./config/security.php
./config/scribe.php
./config/cors.php
./config/ai.php
./config/logging.php
./config/filesystems.php
./Dockerfile
./_ide_helper.php
./resources
./resources/css
./resources/css/app.css
./resources/js
./resources/js/bootstrap.js
./resources/js/app.js
./resources/views
./resources/views/welcome.blade.php
./resources/views/scribe
./apache.conf
./tests
./tests/Unit
./tests/Unit/ExampleTest.php
./tests/Unit/ZimbabweMobileNumberTest.php
./tests/Feature
./tests/Feature/ClassApiTest.php
./tests/Feature/OtherEndpointsApiTest.php
./tests/Feature/TenantRuntimeIsolationTest.php
./tests/Feature/TenantBackupAndLifecycleTest.php
./tests/Feature/AcademicApiTest.php
./tests/Feature/StudentDocumentsAndOnlinePaymentsTest.php
./tests/Feature/AttendanceApiTest.php
./tests/Feature/PlatformTermsAcceptanceTest.php
./tests/Feature/StudentIdCardPrintTest.php
./tests/Feature/TeacherPortalApiTest.php
./tests/Feature/AdminEndpointSmokeTest.php
./tests/Feature/LeaveRequestApiTest.php
./tests/Feature/TeacherRelationshipsTest.php
./tests/Feature/ExampleTest.php
./tests/Feature/WorkflowApiTest.php
./tests/Feature/FeeStructureApiTest.php
./tests/Feature/UserAuthorizationTest.php
./tests/Feature/StudentNumberGenerationTest.php
./tests/Feature/ParentPortalApiTest.php
./tests/Feature/AdminSchoolProvisionTest.php
./tests/Feature/TimetableApiTest.php
./tests/Feature/ReportApiTest.php
./tests/Feature/TenantMailAndCacheTest.php
./tests/Feature/GradeApiTest.php
./tests/Feature/AdminApiTest.php
./tests/Feature/StaffAttendanceSummaryAndCertificateDownloadTest.php
./tests/Feature/StudentApiTest.php
./tests/Feature/TenantDomainResolutionTest.php
./tests/Feature/AdminWriteSmokeTest.php
./tests/Feature/InvoiceApiTest.php
./tests/Feature/DomainRulesTest.php
./tests/Feature/PaymentsStagingInfoCommandTest.php
./tests/Feature/QueryContractTest.php
./tests/Feature/InvoiceDiscountAndFeeGroupTest.php
./tests/Feature/TenantSessionTest.php
./tests/Feature/CommunicationMessagesTest.php
./tests/Feature/RecruitmentTest.php
./tests/Feature/MessagingAndStorageTest.php
./tests/Feature/CashFlowApiTest.php
./tests/Feature/PayrollApiTest.php
./tests/Feature/TeacherPermissionsTest.php
./tests/Feature/ParentChildrenApiTest.php
./tests/Feature/TeacherApiTest.php
./tests/Feature/EnterpriseWebhookDispatcherTest.php
./tests/Feature/TwoFactorEnforcementTest.php
./tests/Feature/PaymentApiTest.php
./tests/Feature/SchoolErpTest.php
./tests/Feature/ErrorHardeningTest.php
./tests/Feature/DepartmentApiTest.php
./tests/Feature/AuditTrailTest.php
./tests/Feature/SchoolAssessmentApiTest.php
./tests/Feature/TwoFactorAuthApiTest.php
./tests/Feature/Api
./tests/Feature/CapabilityMiddlewareTest.php
./tests/Feature/PaynowLiveGatewayTest.php
./tests/Feature/DedicatedStaffRolesTest.php
./tests/Feature/SchoolLicenseTest.php
./tests/Feature/RolePermissionEnforcementTest.php
./tests/Feature/AuthApiTest.php
./tests/Feature/EnterpriseErpTest.php
./tests/Feature/StudentPortalCbtTest.php
./tests/Feature/TenantSwitchTest.php
./tests/Feature/StudentPortalAssignmentsTest.php
./tests/Feature/StudentLifecycleTest.php
./tests/Feature/EduDashParityModulesTest.php
./tests/Feature/GuardianApiTest.php
./tests/Feature/ExamApiTest.php
./tests/Feature/PeopleCsvImportTest.php
./tests/Feature/StudentPlacementLifecycleTest.php
./tests/Feature/HelpDeskTest.php
./tests/Feature/TransactionApiTest.php
./tests/Feature/StudentPhotoUploadTest.php
./tests/Feature/PlatformRevenueSummaryTest.php
./tests/Feature/SpendRequestApiTest.php
./tests/Feature/ParentStudentRelationshipsTest.php
./tests/Feature/AdvancedPlatformTest.php
./tests/Feature/AlumniBudgetCalendarModulesTest.php
./tests/TestCase.php
./vite.config.js
./docs
./docs/deploy
./docs/deploy/wildcard-ingress.md
./docs/deploy/paynow-webhooks-staging.md
./docs/deploy/production-readiness.md
./docs/teacher-permissions.md
./README.md
./artisan
./public
./public/favicon.ico
./public/index.php
./public/storage
./public/hot
./public/build
./public/build/manifest.json
./public/build/assets
./public/robots.txt
./public/vendor
./public/vendor/scribe
./package-lock.json
./package.json
./scripts
./scripts/provision-wildcard-tls.sh
./generate_context.sh
./supervisord.conf
./docker-compose.yml
./nginx.conf
./apache.httpd.conf
./routes
./routes/console.php
./routes/web.php
./routes/api
./routes/api/v1.php
./routes/api.php
./composer.json
```

## 2. Database Schema (DDL Dump)
```sql
CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" varchar not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE IF NOT EXISTS "payments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer not null,
  "parent_id" integer,
  "invoice_id" integer,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "method" varchar not null,
  "reference" varchar,
  "status" varchar not null default 'completed',
  "date" date not null,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("parent_id") references "users"("id") on delete set null,
  foreign key("invoice_id") references "invoices"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "payments_school_id_index" on "payments"("school_id");
CREATE INDEX "payments_student_id_index" on "payments"("student_id");
CREATE INDEX "payments_parent_id_index" on "payments"("parent_id");
CREATE INDEX "payments_invoice_id_index" on "payments"("invoice_id");
CREATE TABLE IF NOT EXISTS "announcements"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "title" varchar not null,
  "message" text not null,
  "type" varchar not null default 'info',
  "target_audience" varchar not null default 'all',
  "date" date not null,
  "is_active" tinyint(1) not null default '1',
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE INDEX "announcements_school_id_index" on "announcements"("school_id");
CREATE INDEX "announcements_school_id_is_active_date_index" on "announcements"(
  "school_id",
  "is_active",
  "date"
);
CREATE TABLE IF NOT EXISTS "assignments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "title" varchar not null,
  "description" text,
  "subject" varchar not null,
  "class_id" integer not null,
  "teacher_id" integer not null,
  "due_date" date not null,
  "total_marks" numeric not null default '100',
  "status" varchar not null default 'active',
  "submissions_count" integer not null default '0',
  "instructions" text,
  "attachment_url" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "submission_type" varchar not null default 'file',
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade
);
CREATE INDEX "assignments_school_id_index" on "assignments"("school_id");
CREATE INDEX "assignments_class_id_due_date_index" on "assignments"(
  "class_id",
  "due_date"
);
CREATE TABLE IF NOT EXISTS "parent_student"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "parent_id" integer not null,
  "student_id" integer not null,
  "relationship" varchar not null default 'parent',
  "is_primary" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("parent_id") references "users"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "parent_student_parent_id_student_id_unique" on "parent_student"(
  "parent_id",
  "student_id"
);
CREATE INDEX "parent_student_school_id_index" on "parent_student"("school_id");
CREATE TABLE IF NOT EXISTS "settings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "category" varchar not null default 'general',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "settings_school_id_key_unique" on "settings"(
  "school_id",
  "key"
);
CREATE INDEX "settings_school_id_index" on "settings"("school_id");
CREATE TABLE IF NOT EXISTS "departments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "head_teacher_id" integer,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("head_teacher_id") references "teachers"("id") on delete set null
);
CREATE UNIQUE INDEX "departments_school_id_name_unique" on "departments"(
  "school_id",
  "name"
);
CREATE INDEX "departments_school_id_index" on "departments"("school_id");
CREATE TABLE IF NOT EXISTS "fee_categories"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "fee_categories_school_id_name_unique" on "fee_categories"(
  "school_id",
  "name"
);
CREATE INDEX "fee_categories_school_id_index" on "fee_categories"("school_id");
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  "permission_ids" text
);
CREATE UNIQUE INDEX "roles_slug_unique" on "roles"("slug");
CREATE TABLE IF NOT EXISTS "subjects"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "class_id" integer,
  "teacher_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("teacher_id") references "teachers"("id") on delete set null
);
CREATE UNIQUE INDEX "subjects_school_id_name_unique" on "subjects"(
  "school_id",
  "name"
);
CREATE INDEX "subjects_school_id_index" on "subjects"("school_id");
CREATE TABLE IF NOT EXISTS "payroll"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "month" integer not null,
  "year" integer not null,
  "base_salary" numeric not null,
  "gross_salary" numeric not null,
  "allowances" text,
  "deductions" text,
  "allowances_total" numeric not null default '0',
  "deductions_total" numeric not null default '0',
  "net_salary" numeric not null,
  "currency" varchar not null default 'USD',
  "status" varchar not null default 'pending',
  "paid_at" date,
  "payment_method" varchar,
  "payment_reference" varchar,
  "processed_by" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "amount_paid" numeric not null default '0',
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("processed_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "payroll_school_id_teacher_id_month_year_unique" on "payroll"(
  "school_id",
  "teacher_id",
  "month",
  "year"
);
CREATE INDEX "payroll_school_id_status_index" on "payroll"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "report_templates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "name" varchar not null,
  "description" text,
  "category" varchar not null,
  "frequency" varchar,
  "recipients" text,
  "parameters" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "report_templates_school_id_index" on "report_templates"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "grade_levels"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "order" integer not null default '0',
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "grade_levels_school_id_name_unique" on "grade_levels"(
  "school_id",
  "name"
);
CREATE UNIQUE INDEX "grade_levels_school_id_code_unique" on "grade_levels"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "rooms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "type" varchar,
  "capacity" integer,
  "description" text,
  "location" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "rooms_school_id_name_unique" on "rooms"(
  "school_id",
  "name"
);
CREATE UNIQUE INDEX "rooms_school_id_code_unique" on "rooms"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "grading_scales"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "grade" varchar not null,
  "min_score" numeric not null,
  "max_score" numeric not null,
  "description" varchar,
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "grade_point" numeric default '0',
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "grading_scales_school_id_grade_unique" on "grading_scales"(
  "school_id",
  "grade"
);
CREATE INDEX "grading_scales_school_id_min_score_max_score_index" on "grading_scales"(
  "school_id",
  "min_score",
  "max_score"
);
CREATE TABLE IF NOT EXISTS "teacher_assignments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "grade_level_id" integer,
  "class_id" integer,
  "subject_id" integer,
  "role" varchar,
  "assigned_at" date not null default '2026-07-28 16:04:50',
  "assigned_until" date,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("grade_level_id") references "grade_levels"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE INDEX "teacher_assignments_school_id_teacher_id_index" on "teacher_assignments"(
  "school_id",
  "teacher_id"
);
CREATE INDEX "teacher_assignments_school_id_class_id_index" on "teacher_assignments"(
  "school_id",
  "class_id"
);
CREATE INDEX "teacher_assignments_school_id_grade_level_id_index" on "teacher_assignments"(
  "school_id",
  "grade_level_id"
);
CREATE TABLE IF NOT EXISTS "guardians"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "email" varchar,
  "phone" varchar not null,
  "relationship" varchar,
  "address" text,
  "national_id" varchar,
  "occupation" varchar,
  "is_primary" tinyint(1) not null default '0',
  "can_receive_notifications" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "guardians_school_id_email_index" on "guardians"(
  "school_id",
  "email"
);
CREATE INDEX "guardians_school_id_phone_index" on "guardians"(
  "school_id",
  "phone"
);
CREATE TABLE IF NOT EXISTS "guardian_student"(
  "id" integer primary key autoincrement not null,
  "guardian_id" integer not null,
  "student_id" integer not null,
  "relationship" varchar,
  "is_primary" tinyint(1) not null default '0',
  "can_pickup" tinyint(1) not null default '1',
  "emergency_contact" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("guardian_id") references "guardians"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "guardian_student_guardian_id_student_id_unique" on "guardian_student"(
  "guardian_id",
  "student_id"
);
CREATE INDEX "guardian_student_student_id_index" on "guardian_student"(
  "student_id"
);
CREATE TABLE IF NOT EXISTS "terms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "academic_year" varchar not null,
  "start_date" date not null,
  "end_date" date not null,
  "is_current" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "terms_school_id_academic_year_name_unique" on "terms"(
  "school_id",
  "academic_year",
  "name"
);
CREATE INDEX "terms_school_id_academic_year_is_current_index" on "terms"(
  "school_id",
  "academic_year",
  "is_current"
);
CREATE INDEX "terms_school_id_is_active_index" on "terms"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "tests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "class_id" integer not null,
  "subject_id" integer not null,
  "teacher_id" integer not null,
  "term_id" integer,
  "name" varchar not null,
  "description" text,
  "test_date" date not null,
  "start_time" time,
  "end_time" time,
  "total_marks" numeric not null default '100',
  "passing_marks" numeric,
  "academic_year" varchar not null,
  "is_published" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null
);
CREATE INDEX "tests_school_id_class_id_subject_id_index" on "tests"(
  "school_id",
  "class_id",
  "subject_id"
);
CREATE INDEX "tests_school_id_teacher_id_index" on "tests"(
  "school_id",
  "teacher_id"
);
CREATE INDEX "tests_school_id_test_date_index" on "tests"(
  "school_id",
  "test_date"
);
CREATE TABLE IF NOT EXISTS "test_results"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "test_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "marks_obtained" numeric not null,
  "total_marks" numeric not null,
  "percentage" numeric,
  "grade" varchar,
  "remarks" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("test_id") references "tests"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade
);
CREATE UNIQUE INDEX "test_results_test_id_student_id_unique" on "test_results"(
  "test_id",
  "student_id"
);
CREATE INDEX "test_results_school_id_test_id_index" on "test_results"(
  "school_id",
  "test_id"
);
CREATE INDEX "test_results_school_id_student_id_index" on "test_results"(
  "school_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "role" varchar not null default('admin'),
  "school_id" integer,
  "phone" varchar,
  "address" text,
  "date_of_birth" date,
  "gender" varchar,
  "first_name" varchar,
  "last_name" varchar,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar not null default 'active',
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "avatar_url" varchar,
  "platform_terms_version" varchar,
  "platform_terms_accepted_at" datetime,
  "permission_ids" text,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "users_role_index" on "users"("role");
CREATE INDEX "users_school_id_index" on "users"("school_id");
CREATE INDEX "users_status_index" on "users"("status");
CREATE TABLE IF NOT EXISTS "school_settings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "group" varchar not null default 'general',
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "is_public" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_settings_school_id_group_key_unique" on "school_settings"(
  "school_id",
  "group",
  "key"
);
CREATE INDEX "school_settings_school_id_group_index" on "school_settings"(
  "school_id",
  "group"
);
CREATE TABLE IF NOT EXISTS "custom_fields"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "entity_type" varchar not null,
  "name" varchar not null,
  "slug" varchar not null,
  "field_type" varchar not null default 'text',
  "options" text,
  "is_required" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "custom_fields_school_id_entity_type_slug_unique" on "custom_fields"(
  "school_id",
  "entity_type",
  "slug"
);
CREATE INDEX "custom_fields_school_id_entity_type_is_active_index" on "custom_fields"(
  "school_id",
  "entity_type",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "custom_field_values"(
  "id" integer primary key autoincrement not null,
  "custom_field_id" integer not null,
  "entity_type" varchar not null,
  "entity_id" integer not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("custom_field_id") references "custom_fields"("id") on delete cascade
);
CREATE INDEX "custom_field_values_entity_type_entity_id_index" on "custom_field_values"(
  "entity_type",
  "entity_id"
);
CREATE UNIQUE INDEX "custom_field_values_unique" on "custom_field_values"(
  "custom_field_id",
  "entity_type",
  "entity_id"
);
CREATE TABLE IF NOT EXISTS "terminology_mappings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "system_key" varchar not null,
  "custom_label" varchar not null,
  "locale" varchar not null default 'en',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "terminology_mappings_school_id_system_key_locale_unique" on "terminology_mappings"(
  "school_id",
  "system_key",
  "locale"
);
CREATE INDEX "terminology_mappings_school_id_locale_index" on "terminology_mappings"(
  "school_id",
  "locale"
);
CREATE TABLE IF NOT EXISTS "passkeys"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "credential_id" varchar not null,
  "credential" text not null,
  "last_used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "passkeys_user_id_index" on "passkeys"("user_id");
CREATE UNIQUE INDEX "passkeys_credential_id_unique" on "passkeys"(
  "credential_id"
);
CREATE TABLE IF NOT EXISTS "agent_conversation_messages"(
  "id" varchar not null,
  "conversation_id" varchar not null,
  "user_id" integer,
  "agent" varchar not null,
  "role" varchar not null,
  "content" text not null,
  "attachments" text not null,
  "tool_calls" text not null,
  "tool_results" text not null,
  "usage" text not null,
  "meta" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "conversation_index" on "agent_conversation_messages"(
  "conversation_id",
  "user_id",
  "updated_at"
);
CREATE INDEX "agent_conversation_messages_user_id_index" on "agent_conversation_messages"(
  "user_id"
);
CREATE INDEX "agent_conversation_messages_conversation_id_index" on "agent_conversation_messages"(
  "conversation_id"
);
CREATE TABLE IF NOT EXISTS "grades"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer not null,
  "subject" varchar not null,
  "subject_id" integer,
  "assessment_type" varchar not null default('test'),
  "score" numeric not null,
  "total" numeric not null default('100'),
  "grade" varchar,
  "term" varchar,
  "year" integer,
  "class_id" integer,
  "teacher_id" integer,
  "grade_level_id" integer,
  "grading_scale_id" integer,
  "remarks" text,
  "created_at" datetime,
  "updated_at" datetime,
  "test_id" integer,
  "assignment_id" integer,
  foreign key("assignment_id") references assignments("id") on delete set null on update no action,
  foreign key("test_id") references tests("id") on delete set null on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("grading_scale_id") references grading_scales("id") on delete set null on update no action,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE INDEX "grades_school_id_index" on "grades"("school_id");
CREATE INDEX "grades_student_id_index" on "grades"("student_id");
CREATE INDEX "grades_student_id_year_term_index" on "grades"(
  "student_id",
  "year",
  "term"
);
CREATE TABLE IF NOT EXISTS "timetable"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "class_id" integer not null,
  "teacher_id" integer,
  "subject_id" integer,
  "subject" varchar not null,
  "room_id" integer,
  "room" varchar,
  "day" varchar not null,
  "start_time" time not null,
  "end_time" time not null,
  "lesson_type" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("room_id") references rooms("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("class_id") references classes("id") on delete cascade on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE INDEX "timetable_class_id_day_index" on "timetable"("class_id", "day");
CREATE INDEX "timetable_school_id_index" on "timetable"("school_id");
CREATE INDEX "timetable_teacher_id_index" on "timetable"("teacher_id");
CREATE TABLE IF NOT EXISTS "agent_conversations"(
  "id" varchar not null,
  "user_id" integer,
  "title" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "agent_conversations_user_id_updated_at_index" on "agent_conversations"(
  "user_id",
  "updated_at"
);
CREATE TABLE IF NOT EXISTS "student_documents"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "uploaded_by" integer,
  "name" varchar not null,
  "type" varchar,
  "path" varchar not null,
  "mime_type" varchar,
  "size" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("uploaded_by") references "users"("id") on delete set null
);
CREATE INDEX "student_documents_school_id_student_id_index" on "student_documents"(
  "school_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "enrollment_applications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "first_name" varchar not null,
  "surname" varchar not null,
  "date_of_birth" date not null,
  "gender" varchar not null,
  "national_id" varchar,
  "address" text not null,
  "suburb" varchar,
  "phone" varchar not null,
  "email" varchar,
  "previous_school" varchar,
  "grade_applying_for" varchar not null,
  "academic_year" varchar not null,
  "guardian_first_name" varchar not null,
  "guardian_surname" varchar not null,
  "guardian_relationship" varchar not null,
  "guardian_phone" varchar not null,
  "guardian_email" varchar,
  "guardian_address" text not null,
  "guardian_employer" varchar,
  "medical_conditions" text,
  "allergies" text,
  "emergency_contact" varchar not null,
  "emergency_phone" varchar not null,
  "birth_certificate" tinyint(1) not null default('0'),
  "report_cards" tinyint(1) not null default('0'),
  "medical_certificate" tinyint(1) not null default('0'),
  "passport_photo" tinyint(1) not null default('0'),
  "status" varchar not null default('pending'),
  "notes" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "student_id" integer,
  foreign key("reviewed_by") references users("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE INDEX "enrollment_applications_school_id_index" on "enrollment_applications"(
  "school_id"
);
CREATE INDEX "enrollment_applications_school_id_status_index" on "enrollment_applications"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "inventory_items"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "sku" varchar,
  "type" varchar not null default 'uniform',
  "size" varchar,
  "unit_price" numeric not null,
  "currency" varchar not null default 'USD',
  "stock_quantity" integer not null default '0',
  "reorder_level" integer not null default '5',
  "billing_mode" varchar not null default 'direct_sale',
  "is_active" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "inventory_items_school_id_type_index" on "inventory_items"(
  "school_id",
  "type"
);
CREATE UNIQUE INDEX "inventory_items_school_id_sku_unique" on "inventory_items"(
  "school_id",
  "sku"
);
CREATE TABLE IF NOT EXISTS "inventory_sales"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "sale_number" varchar not null,
  "student_id" integer,
  "total_amount" numeric not null,
  "currency" varchar not null default 'USD',
  "payment_method" varchar not null default 'student_account',
  "status" varchar not null default 'completed',
  "invoice_id" integer,
  "sold_by" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("invoice_id") references "invoices"("id") on delete set null,
  foreign key("sold_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "inventory_sales_school_id_sale_number_unique" on "inventory_sales"(
  "school_id",
  "sale_number"
);
CREATE TABLE IF NOT EXISTS "inventory_sale_items"(
  "id" integer primary key autoincrement not null,
  "inventory_sale_id" integer not null,
  "inventory_item_id" integer not null,
  "quantity" integer not null,
  "unit_price" numeric not null,
  "line_total" numeric not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("inventory_sale_id") references "inventory_sales"("id") on delete cascade,
  foreign key("inventory_item_id") references "inventory_items"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "inventory_movements"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "inventory_item_id" integer not null,
  "type" varchar not null,
  "quantity_change" integer not null,
  "quantity_after" integer not null,
  "reference_type" varchar,
  "reference_id" integer,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("inventory_item_id") references "inventory_items"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "inventory_movements_inventory_item_id_type_index" on "inventory_movements"(
  "inventory_item_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "holiday_programs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "academic_year" varchar,
  "start_date" date not null,
  "end_date" date not null,
  "fee_amount" numeric not null default '0',
  "currency" varchar not null default 'USD',
  "is_active" tinyint(1) not null default '0',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "holiday_programs_school_id_is_active_index" on "holiday_programs"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "holiday_enrollments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "holiday_program_id" integer not null,
  "student_id" integer not null,
  "invoice_id" integer,
  "status" varchar not null default 'enrolled',
  "enrolled_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("holiday_program_id") references "holiday_programs"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null
);
CREATE UNIQUE INDEX "holiday_enrollments_holiday_program_id_student_id_unique" on "holiday_enrollments"(
  "holiday_program_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "holiday_attendance"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "holiday_program_id" integer not null,
  "student_id" integer not null,
  "date" date not null,
  "status" varchar not null default 'present',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("holiday_program_id") references "holiday_programs"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "holiday_attendance_holiday_program_id_student_id_date_unique" on "holiday_attendance"(
  "holiday_program_id",
  "student_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "parent_notifications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "parent_user_id" integer not null,
  "student_id" integer,
  "type" varchar not null,
  "title" varchar not null,
  "body" text not null,
  "data" text,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("parent_user_id") references "users"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE INDEX "parent_notifications_parent_user_id_read_at_index" on "parent_notifications"(
  "parent_user_id",
  "read_at"
);
CREATE INDEX "parent_notifications_school_id_type_index" on "parent_notifications"(
  "school_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "disciplinary_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "incident_date" date not null,
  "category" varchar not null,
  "severity" varchar not null default 'minor',
  "description" text not null,
  "action_taken" text,
  "recorded_by" integer,
  "parent_notified" tinyint(1) not null default '0',
  "parent_notified_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete set null
);
CREATE INDEX "disciplinary_records_student_id_incident_date_index" on "disciplinary_records"(
  "student_id",
  "incident_date"
);
CREATE TABLE IF NOT EXISTS "communication_threads"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer,
  "parent_user_id" integer not null,
  "staff_user_id" integer,
  "subject" varchar not null,
  "status" varchar not null default 'open',
  "last_message_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("parent_user_id") references "users"("id") on delete cascade,
  foreign key("staff_user_id") references "users"("id") on delete set null
);
CREATE INDEX "communication_threads_parent_user_id_status_index" on "communication_threads"(
  "parent_user_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "communication_messages"(
  "id" integer primary key autoincrement not null,
  "thread_id" integer not null,
  "sender_id" integer not null,
  "body" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "read_by_recipient_at" datetime,
  foreign key("thread_id") references "communication_threads"("id") on delete cascade,
  foreign key("sender_id") references "users"("id") on delete cascade
);
CREATE INDEX "communication_messages_thread_id_created_at_index" on "communication_messages"(
  "thread_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "notification_queue"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "guardian_id" integer,
  "channel" varchar not null,
  "recipient_email" varchar,
  "recipient_phone" varchar,
  "subject" text,
  "message" text not null,
  "data" text,
  "status" varchar not null default('pending'),
  "sent_at" datetime,
  "error_message" text,
  "retry_count" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "parent_user_id" integer,
  foreign key("guardian_id") references guardians("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("parent_user_id") references "users"("id") on delete set null
);
CREATE INDEX "notification_queue_notifiable_type_notifiable_id_index" on "notification_queue"(
  "notifiable_type",
  "notifiable_id"
);
CREATE INDEX "notification_queue_school_id_status_created_at_index" on "notification_queue"(
  "school_id",
  "status",
  "created_at"
);
CREATE INDEX "notification_queue_type_status_index" on "notification_queue"(
  "type",
  "status"
);
CREATE TABLE IF NOT EXISTS "audit_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "user_id" integer,
  "module" varchar not null,
  "action" varchar not null,
  "auditable_type" varchar,
  "auditable_id" integer,
  "description" varchar,
  "old_values" text,
  "new_values" text,
  "metadata" text,
  "ip_address" varchar,
  "user_agent" text,
  "device_type" varchar,
  "platform" varchar,
  "location" varchar,
  "request_method" varchar,
  "request_path" varchar,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  "integrity_hash" varchar,
  "previous_hash" varchar,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "audit_logs_school_id_module_created_at_index" on "audit_logs"(
  "school_id",
  "module",
  "created_at"
);
CREATE INDEX "audit_logs_user_id_created_at_index" on "audit_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "audit_logs_auditable_type_auditable_id_index" on "audit_logs"(
  "auditable_type",
  "auditable_id"
);
CREATE INDEX "audit_logs_action_created_at_index" on "audit_logs"(
  "action",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "login_history"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "user_id" integer,
  "email" varchar,
  "event" varchar not null,
  "ip_address" varchar,
  "user_agent" text,
  "device_type" varchar,
  "platform" varchar,
  "location" varchar,
  "token_name" varchar,
  "failure_reason" text,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "login_history_school_id_event_created_at_index" on "login_history"(
  "school_id",
  "event",
  "created_at"
);
CREATE INDEX "login_history_user_id_created_at_index" on "login_history"(
  "user_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "exam_results"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "marks_obtained" numeric not null,
  "total_marks" numeric not null,
  "percentage" numeric,
  "grade" varchar,
  "remarks" text,
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar not null default 'draft',
  "entered_by" integer,
  "approved_by" integer,
  "approved_at" datetime,
  "is_locked" tinyint(1) not null default '0',
  "locked_at" datetime,
  foreign key("subject_id") references subjects("id") on delete cascade on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("exam_id") references exams("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("entered_by") references "users"("id") on delete set null,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "exam_results_exam_id_student_id_unique" on "exam_results"(
  "exam_id",
  "student_id"
);
CREATE INDEX "exam_results_school_id_exam_id_index" on "exam_results"(
  "school_id",
  "exam_id"
);
CREATE INDEX "exam_results_school_id_student_id_index" on "exam_results"(
  "school_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "exams"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "term_id" integer not null,
  "grade_level_id" integer not null,
  "subject_id" integer not null,
  "name" varchar not null,
  "description" text,
  "exam_date" date not null,
  "start_time" time,
  "end_time" time,
  "total_marks" numeric not null default('100'),
  "passing_marks" numeric,
  "academic_year" varchar not null,
  "is_published" tinyint(1) not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "results_approved_at" datetime,
  "results_approved_by" integer,
  foreign key("subject_id") references subjects("id") on delete cascade on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete cascade on update no action,
  foreign key("term_id") references terms("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("results_approved_by") references "users"("id") on delete set null
);
CREATE INDEX "exams_school_id_academic_year_index" on "exams"(
  "school_id",
  "academic_year"
);
CREATE INDEX "exams_school_id_exam_date_index" on "exams"(
  "school_id",
  "exam_date"
);
CREATE INDEX "exams_school_id_term_id_grade_level_id_index" on "exams"(
  "school_id",
  "term_id",
  "grade_level_id"
);
CREATE TABLE IF NOT EXISTS "workflow_definitions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "code" varchar not null,
  "name" varchar not null,
  "module" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "layout" text,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "workflow_definitions_school_id_code_unique" on "workflow_definitions"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "workflow_definition_steps"(
  "id" integer primary key autoincrement not null,
  "definition_id" integer not null,
  "step_order" integer not null,
  "name" varchar not null,
  "approver_role" varchar,
  "approver_user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "escalation_hours" integer,
  "position" text,
  "approval_mode" varchar not null default 'sequential',
  "required_approvals" integer not null default '1',
  foreign key("definition_id") references "workflow_definitions"("id") on delete cascade,
  foreign key("approver_user_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "workflow_definition_steps_definition_id_step_order_unique" on "workflow_definition_steps"(
  "definition_id",
  "step_order"
);
CREATE TABLE IF NOT EXISTS "workflow_instances"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "definition_id" integer not null,
  "subject_type" varchar not null,
  "subject_id" integer not null,
  "status" varchar not null default 'pending',
  "current_step_order" integer not null default '1',
  "initiated_by" integer not null,
  "metadata" text,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "step_due_at" datetime,
  "escalated_at" datetime,
  "escalation_count" integer not null default '0',
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("definition_id") references "workflow_definitions"("id") on delete cascade,
  foreign key("initiated_by") references "users"("id") on delete cascade
);
CREATE INDEX "workflow_instances_school_id_status_index" on "workflow_instances"(
  "school_id",
  "status"
);
CREATE INDEX "workflow_instances_subject_type_subject_id_index" on "workflow_instances"(
  "subject_type",
  "subject_id"
);
CREATE TABLE IF NOT EXISTS "workflow_approvals"(
  "id" integer primary key autoincrement not null,
  "instance_id" integer not null,
  "step_order" integer not null,
  "approver_id" integer,
  "action" varchar not null,
  "comments" text,
  "acted_at" datetime not null default CURRENT_TIMESTAMP,
  "attachments" text,
  "rejection_reason" varchar,
  foreign key("instance_id") references "workflow_instances"("id") on delete cascade,
  foreign key("approver_id") references "users"("id") on delete set null
);
CREATE INDEX "workflow_approvals_instance_id_step_order_index" on "workflow_approvals"(
  "instance_id",
  "step_order"
);
CREATE TABLE IF NOT EXISTS "vendors"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "contact_person" varchar,
  "email" varchar,
  "phone" varchar,
  "address" text,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "purchase_requisition_items"(
  "id" integer primary key autoincrement not null,
  "requisition_id" integer not null,
  "description" varchar not null,
  "quantity" integer not null default '1',
  "unit_cost" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("requisition_id") references "purchase_requisitions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "goods_receipts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "requisition_id" integer not null,
  "vendor_id" integer,
  "received_by" integer not null,
  "received_date" date not null,
  "notes" text,
  "status" varchar not null default 'received',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("requisition_id") references "purchase_requisitions"("id") on delete cascade,
  foreign key("vendor_id") references "vendors"("id") on delete set null,
  foreign key("received_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "assets"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "asset_tag" varchar,
  "name" varchar not null,
  "category" varchar not null,
  "purchase_date" date,
  "purchase_cost" numeric,
  "location" varchar,
  "custodian_user_id" integer,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("custodian_user_id") references "users"("id") on delete set null
);
CREATE INDEX "assets_school_id_category_index" on "assets"(
  "school_id",
  "category"
);
CREATE TABLE IF NOT EXISTS "asset_maintenance_logs"(
  "id" integer primary key autoincrement not null,
  "asset_id" integer not null,
  "maintenance_date" date not null,
  "description" text not null,
  "cost" numeric,
  "performed_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("asset_id") references "assets"("id") on delete cascade,
  foreign key("performed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "asset_disposals"(
  "id" integer primary key autoincrement not null,
  "asset_id" integer not null,
  "disposed_at" date not null,
  "reason" text not null,
  "workflow_instance_id" integer,
  "approved_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("asset_id") references "assets"("id") on delete cascade,
  foreign key("workflow_instance_id") references "workflow_instances"("id") on delete set null,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "vehicles"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "registration_number" varchar not null,
  "make" varchar,
  "model" varchar,
  "capacity" integer,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "drivers"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer,
  "name" varchar not null,
  "license_number" varchar,
  "phone" varchar,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "transport_routes"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "vehicle_id" integer,
  "driver_id" integer,
  "route_description" text,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete set null,
  foreign key("driver_id") references "drivers"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "student_transport_allocations"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "route_id" integer not null,
  "pickup_point" varchar,
  "status" varchar not null default 'active',
  "allocated_at" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("route_id") references "transport_routes"("id") on delete cascade
);
CREATE UNIQUE INDEX "student_transport_allocations_student_id_route_id_unique" on "student_transport_allocations"(
  "student_id",
  "route_id"
);
CREATE TABLE IF NOT EXISTS "hostels"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "gender" varchar,
  "capacity" integer,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "hostel_rooms"(
  "id" integer primary key autoincrement not null,
  "hostel_id" integer not null,
  "room_number" varchar not null,
  "capacity" integer not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("hostel_id") references "hostels"("id") on delete cascade
);
CREATE UNIQUE INDEX "hostel_rooms_hostel_id_room_number_unique" on "hostel_rooms"(
  "hostel_id",
  "room_number"
);
CREATE TABLE IF NOT EXISTS "hostel_beds"(
  "id" integer primary key autoincrement not null,
  "room_id" integer not null,
  "bed_number" varchar not null,
  "status" varchar not null default 'available',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("room_id") references "hostel_rooms"("id") on delete cascade
);
CREATE UNIQUE INDEX "hostel_beds_room_id_bed_number_unique" on "hostel_beds"(
  "room_id",
  "bed_number"
);
CREATE TABLE IF NOT EXISTS "hostel_allocations"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "bed_id" integer not null,
  "allocated_at" date not null,
  "left_at" date,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("bed_id") references "hostel_beds"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "library_books"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "isbn" varchar,
  "title" varchar not null,
  "author" varchar,
  "total_copies" integer not null default '1',
  "available_copies" integer not null default '1',
  "category" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "rack_number" varchar,
  "shelf_number" varchar,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "visitors"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "phone" varchar,
  "id_number" varchar,
  "purpose" varchar not null,
  "host_user_id" integer,
  "student_id" integer,
  "check_in_at" datetime,
  "check_out_at" datetime,
  "status" varchar not null default 'checked_in',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("host_user_id") references "users"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "student_medical_profiles"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "conditions" text,
  "allergies" text,
  "blood_group" varchar,
  "emergency_notes" text,
  "medications" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "student_medical_profiles_student_id_unique" on "student_medical_profiles"(
  "student_id"
);
CREATE TABLE IF NOT EXISTS "clinic_visits"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "visit_date" date not null,
  "complaint" text not null,
  "diagnosis" text,
  "treatment" text,
  "nurse_user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("nurse_user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "type" varchar not null default 'general',
  "starts_at" datetime not null,
  "ends_at" datetime,
  "location" varchar,
  "description" text,
  "status" varchar not null default 'scheduled',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "policies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "category" varchar,
  "content" text not null,
  "effective_date" date,
  "review_date" date,
  "status" varchar not null default 'active',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "incident_reports"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "reported_by" integer not null,
  "category" varchar not null,
  "severity" varchar not null default 'low',
  "description" text not null,
  "status" varchar not null default 'open',
  "workflow_instance_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("reported_by") references "users"("id") on delete cascade,
  foreign key("workflow_instance_id") references "workflow_instances"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "consent_forms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "content" text not null,
  "target_audience" varchar not null default 'parents',
  "due_date" date,
  "requires_signature" tinyint(1) not null default '0',
  "status" varchar not null default 'active',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "consent_responses"(
  "id" integer primary key autoincrement not null,
  "form_id" integer not null,
  "parent_user_id" integer not null,
  "student_id" integer,
  "status" varchar not null default 'pending',
  "responded_at" datetime,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("form_id") references "consent_forms"("id") on delete cascade,
  foreign key("parent_user_id") references "users"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE UNIQUE INDEX "consent_responses_form_id_parent_user_id_student_id_unique" on "consent_responses"(
  "form_id",
  "parent_user_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "schools"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "code" varchar not null,
  "address" text,
  "phone" varchar,
  "email" varchar,
  "status" varchar not null default('active'),
  "contact_person" varchar,
  "contact_phone" varchar,
  "contact_email" varchar,
  "currency" varchar not null default('USD'),
  "currency_default" varchar not null default('USD'),
  "currency_locked" tinyint(1) not null default('0'),
  "academic_year" varchar,
  "current_term" varchar,
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime,
  "parent_school_id" integer,
  "branch_type" varchar,
  "license_status" varchar not null default 'none',
  "license_plan" varchar,
  "license_expires_at" datetime,
  "principal_name" varchar,
  "website" varchar,
  "year_founded" integer,
  "suburb" varchar,
  "city" varchar,
  "student_capacity" integer,
  "timezone" varchar,
  "motto" varchar,
  "logo_path" varchar,
  foreign key("parent_school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "schools_code_unique" on "schools"("code");
CREATE INDEX "schools_status_index" on "schools"("status");
CREATE INDEX "schools_parent_school_id_index" on "schools"("parent_school_id");
CREATE TABLE IF NOT EXISTS "policy_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "module" varchar not null,
  "trigger_event" varchar not null,
  "conditions" text not null,
  "actions" text not null,
  "priority" integer not null default '100',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "policy_rules_school_id_code_unique" on "policy_rules"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "hub_messages"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "sender_id" integer not null,
  "subject" varchar,
  "body" text not null,
  "channels" text not null,
  "audience_type" varchar not null default 'individual',
  "audience_ids" text,
  "status" varchar not null default 'queued',
  "sent_at" datetime,
  "delivery_stats" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("sender_id") references "users"("id") on delete cascade
);
CREATE INDEX "hub_messages_school_id_status_index" on "hub_messages"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "hub_message_deliveries"(
  "id" integer primary key autoincrement not null,
  "hub_message_id" integer not null,
  "recipient_user_id" integer,
  "channel" varchar not null,
  "recipient_address" varchar,
  "status" varchar not null default 'pending',
  "delivered_at" datetime,
  "read_at" datetime,
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("hub_message_id") references "hub_messages"("id") on delete cascade,
  foreign key("recipient_user_id") references "users"("id") on delete set null
);
CREATE INDEX "hub_message_deliveries_hub_message_id_channel_index" on "hub_message_deliveries"(
  "hub_message_id",
  "channel"
);
CREATE INDEX "hub_message_deliveries_recipient_user_id_read_at_index" on "hub_message_deliveries"(
  "recipient_user_id",
  "read_at"
);
CREATE TABLE IF NOT EXISTS "signable_documents"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "document_type" varchar not null,
  "content" text not null,
  "status" varchar not null default 'draft',
  "created_by" integer not null,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "document_signatures"(
  "id" integer primary key autoincrement not null,
  "document_id" integer not null,
  "signer_id" integer not null,
  "signer_role" varchar,
  "signature_hash" varchar not null,
  "ip_address" varchar,
  "signed_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("document_id") references "signable_documents"("id") on delete cascade,
  foreign key("signer_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "document_signatures_document_id_signer_id_unique" on "document_signatures"(
  "document_id",
  "signer_id"
);
CREATE TABLE IF NOT EXISTS "scholarships"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "type" varchar not null,
  "amount" numeric,
  "percentage" numeric,
  "criteria" text,
  "application_deadline" date,
  "slots" integer,
  "status" varchar not null default 'open',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "scholarship_applications"(
  "id" integer primary key autoincrement not null,
  "scholarship_id" integer not null,
  "student_id" integer not null,
  "school_id" integer not null,
  "motivation" text,
  "supporting_data" text,
  "status" varchar not null default 'pending',
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("scholarship_id") references "scholarships"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "scholarship_applications_scholarship_id_student_id_unique" on "scholarship_applications"(
  "scholarship_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "payment_gateway_configs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "provider" varchar not null,
  "credentials" text not null,
  "is_active" tinyint(1) not null default '0',
  "supports_cards" tinyint(1) not null default '1',
  "supports_mobile_money" tinyint(1) not null default '0',
  "supports_bank_transfer" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "payment_gateway_configs_school_id_provider_unique" on "payment_gateway_configs"(
  "school_id",
  "provider"
);
CREATE TABLE IF NOT EXISTS "payment_gateway_transactions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "config_id" integer not null,
  "invoice_id" integer,
  "student_id" integer,
  "provider_reference" varchar,
  "internal_reference" varchar not null,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "payment_method" varchar not null,
  "status" varchar not null default 'initiated',
  "provider_response" text,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("config_id") references "payment_gateway_configs"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE INDEX "payment_gateway_transactions_school_id_status_index" on "payment_gateway_transactions"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "payment_gateway_transactions_internal_reference_unique" on "payment_gateway_transactions"(
  "internal_reference"
);
CREATE TABLE IF NOT EXISTS "fee_penalty_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "grace_days" integer not null default '0',
  "penalty_type" varchar not null,
  "penalty_value" numeric not null,
  "frequency" varchar not null default 'once',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "fee_penalties"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "rule_id" integer not null,
  "invoice_id" integer not null,
  "student_id" integer not null,
  "amount" numeric not null,
  "applied_on" date not null,
  "status" varchar not null default 'applied',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("rule_id") references "fee_penalty_rules"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE INDEX "fee_penalties_invoice_id_applied_on_index" on "fee_penalties"(
  "invoice_id",
  "applied_on"
);
CREATE TABLE IF NOT EXISTS "refunds"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "payment_id" integer not null,
  "student_id" integer not null,
  "amount" numeric not null,
  "reason" varchar not null,
  "status" varchar not null default 'pending',
  "requested_by" integer not null,
  "approved_by" integer,
  "processed_at" datetime,
  "reference" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("payment_id") references "payments"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("requested_by") references "users"("id") on delete cascade,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "secure_documents"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "vault_type" varchar not null default 'exam_paper',
  "title" varchar not null,
  "encrypted_payload" text not null,
  "content_hash" varchar not null,
  "access_roles" text not null,
  "uploaded_by" integer not null,
  "exam_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("uploaded_by") references "users"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete set null
);
CREATE INDEX "secure_documents_school_id_vault_type_index" on "secure_documents"(
  "school_id",
  "vault_type"
);
CREATE TABLE IF NOT EXISTS "secure_document_access_logs"(
  "id" integer primary key autoincrement not null,
  "document_id" integer not null,
  "user_id" integer not null,
  "action" varchar not null,
  "ip_address" varchar,
  "accessed_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("document_id") references "secure_documents"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "behavior_points"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "points" integer not null,
  "category" varchar not null,
  "description" text not null,
  "recorded_by" integer not null,
  "recorded_on" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete cascade
);
CREATE INDEX "behavior_points_student_id_recorded_on_index" on "behavior_points"(
  "student_id",
  "recorded_on"
);
CREATE TABLE IF NOT EXISTS "student_interventions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "intervention_type" varchar not null,
  "status" varchar not null default 'open',
  "summary" text not null,
  "action_plan" text,
  "assigned_to" integer,
  "created_by" integer not null,
  "start_date" date not null,
  "follow_up_date" date,
  "closed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_tasks"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "description" text,
  "assigned_to" integer not null,
  "assigned_by" integer,
  "priority" varchar not null default 'normal',
  "status" varchar not null default 'pending',
  "due_date" date,
  "progress_percent" integer not null default '0',
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete cascade,
  foreign key("assigned_by") references "users"("id") on delete set null
);
CREATE INDEX "staff_tasks_assigned_to_status_index" on "staff_tasks"(
  "assigned_to",
  "status"
);
CREATE TABLE IF NOT EXISTS "staff_feed_posts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "author_id" integer not null,
  "body" text not null,
  "attachments" text,
  "visibility" varchar not null default 'staff',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_feed_comments"(
  "id" integer primary key autoincrement not null,
  "post_id" integer not null,
  "author_id" integer not null,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("post_id") references "staff_feed_posts"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "operations_alerts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "severity" varchar not null,
  "category" varchar not null,
  "title" varchar not null,
  "message" text not null,
  "metadata" text,
  "resolved_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "operations_alerts_school_id_resolved_at_index" on "operations_alerts"(
  "school_id",
  "resolved_at"
);
CREATE TABLE IF NOT EXISTS "retention_policies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "module" varchar not null,
  "retain_years" integer not null,
  "archive_action" varchar not null default 'archive',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "retention_policies_school_id_module_unique" on "retention_policies"(
  "school_id",
  "module"
);
CREATE TABLE IF NOT EXISTS "archived_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "source_table" varchar not null,
  "source_id" integer not null,
  "snapshot" text not null,
  "archived_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "archived_records_school_id_source_table_index" on "archived_records"(
  "school_id",
  "source_table"
);
CREATE TABLE IF NOT EXISTS "data_masking_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "module" varchar not null,
  "field" varchar not null,
  "visible_roles" text not null,
  "mask_pattern" varchar not null default '***',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "data_masking_rules_school_id_module_field_unique" on "data_masking_rules"(
  "school_id",
  "module",
  "field"
);
CREATE TABLE IF NOT EXISTS "api_clients"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "client_id" varchar not null,
  "client_secret_hash" varchar not null,
  "scopes" text not null,
  "is_active" tinyint(1) not null default '1',
  "last_used_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "api_clients_client_id_unique" on "api_clients"(
  "client_id"
);
CREATE TABLE IF NOT EXISTS "record_versions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "versionable_type" varchar not null,
  "versionable_id" integer not null,
  "version_number" integer not null,
  "snapshot" text not null,
  "changed_by" integer,
  "change_reason" varchar,
  "created_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("changed_by") references "users"("id") on delete set null
);
CREATE INDEX "record_versions_versionable_type_versionable_id_version_number_index" on "record_versions"(
  "versionable_type",
  "versionable_id",
  "version_number"
);
CREATE TABLE IF NOT EXISTS "curriculum_versions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer not null,
  "term_id" integer,
  "academic_year" varchar not null,
  "version_number" integer not null default '1',
  "syllabus" text,
  "learning_outcomes" text,
  "status" varchar not null default 'draft',
  "published_by" integer,
  "published_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null,
  foreign key("published_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "curriculum_versions_school_id_subject_id_academic_year_version_number_unique" on "curriculum_versions"(
  "school_id",
  "subject_id",
  "academic_year",
  "version_number"
);
CREATE TABLE IF NOT EXISTS "learning_outcome_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "outcome_code" varchar not null,
  "competency_level" varchar not null,
  "score" numeric,
  "evidence" text,
  "assessed_by" integer,
  "assessed_on" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("assessed_by") references "users"("id") on delete set null
);
CREATE INDEX "learning_outcome_records_student_id_subject_id_index" on "learning_outcome_records"(
  "student_id",
  "subject_id"
);
CREATE TABLE IF NOT EXISTS "assessment_categories"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer,
  "name" varchar not null,
  "type" varchar not null,
  "weight" numeric not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "continuous_assessments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "subject_id" integer not null,
  "category_id" integer not null,
  "term_id" integer,
  "title" varchar not null,
  "score" numeric not null,
  "max_score" numeric not null default '100',
  "assessed_on" date not null,
  "recorded_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("category_id") references "assessment_categories"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null,
  foreign key("recorded_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "gradebook_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer,
  "grade_level_id" integer,
  "category_weights" text not null,
  "pass_mark" numeric not null default '50',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("grade_level_id") references "grade_levels"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "subject_prerequisites"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer not null,
  "prerequisite_subject_id" integer not null,
  "minimum_grade" numeric not null default '50',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("prerequisite_subject_id") references "subjects"("id") on delete cascade
);
CREATE UNIQUE INDEX "subject_prerequisites_subject_id_prerequisite_subject_id_unique" on "subject_prerequisites"(
  "subject_id",
  "prerequisite_subject_id"
);
CREATE TABLE IF NOT EXISTS "promotion_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "conditions" text not null,
  "action" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "academic_calendar_entries"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "term_id" integer,
  "entry_type" varchar not null,
  "title" varchar not null,
  "start_date" date not null,
  "end_date" date,
  "is_holiday" tinyint(1) not null default '0',
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("term_id") references "terms"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "chart_of_accounts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "account_type" varchar not null,
  "parent_id" integer,
  "normal_balance" varchar not null default 'debit',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("parent_id") references "chart_of_accounts"("id") on delete set null
);
CREATE UNIQUE INDEX "chart_of_accounts_school_id_code_unique" on "chart_of_accounts"(
  "school_id",
  "code"
);
CREATE TABLE IF NOT EXISTS "journal_entries"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "reference" varchar not null,
  "entry_date" date not null,
  "description" text not null,
  "status" varchar not null default 'posted',
  "currency" varchar not null default 'USD',
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "journal_entries_reference_unique" on "journal_entries"(
  "reference"
);
CREATE TABLE IF NOT EXISTS "journal_lines"(
  "id" integer primary key autoincrement not null,
  "journal_entry_id" integer not null,
  "account_id" integer not null,
  "debit" numeric not null default '0',
  "credit" numeric not null default '0',
  "currency" varchar not null default 'USD',
  "memo" text,
  foreign key("journal_entry_id") references "journal_entries"("id") on delete cascade,
  foreign key("account_id") references "chart_of_accounts"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exchange_rates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "from_currency" varchar not null,
  "to_currency" varchar not null,
  "rate" numeric not null,
  "effective_date" date not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "exchange_rates_school_id_from_currency_to_currency_effective_date_unique" on "exchange_rates"(
  "school_id",
  "from_currency",
  "to_currency",
  "effective_date"
);
CREATE TABLE IF NOT EXISTS "instalment_plans"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "invoice_id" integer,
  "total_amount" numeric not null,
  "currency" varchar not null default 'USD',
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "instalment_schedule_items"(
  "id" integer primary key autoincrement not null,
  "plan_id" integer not null,
  "installment_number" integer not null,
  "due_date" date not null,
  "amount" numeric not null,
  "amount_paid" numeric not null default '0',
  "status" varchar not null default 'pending',
  "paid_at" datetime,
  foreign key("plan_id") references "instalment_plans"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "bank_statement_lines"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "transaction_date" date not null,
  "reference" varchar,
  "description" text,
  "amount" numeric not null,
  "currency" varchar not null default 'USD',
  "matched_payment_id" integer,
  "match_status" varchar not null default 'unmatched',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("matched_payment_id") references "payments"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "revenue_recognition_rules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "recognition_method" varchar not null,
  "config" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "workflow_delegations"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "delegator_id" integer not null,
  "delegate_id" integer not null,
  "starts_on" date not null,
  "ends_on" date not null,
  "scope" varchar not null default 'all_workflows',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("delegator_id") references "users"("id") on delete cascade,
  foreign key("delegate_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_contracts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "contract_type" varchar not null,
  "start_date" date not null,
  "end_date" date,
  "salary" numeric,
  "status" varchar not null default 'active',
  "renewal_due_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "performance_reviews"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "staff_user_id" integer not null,
  "reviewer_id" integer,
  "period" varchar not null,
  "overall_score" numeric,
  "summary" text,
  "criteria_scores" text,
  "status" varchar not null default 'draft',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("staff_user_id") references "users"("id") on delete cascade,
  foreign key("reviewer_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "staff_certifications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "certification_name" varchar not null,
  "issuer" varchar,
  "issued_on" date,
  "expires_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_disciplinary_cases"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "staff_user_id" integer not null,
  "category" varchar not null,
  "description" text not null,
  "outcome" text,
  "status" varchar not null default 'open',
  "recorded_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("staff_user_id") references "users"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "staff_shifts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "shift_date" date not null,
  "start_time" time not null,
  "end_time" time not null,
  "location" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "admission_scores"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "enrollment_application_id" integer,
  "applicant_name" varchar not null,
  "academic_score" numeric not null default '0',
  "interview_score" numeric not null default '0',
  "total_score" numeric not null default '0',
  "recommendation" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("enrollment_application_id") references "enrollment_applications"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "alumni_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer,
  "full_name" varchar not null,
  "graduation_year" varchar not null,
  "email" varchar,
  "phone" varchar,
  "current_occupation" varchar,
  "engagement_history" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "student_timeline_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "event_type" varchar not null,
  "title" varchar not null,
  "description" text,
  "metadata" text,
  "occurred_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE INDEX "student_timeline_events_student_id_occurred_at_index" on "student_timeline_events"(
  "student_id",
  "occurred_at"
);
CREATE TABLE IF NOT EXISTS "question_bank_items"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subject_id" integer,
  "question_text" text not null,
  "question_type" varchar not null default 'multiple_choice',
  "options" text,
  "correct_answer" varchar,
  "difficulty" varchar not null default 'medium',
  "tags" text,
  "marks" integer not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "cbt_exam_sessions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_id" integer,
  "student_id" integer not null,
  "question_ids" text not null,
  "started_at" datetime,
  "submitted_at" datetime,
  "score" numeric,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete set null,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "cbt_responses"(
  "id" integer primary key autoincrement not null,
  "session_id" integer not null,
  "question_id" integer not null,
  "answer" text,
  "is_correct" tinyint(1),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("session_id") references "cbt_exam_sessions"("id") on delete cascade,
  foreign key("question_id") references "question_bank_items"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exam_anti_cheat_logs"(
  "id" integer primary key autoincrement not null,
  "session_id" integer not null,
  "event_type" varchar not null,
  "metadata" text,
  "logged_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("session_id") references "cbt_exam_sessions"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "remark_requests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_result_id" integer not null,
  "student_id" integer not null,
  "reason" text not null,
  "status" varchar not null default 'pending',
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("exam_result_id") references "exam_results"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "message_campaigns"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "channels" text not null,
  "audience_filter" text not null,
  "message_body" text not null,
  "scheduled_at" datetime,
  "sent_at" datetime,
  "status" varchar not null default 'draft',
  "created_by" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "communication_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "channel_preferences" text not null,
  "topic_preferences" text,
  "marketing_opt_in" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "communication_preferences_user_id_unique" on "communication_preferences"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "parent_engagement_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "parent_user_id" integer not null,
  "event_type" varchar not null,
  "title" varchar not null,
  "attended_at" datetime,
  "attended" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("parent_user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "maintenance_schedules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "asset_type" varchar not null,
  "asset_id" integer,
  "title" varchar not null,
  "frequency" varchar not null,
  "next_due_date" date not null,
  "status" varchar not null default 'scheduled',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "facility_tasks"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "task_type" varchar not null,
  "location" varchar not null,
  "scheduled_date" date not null,
  "assigned_to" integer,
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("assigned_to") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "room_utilization_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "room_id" integer not null,
  "log_date" date not null,
  "hours_used" integer not null default '0',
  "capacity" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("room_id") references "rooms"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "transport_route_stops"(
  "id" integer primary key autoincrement not null,
  "route_id" integer not null,
  "stop_name" varchar not null,
  "latitude" numeric,
  "longitude" numeric,
  "stop_order" integer not null default '1',
  "pickup_time" time,
  foreign key("route_id") references "transport_routes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "gps_tracking_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "vehicle_id" integer not null,
  "latitude" numeric not null,
  "longitude" numeric not null,
  "speed" numeric,
  "recorded_at" datetime not null,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "fuel_consumption_logs"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "vehicle_id" integer not null,
  "log_date" date not null,
  "litres" numeric not null,
  "cost" numeric,
  "odometer" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "transport_trips"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "route_id" integer not null,
  "vehicle_id" integer,
  "trip_date" date not null,
  "departure_time" time,
  "arrival_time" time,
  "status" varchar not null default 'scheduled',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("route_id") references "transport_routes"("id") on delete cascade,
  foreign key("vehicle_id") references "vehicles"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "library_reservations"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "book_id" integer not null,
  "student_id" integer,
  "user_id" integer,
  "reserved_at" datetime not null,
  "expires_at" datetime,
  "status" varchar not null default 'queued',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("book_id") references "library_books"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "digital_library_resources"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "resource_type" varchar not null,
  "file_path" varchar,
  "isbn" varchar,
  "access_roles" text,
  "view_count" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "library_fines"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "loan_id" integer not null,
  "amount" numeric not null,
  "status" varchar not null default 'pending',
  "paid_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("loan_id") references "library_loans"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "compliance_requirements"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "authority" varchar not null,
  "requirement_code" varchar not null,
  "title" varchar not null,
  "due_date" date,
  "status" varchar not null default 'pending',
  "evidence" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "integration_connectors"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "provider" varchar not null,
  "connector_type" varchar not null,
  "config" text not null,
  "is_active" tinyint(1) not null default '0',
  "last_sync_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "integration_connectors_school_id_provider_connector_type_unique" on "integration_connectors"(
  "school_id",
  "provider",
  "connector_type"
);
CREATE TABLE IF NOT EXISTS "abac_policies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "resource" varchar not null,
  "conditions" text not null,
  "effect" varchar not null default 'allow',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "device_sessions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "device_name" varchar,
  "ip_address" varchar,
  "user_agent" text,
  "last_active_at" datetime,
  "revoked_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "group_policies"(
  "id" integer primary key autoincrement not null,
  "parent_school_id" integer not null,
  "policy_code" varchar not null,
  "name" varchar not null,
  "rules" text not null,
  "enforce_on_branches" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parent_school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "group_policies_parent_school_id_policy_code_unique" on "group_policies"(
  "parent_school_id",
  "policy_code"
);
CREATE TABLE IF NOT EXISTS "cross_school_transfers"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "from_school_id" integer not null,
  "to_school_id" integer not null,
  "status" varchar not null default 'pending',
  "requested_by" integer not null,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("from_school_id") references "schools"("id") on delete cascade,
  foreign key("to_school_id") references "schools"("id") on delete cascade,
  foreign key("requested_by") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "shared_teacher_assignments"(
  "id" integer primary key autoincrement not null,
  "teacher_id" integer not null,
  "school_id" integer not null,
  "allocation_percent" numeric not null default '100',
  "starts_on" date not null,
  "ends_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "license_keys"(
  "id" integer primary key autoincrement not null,
  "key_prefix" varchar not null,
  "key_hash" varchar not null,
  "plan_type" varchar not null,
  "duration_months" integer,
  "status" varchar not null default 'unused',
  "school_id" integer,
  "activated_at" datetime,
  "expires_at" datetime,
  "revoked_at" datetime,
  "customer_name" varchar,
  "customer_email" varchar,
  "notes" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "amount" numeric,
  "currency" varchar not null default 'USD',
  "paid_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "license_keys_school_id_status_index" on "license_keys"(
  "school_id",
  "status"
);
CREATE INDEX "license_keys_status_expires_at_index" on "license_keys"(
  "status",
  "expires_at"
);
CREATE INDEX "license_keys_key_prefix_index" on "license_keys"("key_prefix");
CREATE UNIQUE INDEX "license_keys_key_hash_unique" on "license_keys"(
  "key_hash"
);
CREATE INDEX "payments_school_date_status_idx" on "payments"(
  "school_id",
  "date",
  "status"
);
CREATE INDEX "payments_school_currency_status_idx" on "payments"(
  "school_id",
  "currency",
  "status"
);
CREATE INDEX "payments_school_student_date_idx" on "payments"(
  "school_id",
  "student_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "purchase_requisitions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "requested_by" integer not null,
  "department_id" integer,
  "title" varchar not null,
  "description" text,
  "estimated_cost" numeric not null default('0'),
  "status" varchar not null default('draft'),
  "workflow_instance_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "spend_type" varchar not null default 'procurement',
  "vendor_id" integer,
  "amount_paid" numeric,
  "payment_method" varchar,
  "payment_reference" varchar,
  "disbursed_at" datetime,
  "disbursed_by" integer,
  "transaction_id" integer,
  foreign key("workflow_instance_id") references workflow_instances("id") on delete set null on update no action,
  foreign key("department_id") references departments("id") on delete set null on update no action,
  foreign key("requested_by") references users("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("vendor_id") references "vendors"("id") on delete set null,
  foreign key("disbursed_by") references "users"("id") on delete set null,
  foreign key("transaction_id") references "transactions"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_trips"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "destination" varchar,
  "trip_date" date not null,
  "return_date" date,
  "fee_amount" numeric not null default '0',
  "currency" varchar not null default 'USD',
  "capacity" integer,
  "is_active" tinyint(1) not null default '1',
  "open_for_registration" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "school_trips_school_id_is_active_index" on "school_trips"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "school_trip_enrollments"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "school_trip_id" integer not null,
  "student_id" integer not null,
  "invoice_id" integer,
  "status" varchar not null default 'enrolled',
  "enrolled_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("school_trip_id") references "school_trips"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("invoice_id") references "invoices"("id") on delete set null
);
CREATE UNIQUE INDEX "school_trip_enrollments_school_trip_id_student_id_unique" on "school_trip_enrollments"(
  "school_trip_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "streams"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "streams_school_id_name_unique" on "streams"(
  "school_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "houses"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "color" varchar,
  "teacher_id" integer,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete set null
);
CREATE UNIQUE INDEX "houses_school_id_name_unique" on "houses"(
  "school_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "classes"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "name" varchar not null,
  "form" varchar,
  "school" varchar,
  "capacity" integer not null default('40'),
  "current_enrollment" integer not null default('0'),
  "teacher_id" integer,
  "grade_level_id" integer,
  "room_id" integer,
  "status" varchar not null default('active'),
  "created_at" datetime,
  "updated_at" datetime,
  "stream_id" integer,
  foreign key("room_id") references rooms("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete set null on update no action,
  foreign key("stream_id") references "streams"("id") on delete set null
);
CREATE INDEX "classes_school_id_index" on "classes"("school_id");
CREATE UNIQUE INDEX "classes_school_id_name_unique" on "classes"(
  "school_id",
  "name"
);
CREATE INDEX "classes_teacher_id_index" on "classes"("teacher_id");
CREATE TABLE IF NOT EXISTS "enrollments"(
  "id" integer primary key autoincrement not null,
  "student_id" integer not null,
  "class_id" integer not null,
  "academic_year" varchar not null,
  "enrolled_at" date not null,
  "left_at" date,
  "status" varchar not null default('active'),
  "school_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  "stream_id" integer,
  "house_id" integer,
  "reason" varchar,
  "changed_by" integer,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("class_id") references classes("id") on delete cascade on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("stream_id") references "streams"("id") on delete set null,
  foreign key("house_id") references "houses"("id") on delete set null,
  foreign key("changed_by") references "users"("id") on delete set null
);
CREATE INDEX "enrollments_class_id_academic_year_index" on "enrollments"(
  "class_id",
  "academic_year"
);
CREATE INDEX "enrollments_student_id_academic_year_index" on "enrollments"(
  "student_id",
  "academic_year"
);
CREATE UNIQUE INDEX "enrollments_student_id_class_id_academic_year_unique" on "enrollments"(
  "student_id",
  "class_id",
  "academic_year"
);
CREATE TABLE IF NOT EXISTS "grade_level_subjects"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "grade_level_id" integer not null,
  "subject_id" integer not null,
  "stream_id" integer,
  "is_core" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("grade_level_id") references "grade_levels"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("stream_id") references "streams"("id") on delete set null
);
CREATE UNIQUE INDEX "grade_stream_subject_unique" on "grade_level_subjects"(
  "grade_level_id",
  "subject_id",
  "stream_id"
);
CREATE TABLE IF NOT EXISTS "student_status_events"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer not null,
  "from_status" varchar,
  "to_status" varchar not null,
  "action" varchar not null,
  "reason" text,
  "effective_date" date,
  "performed_by" integer,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("performed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "fee_structures"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "class_id" integer,
  "class_name" varchar not null,
  "category" varchar not null,
  "amount" numeric not null,
  "currency" varchar not null default('USD'),
  "created_at" datetime,
  "updated_at" datetime,
  "fee_category_id" integer,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("fee_category_id") references "fee_categories"("id") on delete set null
);
CREATE INDEX "fee_structures_class_id_index" on "fee_structures"("class_id");
CREATE INDEX "fee_structures_school_id_index" on "fee_structures"("school_id");
CREATE TABLE IF NOT EXISTS "attendance"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer not null,
  "class_id" integer,
  "teacher_id" integer,
  "subject_id" integer,
  "lesson_type" varchar,
  "date" date not null,
  "status" varchar not null,
  "time_in" time,
  "time_out" time,
  "remarks" text,
  "parent_notified" tinyint(1) not null default('0'),
  "notification_sent_at" datetime,
  "marked_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "submitted_at" datetime,
  "locked_at" datetime,
  "locked_by" integer,
  foreign key("subject_id") references subjects("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete set null on update no action,
  foreign key("marked_by") references users("id") on delete set null on update no action,
  foreign key("locked_by") references "users"("id") on delete set null
);
CREATE INDEX "attendance_class_id_date_index" on "attendance"(
  "class_id",
  "date"
);
CREATE INDEX "attendance_school_id_index" on "attendance"("school_id");
CREATE UNIQUE INDEX "attendance_student_id_date_class_id_unique" on "attendance"(
  "student_id",
  "date",
  "class_id"
);
CREATE TABLE IF NOT EXISTS "attendance_sessions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "class_id" integer not null,
  "subject_id" integer,
  "teacher_id" integer,
  "date" date not null,
  "period" varchar,
  "submitted_at" datetime,
  "locked_at" datetime,
  "locked_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("teacher_id") references "teachers"("id") on delete set null,
  foreign key("locked_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "attendance_sessions_unique" on "attendance_sessions"(
  "school_id",
  "class_id",
  "date",
  "subject_id",
  "period"
);
CREATE TABLE IF NOT EXISTS "lesson_plans"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "title" varchar not null,
  "plan_type" varchar not null default 'lesson',
  "planned_date" date,
  "topic" varchar,
  "objectives" text,
  "outcomes" text,
  "activities" text,
  "resources" text,
  "status" varchar not null default 'draft',
  "completed_at" datetime,
  "submitted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "syllabus_topics"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "subject_id" integer not null,
  "class_id" integer,
  "title" varchar not null,
  "chapter" varchar,
  "sort_order" integer not null default '0',
  "objectives" text,
  "status" varchar not null default 'planned',
  "coverage_percent" integer not null default '0',
  "completed_on" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "teaching_resources"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "subject_id" integer,
  "class_id" integer,
  "title" varchar not null,
  "resource_type" varchar not null default 'notes',
  "topic" varchar,
  "term" varchar,
  "file_url" varchar,
  "description" text,
  "shared" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("class_id") references "classes"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "assignment_submissions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "assignment_id" integer not null,
  "student_id" integer not null,
  "status" varchar not null default 'submitted',
  "file_url" varchar,
  "content" text,
  "score" numeric,
  "teacher_comment" text,
  "submitted_at" datetime,
  "graded_at" datetime,
  "returned_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("assignment_id") references "assignments"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade
);
CREATE UNIQUE INDEX "assignment_submissions_assignment_id_student_id_unique" on "assignment_submissions"(
  "assignment_id",
  "student_id"
);
CREATE TABLE IF NOT EXISTS "online_lessons"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "title" varchar not null,
  "lesson_type" varchar not null default 'live',
  "scheduled_at" datetime,
  "meeting_url" varchar,
  "recording_url" varchar,
  "description" text,
  "status" varchar not null default 'scheduled',
  "created_at" datetime,
  "updated_at" datetime,
  "provider" varchar not null default 'zoom',
  "meeting_id" varchar,
  "passcode" varchar,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "report_card_narratives"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "student_id" integer not null,
  "class_id" integer,
  "term_id" integer,
  "academic_comment" text,
  "behaviour_comment" text,
  "recommendations" text,
  "strengths" text,
  "areas_for_improvement" text,
  "status" varchar not null default 'draft',
  "submitted_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("term_id") references "terms"("id") on delete set null
);
CREATE UNIQUE INDEX "report_card_narratives_unique" on "report_card_narratives"(
  "teacher_id",
  "student_id",
  "term_id"
);
CREATE TABLE IF NOT EXISTS "timetable_change_requests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "request_type" varchar not null default 'change',
  "details" text not null,
  "preferred_slot" varchar,
  "status" varchar not null default 'pending',
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "review_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "class_substitutions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "class_id" integer not null,
  "subject_id" integer,
  "absent_teacher_id" integer not null,
  "substitute_teacher_id" integer,
  "leave_request_id" integer,
  "date" date not null,
  "period" varchar,
  "status" varchar not null default 'open',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete cascade,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("absent_teacher_id") references "teachers"("id") on delete cascade,
  foreign key("substitute_teacher_id") references "teachers"("id") on delete set null,
  foreign key("leave_request_id") references "leave_requests"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "class_participation_records"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "student_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "recorded_on" date not null,
  "score" integer not null default '3',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "teachers"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "teacher_notifications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "type" varchar not null default 'info',
  "title" varchar not null,
  "body" text,
  "link" varchar,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "teacher_notifications_user_id_read_at_index" on "teacher_notifications"(
  "user_id",
  "read_at"
);
CREATE TABLE IF NOT EXISTS "fee_groups"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "fee_groups_school_id_name_unique" on "fee_groups"(
  "school_id",
  "name"
);
CREATE INDEX "fee_groups_school_id_index" on "fee_groups"("school_id");
CREATE TABLE IF NOT EXISTS "fee_group_items"(
  "id" integer primary key autoincrement not null,
  "fee_group_id" integer not null,
  "fee_category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("fee_group_id") references "fee_groups"("id") on delete cascade,
  foreign key("fee_category_id") references "fee_categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "fee_group_items_fee_group_id_fee_category_id_unique" on "fee_group_items"(
  "fee_group_id",
  "fee_category_id"
);
CREATE TABLE IF NOT EXISTS "student_categories"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "student_categories_school_id_name_unique" on "student_categories"(
  "school_id",
  "name"
);
CREATE INDEX "student_categories_school_id_index" on "student_categories"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "fee_discounts"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "description" text,
  "discount_type" varchar not null,
  "value" numeric not null,
  "fee_category_id" integer,
  "student_category_id" integer,
  "starts_on" date,
  "ends_on" date,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("fee_category_id") references "fee_categories"("id") on delete set null,
  foreign key("student_category_id") references "student_categories"("id") on delete set null
);
CREATE INDEX "fee_discounts_school_id_is_active_index" on "fee_discounts"(
  "school_id",
  "is_active"
);
CREATE TABLE IF NOT EXISTS "designations"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "designations_school_id_name_unique" on "designations"(
  "school_id",
  "name"
);
CREATE INDEX "designations_school_id_index" on "designations"("school_id");
CREATE TABLE IF NOT EXISTS "employees"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "employee_number" varchar,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "designation_id" integer,
  "department_id" integer,
  "joining_date" date,
  "employment_type" varchar not null default 'full_time',
  "status" varchar not null default 'active',
  "base_salary" numeric,
  "salary_currency" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("designation_id") references "designations"("id") on delete set null,
  foreign key("department_id") references "departments"("id") on delete set null
);
CREATE INDEX "employees_school_id_status_index" on "employees"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "employees_school_id_employee_number_unique" on "employees"(
  "school_id",
  "employee_number"
);
CREATE TABLE IF NOT EXISTS "staff_attendances"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "date" date not null,
  "staff_type" varchar not null,
  "staff_id" integer not null,
  "status" varchar not null,
  "remarks" varchar,
  "recorded_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("recorded_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "staff_attendance_unique" on "staff_attendances"(
  "school_id",
  "date",
  "staff_type",
  "staff_id"
);
CREATE INDEX "staff_attendances_school_id_date_index" on "staff_attendances"(
  "school_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "leave_types"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "default_days" integer,
  "is_paid" tinyint(1) not null default '1',
  "is_active" tinyint(1) not null default '1',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "leave_types_school_id_name_unique" on "leave_types"(
  "school_id",
  "name"
);
CREATE INDEX "leave_types_school_id_index" on "leave_types"("school_id");
CREATE TABLE IF NOT EXISTS "certificate_templates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "certificate_type" varchar not null,
  "title" varchar not null,
  "body_html" text not null,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "certificate_templates_school_id_name_unique" on "certificate_templates"(
  "school_id",
  "name"
);
CREATE INDEX "certificate_templates_school_id_index" on "certificate_templates"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "exam_schedules"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "exam_id" integer not null,
  "class_id" integer,
  "subject_id" integer,
  "room_id" integer,
  "starts_at" datetime not null,
  "ends_at" datetime,
  "duration_minutes" integer,
  "invigilator" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("exam_id") references "exams"("id") on delete cascade,
  foreign key("class_id") references "classes"("id") on delete set null,
  foreign key("subject_id") references "subjects"("id") on delete set null,
  foreign key("room_id") references "rooms"("id") on delete set null
);
CREATE INDEX "exam_schedules_school_id_exam_id_index" on "exam_schedules"(
  "school_id",
  "exam_id"
);
CREATE INDEX "exam_schedules_school_id_starts_at_index" on "exam_schedules"(
  "school_id",
  "starts_at"
);
CREATE TABLE IF NOT EXISTS "income_heads"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "income_heads_school_id_name_unique" on "income_heads"(
  "school_id",
  "name"
);
CREATE INDEX "income_heads_school_id_index" on "income_heads"("school_id");
CREATE TABLE IF NOT EXISTS "expense_heads"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "code" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "expense_heads_school_id_name_unique" on "expense_heads"(
  "school_id",
  "name"
);
CREATE INDEX "expense_heads_school_id_index" on "expense_heads"("school_id");
CREATE TABLE IF NOT EXISTS "library_members"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "member_type" varchar not null,
  "member_id" integer,
  "member_number" varchar,
  "name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "status" varchar not null default 'active',
  "joined_on" date,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "library_members_school_id_status_index" on "library_members"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "library_members_school_id_member_number_unique" on "library_members"(
  "school_id",
  "member_number"
);
CREATE TABLE IF NOT EXISTS "school_currencies"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "symbol" varchar,
  "is_default" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_currencies_school_id_code_unique" on "school_currencies"(
  "school_id",
  "code"
);
CREATE INDEX "school_currencies_school_id_index" on "school_currencies"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "students"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_number" varchar not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "full_name" varchar not null,
  "date_of_birth" date,
  "gender" varchar,
  "national_id" varchar,
  "phone" varchar,
  "email" varchar,
  "address" text,
  "suburb" varchar,
  "class" varchar,
  "school" varchar,
  "status" varchar not null default('active'),
  "previous_school" varchar,
  "balance" numeric not null default('0'),
  "currency" varchar not null default('USD'),
  "user_id" integer,
  "class_id" integer,
  "grade_level_id" integer,
  "guardian_first_name" varchar,
  "guardian_last_name" varchar,
  "guardian_phone" varchar,
  "guardian_email" varchar,
  "guardian_relationship" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "repetition_count" integer not null default('0'),
  "stream_id" integer,
  "house_id" integer,
  "status_reason" varchar,
  "status_changed_at" datetime,
  "exited_at" date,
  "student_category_id" integer,
  "photo_url" varchar,
  "roll_number" varchar,
  "blood_group" varchar,
  "passport_number" varchar,
  "social_links" text,
  foreign key("house_id") references houses("id") on delete set null on update no action,
  foreign key("stream_id") references streams("id") on delete set null on update no action,
  foreign key("grade_level_id") references grade_levels("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("user_id") references users("id") on delete set null on update no action,
  foreign key("class_id") references classes("id") on delete set null on update no action,
  foreign key("student_category_id") references "student_categories"("id") on delete set null
);
CREATE INDEX "students_class_id_index" on "students"("class_id");
CREATE INDEX "students_school_id_index" on "students"("school_id");
CREATE UNIQUE INDEX "students_school_id_student_number_unique" on "students"(
  "school_id",
  "student_number"
);
CREATE TABLE IF NOT EXISTS "leave_requests"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "requested_by" integer,
  "type" varchar not null default('annual'),
  "start_date" date not null,
  "end_date" date not null,
  "days" integer not null default('1'),
  "reason" text,
  "status" varchar not null default('pending'),
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "review_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "leave_type_id" integer,
  foreign key("reviewed_by") references users("id") on delete set null on update no action,
  foreign key("requested_by") references users("id") on delete set null on update no action,
  foreign key("teacher_id") references teachers("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("leave_type_id") references "leave_types"("id") on delete set null
);
CREATE INDEX "leave_requests_school_id_status_index" on "leave_requests"(
  "school_id",
  "status"
);
CREATE INDEX "leave_requests_teacher_id_start_date_index" on "leave_requests"(
  "teacher_id",
  "start_date"
);
CREATE TABLE IF NOT EXISTS "teachers"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "employee_id" varchar not null,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "name" varchar not null,
  "email" varchar not null,
  "phone" varchar,
  "address" text,
  "subject" varchar,
  "department" varchar,
  "qualification" varchar,
  "joining_date" date,
  "status" varchar not null default('active'),
  "base_salary" numeric,
  "salary_currency" varchar not null default('USD'),
  "allowances" text,
  "deductions" text,
  "bank_name" varchar,
  "bank_account_number" varchar,
  "payment_method" varchar not null default('bank_transfer'),
  "user_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "employment_type" varchar not null default('full_time'),
  "period_rate" numeric,
  "designation_id" integer,
  "blood_group" varchar,
  "passport_number" varchar,
  "social_links" text,
  "emergency_contact_name" varchar,
  "emergency_contact_phone" varchar,
  foreign key("user_id") references users("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("designation_id") references "designations"("id") on delete set null
);
CREATE UNIQUE INDEX "teachers_school_id_email_unique" on "teachers"(
  "school_id",
  "email"
);
CREATE UNIQUE INDEX "teachers_school_id_employee_id_unique" on "teachers"(
  "school_id",
  "employee_id"
);
CREATE INDEX "teachers_school_id_index" on "teachers"("school_id");
CREATE TABLE IF NOT EXISTS "certificates"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "student_id" integer,
  "certificate_type" varchar not null,
  "title" varchar not null,
  "verification_code" varchar not null,
  "content_html" text not null,
  "metadata" text,
  "issued_by" integer,
  "issued_at" datetime not null,
  "revoked_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "certificate_template_id" integer,
  foreign key("issued_by") references users("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("certificate_template_id") references "certificate_templates"("id") on delete set null
);
CREATE INDEX "certificates_school_id_certificate_type_index" on "certificates"(
  "school_id",
  "certificate_type"
);
CREATE UNIQUE INDEX "certificates_verification_code_unique" on "certificates"(
  "verification_code"
);
CREATE TABLE IF NOT EXISTS "invoices"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "invoice_number" varchar not null,
  "student_id" integer not null,
  "parent_id" integer,
  "fee_structure_id" integer,
  "description" varchar not null,
  "amount" numeric not null,
  "amount_paid" numeric not null default('0'),
  "balance" numeric not null,
  "currency" varchar not null default('USD'),
  "due_date" date not null,
  "status" varchar not null default('pending'),
  "created_at" datetime,
  "updated_at" datetime,
  "original_amount" numeric,
  "discount_amount" numeric not null default '0',
  "fee_discount_id" integer,
  "fee_group_id" integer,
  foreign key("fee_structure_id") references fee_structures("id") on delete set null on update no action,
  foreign key("parent_id") references users("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("fee_discount_id") references "fee_discounts"("id") on delete set null,
  foreign key("fee_group_id") references "fee_groups"("id") on delete set null
);
CREATE INDEX "invoices_parent_id_index" on "invoices"("parent_id");
CREATE INDEX "invoices_school_id_index" on "invoices"("school_id");
CREATE UNIQUE INDEX "invoices_school_id_invoice_number_unique" on "invoices"(
  "school_id",
  "invoice_number"
);
CREATE INDEX "invoices_school_status_due_idx" on "invoices"(
  "school_id",
  "status",
  "due_date"
);
CREATE INDEX "invoices_school_student_status_idx" on "invoices"(
  "school_id",
  "student_id",
  "status"
);
CREATE INDEX "invoices_student_id_index" on "invoices"("student_id");
CREATE TABLE IF NOT EXISTS "school_languages"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "code" varchar not null,
  "name" varchar not null,
  "is_default" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_languages_school_id_code_unique" on "school_languages"(
  "school_id",
  "code"
);
CREATE INDEX "school_languages_school_id_index" on "school_languages"(
  "school_id"
);
CREATE TABLE IF NOT EXISTS "transactions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer,
  "student_id" integer,
  "payment_id" integer,
  "invoice_id" integer,
  "payroll_id" integer,
  "type" varchar not null,
  "category" varchar not null default('student'),
  "description" varchar not null,
  "reference" varchar,
  "debit" numeric not null default('0'),
  "credit" numeric not null default('0'),
  "balance" numeric not null,
  "currency" varchar not null default('USD'),
  "status" varchar not null default('completed'),
  "payment_method" varchar,
  "invoice_number" varchar,
  "created_by" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "income_head_id" integer,
  "expense_head_id" integer,
  foreign key("payroll_id") references payroll("id") on delete set null on update no action,
  foreign key("school_id") references schools("id") on delete set null on update no action,
  foreign key("student_id") references students("id") on delete cascade on update no action,
  foreign key("payment_id") references payments("id") on delete set null on update no action,
  foreign key("invoice_id") references invoices("id") on delete set null on update no action,
  foreign key("created_by") references users("id") on delete set null on update no action,
  foreign key("income_head_id") references "income_heads"("id") on delete set null,
  foreign key("expense_head_id") references "expense_heads"("id") on delete set null
);
CREATE INDEX "transactions_category_status_index" on "transactions"(
  "category",
  "status"
);
CREATE INDEX "transactions_school_created_idx" on "transactions"(
  "school_id",
  "created_at"
);
CREATE INDEX "transactions_school_id_index" on "transactions"("school_id");
CREATE INDEX "transactions_school_student_created_idx" on "transactions"(
  "school_id",
  "student_id",
  "created_at"
);
CREATE INDEX "transactions_student_id_index" on "transactions"("student_id");
CREATE TABLE IF NOT EXISTS "library_loans"(
  "id" integer primary key autoincrement not null,
  "book_id" integer not null,
  "student_id" integer,
  "borrowed_at" date not null,
  "due_at" date not null,
  "returned_at" date,
  "fine_amount" numeric not null default('0'),
  "status" varchar not null default('borrowed'),
  "created_at" datetime,
  "updated_at" datetime,
  "library_member_id" integer,
  foreign key("book_id") references library_books("id") on delete cascade on update no action,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("library_member_id") references "library_members"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_domains"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "domain" varchar not null,
  "is_primary" tinyint(1) not null default '0',
  "is_verified" tinyint(1) not null default '0',
  "status" varchar not null default 'active',
  "verified_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "verification_token" varchar,
  foreign key("school_id") references "schools"("id") on delete cascade
);
CREATE INDEX "school_domains_school_id_status_index" on "school_domains"(
  "school_id",
  "status"
);
CREATE UNIQUE INDEX "school_domains_domain_unique" on "school_domains"(
  "domain"
);
CREATE TABLE IF NOT EXISTS "school_backups"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "path" varchar not null,
  "status" varchar not null default 'completed',
  "table_count" integer not null default '0',
  "row_count" integer not null default '0',
  "file_size" integer not null default '0',
  "created_by" integer,
  "restored_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "school_backups_school_id_created_at_index" on "school_backups"(
  "school_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "help_desk_tickets"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "ticket_number" varchar not null,
  "subject" varchar not null,
  "description" text,
  "category" varchar not null default 'general',
  "priority" varchar not null default 'normal',
  "status" varchar not null default 'open',
  "requester_name" varchar not null,
  "requester_email" varchar,
  "requester_phone" varchar,
  "student_id" integer,
  "assigned_to" integer,
  "created_by" integer,
  "resolved_at" datetime,
  "closed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("student_id") references "students"("id") on delete set null,
  foreign key("assigned_to") references "users"("id") on delete set null,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "help_desk_tickets_school_id_ticket_number_unique" on "help_desk_tickets"(
  "school_id",
  "ticket_number"
);
CREATE INDEX "help_desk_tickets_school_id_status_index" on "help_desk_tickets"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "job_postings"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "title" varchar not null,
  "department" varchar,
  "employment_type" varchar not null default 'full_time',
  "location" varchar,
  "description" text,
  "requirements" text,
  "openings" integer not null default '1',
  "status" varchar not null default 'draft',
  "published_at" datetime,
  "closes_at" datetime,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("created_by") references "users"("id") on delete set null
);
CREATE INDEX "job_postings_school_id_status_index" on "job_postings"(
  "school_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "job_applications"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "job_posting_id" integer not null,
  "applicant_name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "resume_url" varchar,
  "cover_letter" text,
  "status" varchar not null default 'submitted',
  "notes" text,
  "reviewed_by" integer,
  "reviewed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("job_posting_id") references "job_postings"("id") on delete cascade,
  foreign key("reviewed_by") references "users"("id") on delete set null
);
CREATE INDEX "job_applications_school_id_status_index" on "job_applications"(
  "school_id",
  "status"
);
CREATE INDEX "job_applications_job_posting_id_status_index" on "job_applications"(
  "job_posting_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "budgets"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "name" varchar not null,
  "fiscal_year" varchar not null,
  "department" varchar,
  "allocated_amount" numeric not null,
  "spent_amount" numeric not null default('0'),
  "currency" varchar not null default('USD'),
  "created_at" datetime,
  "updated_at" datetime,
  "status" varchar not null default 'draft',
  "notes" text,
  "approved_by" integer,
  "approved_at" datetime,
  foreign key("school_id") references schools("id") on delete cascade on update no action,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "school_user_memberships"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "user_id" integer not null,
  "role" varchar,
  "is_default" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "school_user_memberships_school_id_user_id_unique" on "school_user_memberships"(
  "school_id",
  "user_id"
);
CREATE INDEX "school_user_memberships_user_id_is_default_index" on "school_user_memberships"(
  "user_id",
  "is_default"
);
CREATE TABLE IF NOT EXISTS "webhook_subscriptions"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "event_type" varchar not null,
  "target_url" varchar not null,
  "secret_hash" varchar,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "secret" text,
  foreign key("school_id") references schools("id") on delete cascade on update no action
);
CREATE TABLE IF NOT EXISTS "webhook_deliveries"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "subscription_id" integer not null,
  "event_type" varchar not null,
  "payload" text not null,
  "status" varchar not null default 'pending',
  "attempts" integer not null default '0',
  "response_status" integer,
  "response_body" text,
  "last_error" text,
  "next_retry_at" datetime,
  "delivered_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("subscription_id") references "webhook_subscriptions"("id") on delete cascade
);
CREATE INDEX "webhook_deliveries_school_id_status_index" on "webhook_deliveries"(
  "school_id",
  "status"
);
CREATE INDEX "webhook_deliveries_status_next_retry_at_index" on "webhook_deliveries"(
  "status",
  "next_retry_at"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2024_01_01_000000_create_schools_table',1);
INSERT INTO migrations VALUES(5,'2024_01_20_000000_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(6,'2024_01_20_100000_create_students_table',1);
INSERT INTO migrations VALUES(7,'2024_01_20_100001_create_teachers_table',1);
INSERT INTO migrations VALUES(8,'2024_01_20_100002_create_classes_table',1);
INSERT INTO migrations VALUES(9,'2024_01_20_100003_create_payments_table',1);
INSERT INTO migrations VALUES(10,'2024_01_20_100004_create_invoices_table',1);
INSERT INTO migrations VALUES(11,'2024_01_20_100005_create_grades_table',1);
INSERT INTO migrations VALUES(12,'2024_01_20_100006_create_attendance_table',1);
INSERT INTO migrations VALUES(13,'2024_01_20_100007_create_timetable_table',1);
INSERT INTO migrations VALUES(14,'2024_01_20_100008_create_fee_structures_table',1);
INSERT INTO migrations VALUES(15,'2024_01_20_100009_create_transactions_table',1);
INSERT INTO migrations VALUES(16,'2024_01_23_100000_create_enrollment_applications_table',1);
INSERT INTO migrations VALUES(17,'2024_01_23_100001_create_announcements_table',1);
INSERT INTO migrations VALUES(18,'2024_01_23_100002_create_assignments_table',1);
INSERT INTO migrations VALUES(19,'2024_01_23_100003_create_parent_student_table',1);
INSERT INTO migrations VALUES(20,'2026_01_22_123134_create_settings_table',1);
INSERT INTO migrations VALUES(21,'2026_01_23_114727_create_enrollments_table',1);
INSERT INTO migrations VALUES(22,'2026_01_23_195455_create_departments_table',1);
INSERT INTO migrations VALUES(23,'2026_01_23_195542_create_fee_categories_table',1);
INSERT INTO migrations VALUES(24,'2026_01_24_000001_create_schools_table',1);
INSERT INTO migrations VALUES(25,'2026_01_24_000002_create_roles_table',1);
INSERT INTO migrations VALUES(26,'2026_01_24_000003_create_subjects_table',1);
INSERT INTO migrations VALUES(27,'2026_01_24_000004_create_payroll_table',1);
INSERT INTO migrations VALUES(28,'2026_01_24_000007_add_foreign_keys_to_users',1);
INSERT INTO migrations VALUES(29,'2026_01_25_000001_create_report_templates_table',1);
INSERT INTO migrations VALUES(30,'2026_01_27_000002_create_grade_levels_table',1);
INSERT INTO migrations VALUES(31,'2026_01_27_000003_create_rooms_table',1);
INSERT INTO migrations VALUES(32,'2026_01_27_000004_create_grading_scales_table',1);
INSERT INTO migrations VALUES(33,'2026_01_27_000005_create_teacher_assignments_table',1);
INSERT INTO migrations VALUES(34,'2026_01_27_000006_create_guardians_table',1);
INSERT INTO migrations VALUES(35,'2026_01_27_000007_create_guardian_student_table',1);
INSERT INTO migrations VALUES(36,'2026_01_27_000013_create_notification_queue_table',1);
INSERT INTO migrations VALUES(37,'2026_01_27_000014_create_terms_table',1);
INSERT INTO migrations VALUES(38,'2026_01_27_000015_create_exams_table',1);
INSERT INTO migrations VALUES(39,'2026_01_27_000016_create_exam_results_table',1);
INSERT INTO migrations VALUES(40,'2026_01_27_000017_create_tests_table',1);
INSERT INTO migrations VALUES(41,'2026_01_27_000018_create_test_results_table',1);
INSERT INTO migrations VALUES(42,'2026_01_27_000019_add_missing_foreign_key_constraints',1);
INSERT INTO migrations VALUES(43,'2026_01_28_000001_add_permission_ids_to_roles_table',1);
INSERT INTO migrations VALUES(44,'2026_01_28_000002_add_status_to_users_table',1);
INSERT INTO migrations VALUES(45,'2026_01_28_000003_add_assessment_links_to_grades_table',1);
INSERT INTO migrations VALUES(46,'2026_01_28_000004_add_amount_paid_to_payroll_table',1);
INSERT INTO migrations VALUES(47,'2026_06_20_000001_create_school_settings_table',1);
INSERT INTO migrations VALUES(48,'2026_06_20_000001_fix_attendance_unique_index',1);
INSERT INTO migrations VALUES(49,'2026_06_20_000002_create_custom_fields_table',1);
INSERT INTO migrations VALUES(50,'2026_06_20_000003_create_custom_field_values_table',1);
INSERT INTO migrations VALUES(51,'2026_06_20_000004_create_terminology_mappings_table',1);
INSERT INTO migrations VALUES(52,'2026_06_20_202240_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(53,'2026_06_20_202241_create_passkeys_table',1);
INSERT INTO migrations VALUES(54,'2026_06_20_203743_create_agent_conversations_table',1);
INSERT INTO migrations VALUES(55,'2026_06_21_000001_add_deferred_foreign_key_constraints',1);
INSERT INTO migrations VALUES(56,'2026_06_21_000002_create_leave_requests_table',1);
INSERT INTO migrations VALUES(57,'2026_06_21_000003_create_student_documents_table',1);
INSERT INTO migrations VALUES(58,'2026_06_22_000001_add_student_lifecycle_columns',1);
INSERT INTO migrations VALUES(59,'2026_06_22_000002_create_inventory_tables',1);
INSERT INTO migrations VALUES(60,'2026_06_22_000003_create_holiday_program_tables',1);
INSERT INTO migrations VALUES(61,'2026_06_22_000004_create_parent_portal_tables',1);
INSERT INTO migrations VALUES(62,'2026_06_22_000005_create_audit_tables',1);
INSERT INTO migrations VALUES(63,'2026_06_22_000006_create_school_erp_modules',1);
INSERT INTO migrations VALUES(64,'2026_06_22_000007_create_advanced_platform_features',1);
INSERT INTO migrations VALUES(65,'2026_06_22_000008_create_enterprise_erp_modules',1);
INSERT INTO migrations VALUES(66,'2026_06_22_000009_create_school_license_keys',1);
INSERT INTO migrations VALUES(67,'2026_06_22_120000_add_finance_performance_indexes',1);
INSERT INTO migrations VALUES(68,'2026_06_23_000001_add_avatar_url_to_users_table',1);
INSERT INTO migrations VALUES(69,'2026_07_19_000001_add_disbursement_fields_to_purchase_requisitions',1);
INSERT INTO migrations VALUES(70,'2026_07_19_000001_add_platform_terms_acceptance_to_users_table',1);
INSERT INTO migrations VALUES(71,'2026_07_19_000002_create_school_trip_tables',1);
INSERT INTO migrations VALUES(72,'2026_07_19_014200_add_student_placement_lifecycle_tables',1);
INSERT INTO migrations VALUES(73,'2026_07_19_114900_add_fee_category_id_to_fee_structures_table',1);
INSERT INTO migrations VALUES(74,'2026_07_19_120000_add_permission_ids_to_users_table',1);
INSERT INTO migrations VALUES(75,'2026_07_20_112600_add_profile_fields_to_schools_table',1);
INSERT INTO migrations VALUES(76,'2026_07_23_000001_create_teacher_portal_tables',1);
INSERT INTO migrations VALUES(77,'2026_07_26_200000_add_edudash_parity_modules',1);
INSERT INTO migrations VALUES(78,'2026_07_26_203000_add_discount_fields_to_invoices_table',1);
INSERT INTO migrations VALUES(79,'2026_07_26_204000_create_school_languages_table',1);
INSERT INTO migrations VALUES(80,'2026_07_26_205000_add_accounting_heads_to_transactions_table',1);
INSERT INTO migrations VALUES(81,'2026_07_26_210000_add_library_member_id_to_library_loans_table',1);
INSERT INTO migrations VALUES(82,'2026_07_28_120000_create_school_domains_table',1);
INSERT INTO migrations VALUES(83,'2026_07_28_130000_add_verification_token_to_school_domains',1);
INSERT INTO migrations VALUES(84,'2026_07_28_130100_create_school_backups_table',1);
INSERT INTO migrations VALUES(85,'2026_07_28_140000_create_help_desk_tickets_table',1);
INSERT INTO migrations VALUES(86,'2026_07_28_140100_add_photo_url_to_students_table',1);
INSERT INTO migrations VALUES(87,'2026_07_28_150000_add_revenue_fields_to_license_keys',1);
INSERT INTO migrations VALUES(88,'2026_07_28_150000_create_recruitment_tables',1);
INSERT INTO migrations VALUES(89,'2026_07_28_160000_add_workflow_fields_to_budgets',1);
INSERT INTO migrations VALUES(90,'2026_07_28_160000_create_school_user_memberships_table',1);
INSERT INTO migrations VALUES(91,'2026_07_28_170000_create_webhook_deliveries_and_secrets',1);
INSERT INTO migrations VALUES(92,'2026_10_06_095715_add_preskool_ui_parity_fields_to_tables',2);
```

## 3. Registered API Routes
```

  GET|HEAD  api/v1/admin/licenses .................................................... Api\V1\AdminLicenseController@index
  POST      api/v1/admin/licenses .................................................... Api\V1\AdminLicenseController@store
  POST      api/v1/admin/licenses/{id}/revoke ....................................... Api\V1\AdminLicenseController@revoke
  GET|HEAD  api/v1/admin/schools ...................................................... Api\V1\AdminSchoolController@index
  POST      api/v1/admin/schools ...................................................... Api\V1\AdminSchoolController@store
  GET|HEAD  api/v1/admin/schools/{school} .............................................. Api\V1\AdminSchoolController@show
  DELETE    api/v1/admin/schools/{school} ........................................... Api\V1\AdminSchoolController@destroy
  GET|HEAD  api/v1/admin/schools/{school}/backups ............................... Api\V1\AdminSchoolController@listBackups
  POST      api/v1/admin/schools/{school}/backups .............................. Api\V1\AdminSchoolController@createBackup
  POST      api/v1/admin/schools/{school}/backups/{backup}/restore ............ Api\V1\AdminSchoolController@restoreBackup
  GET|HEAD  api/v1/admin/schools/{school}/domains ................................... Api\V1\AdminSchoolController@domains
  POST      api/v1/admin/schools/{school}/domains ............................... Api\V1\AdminSchoolController@storeDomain
  PATCH     api/v1/admin/schools/{school}/domains/{domain} ..................... Api\V1\AdminSchoolController@updateDomain
  DELETE    api/v1/admin/schools/{school}/domains/{domain} .................... Api\V1\AdminSchoolController@destroyDomain
  GET|HEAD  api/v1/admin/schools/{school}/domains/{domain}/verification Api\V1\AdminSchoolController@checkDomainVerificat…
  POST      api/v1/admin/schools/{school}/domains/{domain}/verify .............. Api\V1\AdminSchoolController@verifyDomain
  PATCH     api/v1/admin/schools/{school}/status ............................... Api\V1\AdminSchoolController@updateStatus
  GET|HEAD  api/v1/admin/schools/{school}/usage ....................................... Api\V1\AdminSchoolController@usage
  GET|HEAD  api/v1/analytics/insights .......................................................... AnalyticsController@index
  GET|HEAD  api/v1/announcements ............................................................ AnnouncementController@index
  POST      api/v1/announcements ............................................................ AnnouncementController@store
  GET|HEAD  api/v1/announcements/{id} ........................................................ AnnouncementController@show
  PUT       api/v1/announcements/{id} ...................................................... AnnouncementController@update
  DELETE    api/v1/announcements/{id} ..................................................... AnnouncementController@destroy
  GET|HEAD  api/v1/assets .......................................................................... AssetController@index
  POST      api/v1/assets .......................................................................... AssetController@store
  PUT       api/v1/assets/{id} .................................................................... AssetController@update
  POST      api/v1/assets/{id}/dispose ........................................................... AssetController@dispose
  POST      api/v1/assets/{id}/maintenance ................................................ AssetController@logMaintenance
  GET|HEAD  api/v1/assignments ................................................................ AssignmentController@index
  POST      api/v1/assignments ................................................................ AssignmentController@store
  GET|HEAD  api/v1/assignments/{id} ............................................................ AssignmentController@show
  PUT       api/v1/assignments/{id} .......................................................... AssignmentController@update
  DELETE    api/v1/assignments/{id} ......................................................... AssignmentController@destroy
  POST      api/v1/assistant/chat ........................................................ Api\V1\AssistantController@chat
  GET|HEAD  api/v1/assistant/conversations ...................................... Api\V1\AssistantController@conversations
  GET|HEAD  api/v1/assistant/conversations/{conversationId} .................. Api\V1\AssistantController@showConversation
  GET|HEAD  api/v1/assistant/status .................................................... Api\V1\AssistantController@status
  GET|HEAD  api/v1/attendance ................................................................. AttendanceController@index
  POST      api/v1/attendance ................................................................. AttendanceController@store
  GET|HEAD  api/v1/attendance/class/{id}/report ......................................... AttendanceController@classReport
  GET|HEAD  api/v1/attendance/student/{id}/summary ................................... AttendanceController@studentSummary
  GET|HEAD  api/v1/attendance/today/summary ............................................ AttendanceController@todaySummary
  GET|HEAD  api/v1/audit-logs ................................................................... AuditLogController@index
  GET|HEAD  api/v1/audit-logs/login-history .............................................. AuditLogController@loginHistory
  GET|HEAD  api/v1/audit-logs/{id} ............................................................... AuditLogController@show
  POST      api/v1/auth/accept-platform-terms .................................. Api\V1\AuthController@acceptPlatformTerms
  POST      api/v1/auth/forgot-password ............................................. Api\V1\AuthController@forgotPassword
  POST      api/v1/auth/login ................................................................ Api\V1\AuthController@login
  POST      api/v1/auth/logout .............................................................. Api\V1\AuthController@logout
  GET|HEAD  api/v1/auth/me ...................................................................... Api\V1\AuthController@me
  GET|HEAD  api/v1/auth/platform-terms ............................................... Api\V1\AuthController@platformTerms
  GET|HEAD  api/v1/auth/privacy-policy ............................................... Api\V1\AuthController@privacyPolicy
  POST      api/v1/auth/reset-password ............................................... Api\V1\AuthController@resetPassword
  GET|HEAD  api/v1/auth/schools ............................................................ Api\V1\AuthController@schools
  POST      api/v1/auth/switch-school ................................................. Api\V1\AuthController@switchSchool
  GET|HEAD  api/v1/auth/two-factor ................................................. Api\V1\TwoFactorAuthController@status
  POST      api/v1/auth/two-factor ................................................. Api\V1\TwoFactorAuthController@enable
  DELETE    api/v1/auth/two-factor ................................................ Api\V1\TwoFactorAuthController@disable
  POST      api/v1/auth/two-factor/challenge .................................... Api\V1\TwoFactorAuthController@challenge
  POST      api/v1/auth/two-factor/confirm ........................................ Api\V1\TwoFactorAuthController@confirm
  POST      api/v1/auth/two-factor/recovery-codes ........................... Api\V1\TwoFactorAuthController@recoveryCodes
  GET|HEAD  api/v1/budgets ........................................................................ BudgetController@index
  POST      api/v1/budgets ........................................................................ BudgetController@store
  GET|HEAD  api/v1/budgets/{id} .................................................................... BudgetController@show
  PUT       api/v1/budgets/{id} .................................................................. BudgetController@update
  DELETE    api/v1/budgets/{id} ................................................................. BudgetController@destroy
  POST      api/v1/budgets/{id}/approve ......................................................... BudgetController@approve
  POST      api/v1/budgets/{id}/spend ....................................................... BudgetController@recordSpend
  POST      api/v1/budgets/{id}/submit ........................................................... BudgetController@submit
  GET|HEAD  api/v1/certificate-templates ............................................. CertificateTemplateController@index
  POST      api/v1/certificate-templates ............................................. CertificateTemplateController@store
  GET|HEAD  api/v1/certificate-templates/{id} ......................................... CertificateTemplateController@show
  PUT       api/v1/certificate-templates/{id} ....................................... CertificateTemplateController@update
  DELETE    api/v1/certificate-templates/{id} ...................................... CertificateTemplateController@destroy
  POST      api/v1/certificate-templates/{id}/issue .................................. CertificateTemplateController@issue
  GET|HEAD  api/v1/certificates/verify/{code} ......................................... SchoolCertificateController@verify
  GET|HEAD  api/v1/classes ......................................................................... ClassController@index
  POST      api/v1/classes ......................................................................... ClassController@store
  GET|HEAD  api/v1/classes/{class} .................................................................. ClassController@show
  PUT       api/v1/classes/{class} ................................................................ ClassController@update
  DELETE    api/v1/classes/{class} ............................................................... ClassController@destroy
  GET|HEAD  api/v1/communications/parents ................................................ CommunicationController@parents
  GET|HEAD  api/v1/communications/parents/{parentUserId}/students ................. CommunicationController@parentStudents
  GET|HEAD  api/v1/communications/staff .................................................... CommunicationController@staff
  GET|HEAD  api/v1/communications/threads .................................................. CommunicationController@index
  POST      api/v1/communications/threads .................................................. CommunicationController@store
  GET|HEAD  api/v1/communications/threads/unread-count ............................... CommunicationController@unreadCount
  GET|HEAD  api/v1/communications/threads/{id} .............................................. CommunicationController@show
  PATCH     api/v1/communications/threads/{id} ............................................ CommunicationController@update
  POST      api/v1/communications/threads/{id}/messages .................................... CommunicationController@reply
  GET|HEAD  api/v1/compliance/incidents ................................................... ComplianceController@incidents
  POST      api/v1/compliance/incidents ............................................... ComplianceController@storeIncident
  PUT       api/v1/compliance/incidents/{id} ......................................... ComplianceController@updateIncident
  GET|HEAD  api/v1/compliance/policies ..................................................... ComplianceController@policies
  POST      api/v1/compliance/policies .................................................. ComplianceController@storePolicy
  PUT       api/v1/compliance/policies/{id} ............................................ ComplianceController@updatePolicy
  GET|HEAD  api/v1/consent-forms ............................................................. ConsentFormController@index
  POST      api/v1/consent-forms ............................................................. ConsentFormController@store
  PUT       api/v1/consent-forms/{id} ....................................................... ConsentFormController@update
  GET|HEAD  api/v1/dashboard/activity ....................................................... DashboardController@activity
  GET|HEAD  api/v1/dashboard/kpis ............................................................... DashboardController@kpis
  GET|HEAD  api/v1/dashboard/lms-widgets .................................................. DashboardController@lmsWidgets
  GET|HEAD  api/v1/dashboard/monthly-stats .............................................. DashboardController@monthlyStats
  GET|HEAD  api/v1/dashboard/recent-activity .......................................... DashboardController@recentActivity
  GET|HEAD  api/v1/dashboard/role-preview/{role} ......................................... DashboardController@rolePreview
  GET|HEAD  api/v1/dashboard/school-widgets ............................................ DashboardController@schoolWidgets
  GET|HEAD  api/v1/departments ................................................................ DepartmentController@index
  POST      api/v1/departments ................................................................ DepartmentController@store
  GET|HEAD  api/v1/departments/{department} .................................................... DepartmentController@show
  PUT       api/v1/departments/{department} .................................................. DepartmentController@update
  DELETE    api/v1/departments/{department} ................................................. DepartmentController@destroy
  GET|HEAD  api/v1/designations .............................................................. DesignationController@index
  POST      api/v1/designations .............................................................. DesignationController@store
  GET|HEAD  api/v1/designations/{id} .......................................................... DesignationController@show
  PUT       api/v1/designations/{id} ........................................................ DesignationController@update
  DELETE    api/v1/designations/{id} ....................................................... DesignationController@destroy
  GET|HEAD  api/v1/disciplinary-records ............................................... DisciplinaryRecordController@index
  POST      api/v1/disciplinary-records ............................................... DisciplinaryRecordController@store
  GET|HEAD  api/v1/disciplinary-records/{id} ........................................... DisciplinaryRecordController@show
  PUT       api/v1/disciplinary-records/{id} ......................................... DisciplinaryRecordController@update
  GET|HEAD  api/v1/employees .................................................................... EmployeeController@index
  POST      api/v1/employees .................................................................... EmployeeController@store
  GET|HEAD  api/v1/employees/{id} ................................................................ EmployeeController@show
  PUT       api/v1/employees/{id} .............................................................. EmployeeController@update
  DELETE    api/v1/employees/{id} ............................................................. EmployeeController@destroy
  GET|HEAD  api/v1/enrollment-applications .................................................... EnrollmentController@index
  POST      api/v1/enrollment-applications .................................................... EnrollmentController@store
  GET|HEAD  api/v1/enrollment-applications/{id} ................................................ EnrollmentController@show
  PUT       api/v1/enrollment-applications/{id}/approve ..................................... EnrollmentController@approve
  PUT       api/v1/enrollment-applications/{id}/reject ....................................... EnrollmentController@reject
  GET|HEAD  api/v1/enterprise/academic/assessment-categories Enterprise\EnterpriseAcademicController@assessmentCategories
  POST      api/v1/enterprise/academic/assessment-categories Enterprise\EnterpriseAcademicController@storeAssessmentCateg…
  GET|HEAD  api/v1/enterprise/academic/calendar ......................... Enterprise\EnterpriseAcademicController@calendar
  POST      api/v1/enterprise/academic/calendar ............... Enterprise\EnterpriseAcademicController@storeCalendarEntry
  PUT       api/v1/enterprise/academic/calendar/{id} ......... Enterprise\EnterpriseAcademicController@updateCalendarEntry
  DELETE    api/v1/enterprise/academic/calendar/{id} ........ Enterprise\EnterpriseAcademicController@destroyCalendarEntry
  POST      api/v1/enterprise/academic/continuous-assessments ... Enterprise\EnterpriseAcademicController@recordAssessment
  GET|HEAD  api/v1/enterprise/academic/curriculum ..................... Enterprise\EnterpriseAcademicController@curriculum
  POST      api/v1/enterprise/academic/curriculum .............. Enterprise\EnterpriseAcademicController@publishCurriculum
  GET|HEAD  api/v1/enterprise/academic/gpa-ranking .................... Enterprise\EnterpriseAcademicController@gpaRanking
  POST      api/v1/enterprise/academic/gradebook-rules ........ Enterprise\EnterpriseAcademicController@storeGradebookRule
  POST      api/v1/enterprise/academic/learning-outcomes ........... Enterprise\EnterpriseAcademicController@recordOutcome
  GET|HEAD  api/v1/enterprise/academic/promotion-rules ............ Enterprise\EnterpriseAcademicController@promotionRules
  POST      api/v1/enterprise/academic/promotion-rules ........ Enterprise\EnterpriseAcademicController@storePromotionRule
  GET|HEAD  api/v1/enterprise/academic/students/{studentId}/promotion-evaluation Enterprise\EnterpriseAcademicController@…
  GET|HEAD  api/v1/enterprise/academic/students/{studentId}/subjects/{subjectId}/prerequisites Enterprise\EnterpriseAcade…
  GET|HEAD  api/v1/enterprise/academic/students/{studentId}/subjects/{subjectId}/weighted-grade Enterprise\EnterpriseAcad…
  POST      api/v1/enterprise/admissions/score ................ Enterprise\EnterpriseIntelligenceController@admissionScore
  GET|HEAD  api/v1/enterprise/alumni .................................. Enterprise\EnterpriseIntelligenceController@alumni
  POST      api/v1/enterprise/alumni ............................. Enterprise\EnterpriseIntelligenceController@storeAlumni
  POST      api/v1/enterprise/alumni/students/{studentId} ..... Enterprise\EnterpriseIntelligenceController@registerAlumni
  PUT       api/v1/enterprise/alumni/{id} ....................... Enterprise\EnterpriseIntelligenceController@updateAlumni
  DELETE    api/v1/enterprise/alumni/{id} ...................... Enterprise\EnterpriseIntelligenceController@destroyAlumni
  POST      api/v1/enterprise/alumni/{id}/engagements . Enterprise\EnterpriseIntelligenceController@recordAlumniEngagement
  GET|HEAD  api/v1/enterprise/campaigns ............................ Enterprise\EnterpriseIntelligenceController@campaigns
  POST      api/v1/enterprise/campaigns ........................ Enterprise\EnterpriseIntelligenceController@storeCampaign
  POST      api/v1/enterprise/campaigns/{id}/send ............... Enterprise\EnterpriseIntelligenceController@sendCampaign
  GET|HEAD  api/v1/enterprise/command-center ................... Enterprise\EnterpriseIntelligenceController@commandCenter
  GET|HEAD  api/v1/enterprise/compliance/requirements ... Enterprise\EnterpriseGovernanceController@complianceRequirements
  POST      api/v1/enterprise/compliance/requirements Enterprise\EnterpriseGovernanceController@storeComplianceRequirement
  GET|HEAD  api/v1/enterprise/early-warnings ................... Enterprise\EnterpriseIntelligenceController@earlyWarnings
  POST      api/v1/enterprise/exams/cbt/start ............................... Enterprise\EnterpriseExamController@startCbt
  POST      api/v1/enterprise/exams/cbt/{id}/submit ........................ Enterprise\EnterpriseExamController@submitCbt
  POST      api/v1/enterprise/exams/cbt/{sessionId}/anti-cheat .......... Enterprise\EnterpriseExamController@antiCheatLog
  POST      api/v1/enterprise/exams/generate-paper ..................... Enterprise\EnterpriseExamController@generatePaper
  GET|HEAD  api/v1/enterprise/exams/question-bank .......................... Enterprise\EnterpriseExamController@questions
  POST      api/v1/enterprise/exams/question-bank ...................... Enterprise\EnterpriseExamController@storeQuestion
  GET|HEAD  api/v1/enterprise/exams/remark-requests ................... Enterprise\EnterpriseExamController@remarkRequests
  POST      api/v1/enterprise/exams/remark-requests .................... Enterprise\EnterpriseExamController@requestRemark
  GET|HEAD  api/v1/enterprise/finance/accounts ........................... Enterprise\EnterpriseFinanceController@accounts
  POST      api/v1/enterprise/finance/accounts/seed .................. Enterprise\EnterpriseFinanceController@seedAccounts
  GET|HEAD  api/v1/enterprise/finance/bank-statements .............. Enterprise\EnterpriseFinanceController@bankStatements
  POST      api/v1/enterprise/finance/bank-statements .............. Enterprise\EnterpriseFinanceController@importBankLine
  POST      api/v1/enterprise/finance/bank-statements/{line}/reconcile .. Enterprise\EnterpriseFinanceController@reconcile
  GET|HEAD  api/v1/enterprise/finance/cashflow-forecast .......... Enterprise\EnterpriseFinanceController@cashflowForecast
  GET|HEAD  api/v1/enterprise/finance/exchange-rates ................ Enterprise\EnterpriseFinanceController@exchangeRates
  POST      api/v1/enterprise/finance/exchange-rates ............ Enterprise\EnterpriseFinanceController@storeExchangeRate
  GET|HEAD  api/v1/enterprise/finance/instalment-plans ............ Enterprise\EnterpriseFinanceController@instalmentPlans
  POST      api/v1/enterprise/finance/instalment-plans ....... Enterprise\EnterpriseFinanceController@createInstalmentPlan
  POST      api/v1/enterprise/finance/journals ........................ Enterprise\EnterpriseFinanceController@postJournal
  GET|HEAD  api/v1/enterprise/finance/reports/balance-sheet .......... Enterprise\EnterpriseFinanceController@balanceSheet
  GET|HEAD  api/v1/enterprise/finance/reports/profit-loss ........... Enterprise\EnterpriseFinanceController@profitAndLoss
  GET|HEAD  api/v1/enterprise/finance/revenue-rules .................. Enterprise\EnterpriseFinanceController@revenueRules
  POST      api/v1/enterprise/finance/revenue-rules .............. Enterprise\EnterpriseFinanceController@storeRevenueRule
  GET|HEAD  api/v1/enterprise/group/policies ..................... Enterprise\EnterpriseGovernanceController@groupPolicies
  POST      api/v1/enterprise/group/policies .................. Enterprise\EnterpriseGovernanceController@storeGroupPolicy
  GET|HEAD  api/v1/enterprise/group/transfers ............. Enterprise\EnterpriseGovernanceController@crossSchoolTransfers
  POST      api/v1/enterprise/group/transfers .................. Enterprise\EnterpriseGovernanceController@requestTransfer
  GET|HEAD  api/v1/enterprise/hr/certifications ................. Enterprise\EnterpriseGovernanceController@certifications
  POST      api/v1/enterprise/hr/certifications ............. Enterprise\EnterpriseGovernanceController@storeCertification
  GET|HEAD  api/v1/enterprise/hr/contracts ........................... Enterprise\EnterpriseGovernanceController@contracts
  POST      api/v1/enterprise/hr/contracts ....................... Enterprise\EnterpriseGovernanceController@storeContract
  GET|HEAD  api/v1/enterprise/hr/performance-reviews ........ Enterprise\EnterpriseGovernanceController@performanceReviews
  POST      api/v1/enterprise/hr/performance-reviews .... Enterprise\EnterpriseGovernanceController@storePerformanceReview
  GET|HEAD  api/v1/enterprise/integrations/connectors ............... Enterprise\EnterpriseGovernanceController@connectors
  POST      api/v1/enterprise/integrations/connectors ........... Enterprise\EnterpriseGovernanceController@storeConnector
  GET|HEAD  api/v1/enterprise/integrations/webhooks ................... Enterprise\EnterpriseGovernanceController@webhooks
  POST      api/v1/enterprise/integrations/webhooks ............... Enterprise\EnterpriseGovernanceController@storeWebhook
  GET|HEAD  api/v1/enterprise/integrations/webhooks/deliveries Enterprise\EnterpriseGovernanceController@webhookDeliveries
  POST      api/v1/enterprise/integrations/webhooks/deliveries/{id}/retry Enterprise\EnterpriseGovernanceController@retry…
  PUT       api/v1/enterprise/integrations/webhooks/{id} ......... Enterprise\EnterpriseGovernanceController@updateWebhook
  DELETE    api/v1/enterprise/integrations/webhooks/{id} ........ Enterprise\EnterpriseGovernanceController@destroyWebhook
  GET|HEAD  api/v1/enterprise/security/abac-policies .............. Enterprise\EnterpriseGovernanceController@abacPolicies
  POST      api/v1/enterprise/security/abac-policies ........... Enterprise\EnterpriseGovernanceController@storeAbacPolicy
  GET|HEAD  api/v1/enterprise/students/{id}/profile ........... Enterprise\EnterpriseIntelligenceController@studentProfile
  GET|HEAD  api/v1/enterprise/students/{id}/timeline ......... Enterprise\EnterpriseIntelligenceController@studentTimeline
  POST      api/v1/enterprise/students/{id}/timeline ..... Enterprise\EnterpriseIntelligenceController@recordTimelineEvent
  GET|HEAD  api/v1/enterprise/workflows/delegations ...................... Enterprise\EnterpriseExamController@delegations
  POST      api/v1/enterprise/workflows/delegations .................. Enterprise\EnterpriseExamController@storeDelegation
  GET|HEAD  api/v1/events .................................................................... SchoolEventController@index
  POST      api/v1/events .................................................................... SchoolEventController@store
  PUT       api/v1/events/{id} .............................................................. SchoolEventController@update
  DELETE    api/v1/events/{id} ............................................................. SchoolEventController@destroy
  GET|HEAD  api/v1/exam-schedules ........................................................... ExamScheduleController@index
  POST      api/v1/exam-schedules ........................................................... ExamScheduleController@store
  GET|HEAD  api/v1/exam-schedules/{id} ....................................................... ExamScheduleController@show
  PUT       api/v1/exam-schedules/{id} ..................................................... ExamScheduleController@update
  DELETE    api/v1/exam-schedules/{id} .................................................... ExamScheduleController@destroy
  GET|HEAD  api/v1/exams ............................................................................ ExamController@index
  POST      api/v1/exams ............................................................................ ExamController@store
  GET|HEAD  api/v1/exams/analytics/performance .................................................. ExamController@analytics
  GET|HEAD  api/v1/exams/{id} ........................................................................ ExamController@show
  PUT       api/v1/exams/{id} ...................................................................... ExamController@update
  DELETE    api/v1/exams/{id} ..................................................................... ExamController@destroy
  POST      api/v1/exams/{id}/publish ............................................................. ExamController@publish
  POST      api/v1/exams/{id}/results ....................................................... ExamController@recordResults
  POST      api/v1/exams/{id}/results/approve .............................................. ExamController@approveResults
  GET|HEAD  api/v1/expense-heads ............................................................. ExpenseHeadController@index
  POST      api/v1/expense-heads ............................................................. ExpenseHeadController@store
  GET|HEAD  api/v1/expense-heads/{id} ......................................................... ExpenseHeadController@show
  PUT       api/v1/expense-heads/{id} ....................................................... ExpenseHeadController@update
  DELETE    api/v1/expense-heads/{id} ...................................................... ExpenseHeadController@destroy
  GET|HEAD  api/v1/fee-categories ............................................................ FeeCategoryController@index
  POST      api/v1/fee-categories ............................................................ FeeCategoryController@store
  GET|HEAD  api/v1/fee-categories/{id} ........................................................ FeeCategoryController@show
  PUT       api/v1/fee-categories/{id} ...................................................... FeeCategoryController@update
  DELETE    api/v1/fee-categories/{id} ..................................................... FeeCategoryController@destroy
  GET|HEAD  api/v1/fee-discounts ............................................................. FeeDiscountController@index
  POST      api/v1/fee-discounts ............................................................. FeeDiscountController@store
  GET|HEAD  api/v1/fee-discounts/{id} ......................................................... FeeDiscountController@show
  PUT       api/v1/fee-discounts/{id} ....................................................... FeeDiscountController@update
  DELETE    api/v1/fee-discounts/{id} ...................................................... FeeDiscountController@destroy
  GET|HEAD  api/v1/fee-groups ................................................................... FeeGroupController@index
  POST      api/v1/fee-groups ................................................................... FeeGroupController@store
  GET|HEAD  api/v1/fee-groups/{id} ............................................................... FeeGroupController@show
  PUT       api/v1/fee-groups/{id} ............................................................. FeeGroupController@update
  DELETE    api/v1/fee-groups/{id} ............................................................ FeeGroupController@destroy
  GET|HEAD  api/v1/fee-structures ........................................................... FeeStructureController@index
  POST      api/v1/fee-structures ........................................................... FeeStructureController@store
  PUT       api/v1/fee-structures/{id} ..................................................... FeeStructureController@update
  DELETE    api/v1/fee-structures/{id} .................................................... FeeStructureController@destroy
  GET|HEAD  api/v1/finance/aging ................................................................. FinanceController@aging
  GET|HEAD  api/v1/finance/cash-flow .......................................................... FinanceController@cashFlow
  GET|HEAD  api/v1/finance/outstanding-balances .................................... FinanceController@outstandingBalances
  GET|HEAD  api/v1/finance/reconciliation ............................................... FinanceController@reconciliation
  GET|HEAD  api/v1/finance/reports/{period} ............................................... FinanceController@periodReport
  GET|HEAD  api/v1/finance/summary ............................................................. FinanceController@summary
  GET|HEAD  api/v1/grade-levels ............................................................... GradeLevelController@index
  POST      api/v1/grade-levels ............................................................... GradeLevelController@store
  GET|HEAD  api/v1/grade-levels/{id} ........................................................... GradeLevelController@show
  PUT       api/v1/grade-levels/{id} ......................................................... GradeLevelController@update
  DELETE    api/v1/grade-levels/{id} ........................................................ GradeLevelController@destroy
  POST      api/v1/grades .......................................................................... GradeController@store
  POST      api/v1/grades/bulk ................................................................ GradeController@bulkUpload
  GET|HEAD  api/v1/grades/class/{classId} ..................................................... GradeController@getByClass
  GET|HEAD  api/v1/grades/class/{classId}/performance ........................................ GradeController@performance
  GET|HEAD  api/v1/grades/student/{studentId} ............................................... GradeController@getByStudent
  GET|HEAD  api/v1/grading-scales ........................................................... GradingScaleController@index
  POST      api/v1/grading-scales ........................................................... GradingScaleController@store
  POST      api/v1/grading-scales/get-grade ...................................... GradingScaleController@getGradeForScore
  PUT       api/v1/grading-scales/{id} ..................................................... GradingScaleController@update
  DELETE    api/v1/grading-scales/{id} .................................................... GradingScaleController@destroy
  GET|HEAD  api/v1/guardians .................................................................... GuardianController@index
  POST      api/v1/guardians .................................................................... GuardianController@store
  GET|HEAD  api/v1/guardians/{id} ................................................................ GuardianController@show
  PUT       api/v1/guardians/{id} .............................................................. GuardianController@update
  DELETE    api/v1/guardians/{id} ............................................................. GuardianController@destroy
  POST      api/v1/guardians/{id}/link-student .......................................... GuardianController@linkToStudent
  GET|HEAD  api/v1/guardians/{id}/students ................................................... GuardianController@students
  GET|HEAD  api/v1/health/clinic-visits .......................................................... HealthController@visits
  POST      api/v1/health/clinic-visits ..................................................... HealthController@recordVisit
  PUT       api/v1/health/clinic-visits/{id} ................................................ HealthController@updateVisit
  GET|HEAD  api/v1/health/students/{id}/profile ................................................. HealthController@profile
  PUT       api/v1/health/students/{id}/profile ........................................... HealthController@updateProfile
  GET|HEAD  api/v1/help-desk/tickets ............................................................ HelpDeskController@index
  POST      api/v1/help-desk/tickets ............................................................ HelpDeskController@store
  PUT       api/v1/help-desk/tickets/{id} ...................................................... HelpDeskController@update
  POST      api/v1/help-desk/tickets/{id}/close ................................................. HelpDeskController@close
  POST      api/v1/help-desk/tickets/{id}/resolve ............................................. HelpDeskController@resolve
  GET|HEAD  api/v1/holiday-programs ....................................................... HolidayProgramController@index
  POST      api/v1/holiday-programs ....................................................... HolidayProgramController@store
  PUT       api/v1/holiday-programs/{id} ................................................. HolidayProgramController@update
  GET|HEAD  api/v1/holiday-programs/{id}/attendance .................................. HolidayProgramController@attendance
  POST      api/v1/holiday-programs/{id}/attendance ............................ HolidayProgramController@recordAttendance
  POST      api/v1/holiday-programs/{id}/enroll .......................................... HolidayProgramController@enroll
  GET|HEAD  api/v1/holiday-programs/{id}/enrollments ................................ HolidayProgramController@enrollments
  GET|HEAD  api/v1/hostels ........................................................................ HostelController@index
  POST      api/v1/hostels ........................................................................ HostelController@store
  POST      api/v1/hostels/allocations ......................................................... HostelController@allocate
  PUT       api/v1/hostels/{id} .................................................................. HostelController@update
  POST      api/v1/hostels/{id}/rooms ......................................................... HostelController@storeRoom
  GET|HEAD  api/v1/houses ............................................................. AcademicStructureController@houses
  POST      api/v1/houses ......................................................... AcademicStructureController@storeHouse
  PUT       api/v1/houses/{id} ................................................... AcademicStructureController@updateHouse
  POST      api/v1/imports/{type} ................................................... Api\V1\PeopleImportController@import
  GET|HEAD  api/v1/imports/{type}/template ........................................ Api\V1\PeopleImportController@template
  GET|HEAD  api/v1/income-heads ............................................................... IncomeHeadController@index
  POST      api/v1/income-heads ............................................................... IncomeHeadController@store
  GET|HEAD  api/v1/income-heads/{id} ........................................................... IncomeHeadController@show
  PUT       api/v1/income-heads/{id} ......................................................... IncomeHeadController@update
  DELETE    api/v1/income-heads/{id} ........................................................ IncomeHeadController@destroy
  POST      api/v1/integrations/token ....................................... Platform\ExternalIntegrationController@token
  GET|HEAD  api/v1/inventory/items ............................................................. InventoryController@index
  POST      api/v1/inventory/items ............................................................. InventoryController@store
  PUT       api/v1/inventory/items/{id} ....................................................... InventoryController@update
  POST      api/v1/inventory/items/{id}/restock .............................................. InventoryController@restock
  GET|HEAD  api/v1/inventory/sales ............................................................. InventoryController@sales
  POST      api/v1/inventory/sales ........................................................ InventoryController@createSale
  GET|HEAD  api/v1/invoices ...................................................................... InvoiceController@index
  POST      api/v1/invoices ...................................................................... InvoiceController@store
  GET|HEAD  api/v1/invoices/{invoice} ............................................................. InvoiceController@show
  PUT       api/v1/invoices/{invoice} ........................................................... InvoiceController@update
  GET|HEAD  api/v1/invoices/{invoice}/print ...................................................... InvoiceController@print
  GET|HEAD  api/v1/leave-requests ........................................................... LeaveRequestController@index
  POST      api/v1/leave-requests ........................................................... LeaveRequestController@store
  POST      api/v1/leave-requests/{id}/approve ............................................ LeaveRequestController@approve
  POST      api/v1/leave-requests/{id}/reject .............................................. LeaveRequestController@reject
  GET|HEAD  api/v1/leave-types ................................................................. LeaveTypeController@index
  POST      api/v1/leave-types ................................................................. LeaveTypeController@store
  GET|HEAD  api/v1/leave-types/{id} ............................................................. LeaveTypeController@show
  PUT       api/v1/leave-types/{id} ........................................................... LeaveTypeController@update
  DELETE    api/v1/leave-types/{id} .......................................................... LeaveTypeController@destroy
  GET|HEAD  api/v1/library/books ................................................................. LibraryController@books
  POST      api/v1/library/books ............................................................. LibraryController@storeBook
  PUT       api/v1/library/books/{id} ....................................................... LibraryController@updateBook
  DELETE    api/v1/library/books/{id} ...................................................... LibraryController@destroyBook
  GET|HEAD  api/v1/library/loans ................................................................. LibraryController@loans
  POST      api/v1/library/loans ................................................................ LibraryController@borrow
  POST      api/v1/library/loans/{id}/return ................................................ LibraryController@returnBook
  GET|HEAD  api/v1/library/members ......................................................... LibraryMemberController@index
  POST      api/v1/library/members ......................................................... LibraryMemberController@store
  GET|HEAD  api/v1/library/members/{id} ..................................................... LibraryMemberController@show
  PUT       api/v1/library/members/{id} ................................................... LibraryMemberController@update
  DELETE    api/v1/library/members/{id} .................................................. LibraryMemberController@destroy
  POST      api/v1/license/activate .................................................... Api\V1\LicenseController@activate
  GET|HEAD  api/v1/license/status ........................................................ Api\V1\LicenseController@status
  GET|HEAD  api/v1/parent/portal/announcements ...................................... ParentPortalController@announcements
  GET|HEAD  api/v1/parent/portal/children ................................................ ParentPortalController@children
  GET|HEAD  api/v1/parent/portal/communications/threads ................................... ParentPortalController@threads
  POST      api/v1/parent/portal/communications/threads .............................. ParentPortalController@createThread
  PATCH     api/v1/parent/portal/communications/threads/{threadId} ................... ParentPortalController@updateThread
  GET|HEAD  api/v1/parent/portal/communications/threads/{threadId}/messages ........ ParentPortalController@threadMessages
  POST      api/v1/parent/portal/communications/threads/{threadId}/messages ........... ParentPortalController@sendMessage
  GET|HEAD  api/v1/parent/portal/consent-forms ......................................... ConsentFormController@parentForms
  POST      api/v1/parent/portal/consent-forms/{id}/respond ................................ ConsentFormController@respond
  GET|HEAD  api/v1/parent/portal/dashboard .............................................. ParentPortalController@dashboard
  GET|HEAD  api/v1/parent/portal/notifications ...................................... ParentPortalController@notifications
  POST      api/v1/parent/portal/notifications/read-all .................. ParentPortalController@markAllNotificationsRead
  POST      api/v1/parent/portal/notifications/{id}/read ..................... ParentPortalController@markNotificationRead
  POST      api/v1/parent/portal/store/buy .......................................... ParentPortalController@buyStoreItems
  GET|HEAD  api/v1/parent/portal/store/items ........................................... ParentPortalController@storeItems
  GET|HEAD  api/v1/parent/portal/students/{studentId}/attendance ....................... ParentPortalController@attendance
  GET|HEAD  api/v1/parent/portal/students/{studentId}/discipline ....................... ParentPortalController@discipline
  GET|HEAD  api/v1/parent/portal/students/{studentId}/fees ................................... ParentPortalController@fees
  GET|HEAD  api/v1/parent/portal/students/{studentId}/progress ........................... ParentPortalController@progress
  GET|HEAD  api/v1/parent/portal/students/{studentId}/results ............................. ParentPortalController@results
  GET|HEAD  api/v1/parent/portal/trips ...................................................... ParentPortalController@trips
  POST      api/v1/parent/portal/trips/{id}/enroll ..................................... ParentPortalController@enrollTrip
  GET|HEAD  api/v1/parents/{id}/children ....................................................... ParentController@children
  GET|HEAD  api/v1/payments ...................................................................... PaymentController@index
  POST      api/v1/payments ...................................................................... PaymentController@store
  DELETE    api/v1/payments/{payment} .......................................................... PaymentController@destroy
  GET|HEAD  api/v1/payments/{payment}/receipt .................................................. PaymentController@receipt
  POST      api/v1/payments/{payment}/reverse .................................................. PaymentController@reverse
  GET|HEAD  api/v1/payroll ....................................................................... PayrollController@index
  GET|HEAD  api/v1/payroll/department-summary ........................................ PayrollController@departmentSummary
  POST      api/v1/payroll/generate ........................................................... PayrollController@generate
  GET|HEAD  api/v1/payroll/summary ............................................................. PayrollController@summary
  GET|HEAD  api/v1/payroll/teachers ........................................................ PayrollController@getTeachers
  GET|HEAD  api/v1/payroll/teachers/{id}/history ........................................ PayrollController@teacherHistory
  GET|HEAD  api/v1/payroll/trends ............................................................... PayrollController@trends
  PUT       api/v1/payroll/{id} ................................................................. PayrollController@update
  GET|HEAD  api/v1/payroll/{id}/payslip ........................................................ PayrollController@payslip
  POST      api/v1/payroll/{id}/process ........................................................ PayrollController@process
  GET|HEAD  api/v1/permissions ................................................................ PermissionController@index
  GET|HEAD  api/v1/platform/analytics/predictive ............... Platform\PlatformOperationsController@predictiveAnalytics
  GET|HEAD  api/v1/platform/api-clients ................................. Platform\PlatformOperationsController@apiClients
  POST      api/v1/platform/api-clients ............................ Platform\PlatformOperationsController@createApiClient
  GET|HEAD  api/v1/platform/audit-integrity/verify ............ Platform\PlatformOperationsController@verifyAuditIntegrity
  GET|HEAD  api/v1/platform/behavior-points .............................. Platform\PlatformStaffController@behaviorPoints
  POST      api/v1/platform/behavior-points .......................... Platform\PlatformStaffController@storeBehaviorPoint
  GET|HEAD  api/v1/platform/behavior-points/students/{studentId}/summary Platform\PlatformStaffController@behaviorSummary
  POST      api/v1/platform/branches ........................................ Platform\PlatformCoreController@createBranch
  GET|HEAD  api/v1/platform/branches/tree ..................................... Platform\PlatformCoreController@branchTree
  GET|HEAD  api/v1/platform/certificates ................................ Platform\PlatformDocumentController@certificates
  POST      api/v1/platform/certificates/issue ...................... Platform\PlatformDocumentController@issueCertificate
  GET|HEAD  api/v1/platform/certificates/verify/{code} ............. Platform\PlatformDocumentController@verifyCertificate
  POST      api/v1/platform/communications/deliveries/{id}/read ........ Platform\PlatformCommunicationController@markRead
  POST      api/v1/platform/communications/send ............................ Platform\PlatformCommunicationController@send
  GET|HEAD  api/v1/platform/communications/tracking .................... Platform\PlatformCommunicationController@tracking
  GET|HEAD  api/v1/platform/documents ...................................... Platform\PlatformDocumentController@documents
  POST      api/v1/platform/documents ................................. Platform\PlatformDocumentController@createDocument
  POST      api/v1/platform/documents/{id}/sign ......................... Platform\PlatformDocumentController@signDocument
  GET|HEAD  api/v1/platform/fee-penalty-rules ............................ Platform\PlatformFinanceController@penaltyRules
  POST      api/v1/platform/fee-penalty-rules ........................ Platform\PlatformFinanceController@storePenaltyRule
  GET|HEAD  api/v1/platform/interventions ................................. Platform\PlatformStaffController@interventions
  POST      api/v1/platform/interventions ............................. Platform\PlatformStaffController@storeIntervention
  GET|HEAD  api/v1/platform/masking-rules ................................... Platform\PlatformCoreController@maskingRules
  POST      api/v1/platform/masking-rules ............................... Platform\PlatformCoreController@storeMaskingRule
  POST      api/v1/platform/operations/alerts/{id}/resolve ............ Platform\PlatformOperationsController@resolveAlert
  GET|HEAD  api/v1/platform/operations/live .......................... Platform\PlatformOperationsController@liveDashboard
  GET|HEAD  api/v1/platform/payment-gateways ........................... Platform\PlatformFinanceController@gatewayConfigs
  POST      api/v1/platform/payment-gateways ....................... Platform\PlatformFinanceController@storeGatewayConfig
  POST      api/v1/platform/payments/initiate ......................... Platform\PlatformFinanceController@initiatePayment
  GET|HEAD  api/v1/platform/payments/status/{reference} ........................... Api\V1\PaymentWebhookController@status
  GET|HEAD  api/v1/platform/policy-rules ..................................... Platform\PlatformCoreController@policyRules
  POST      api/v1/platform/policy-rules ................................. Platform\PlatformCoreController@storePolicyRule
  POST      api/v1/platform/policy-rules/evaluate ......................... Platform\PlatformCoreController@evaluatePolicy
  GET|HEAD  api/v1/platform/refunds ........................................... Platform\PlatformFinanceController@refunds
  POST      api/v1/platform/refunds ..................................... Platform\PlatformFinanceController@requestRefund
  POST      api/v1/platform/refunds/{refund}/approve .................... Platform\PlatformFinanceController@approveRefund
  GET|HEAD  api/v1/platform/retention-policies ......................... Platform\PlatformCoreController@retentionPolicies
  POST      api/v1/platform/retention-policies ...................... Platform\PlatformCoreController@storeRetentionPolicy
  POST      api/v1/platform/scholarship-applications/{id}/review Platform\PlatformFinanceController@reviewScholarshipAppl…
  GET|HEAD  api/v1/platform/scholarships ................................. Platform\PlatformFinanceController@scholarships
  POST      api/v1/platform/scholarships ............................. Platform\PlatformFinanceController@storeScholarship
  POST      api/v1/platform/scholarships/{id}/apply .................. Platform\PlatformFinanceController@applyScholarship
  GET|HEAD  api/v1/platform/staff-feed ............................................. Platform\PlatformStaffController@feed
  POST      api/v1/platform/staff-feed ......................................... Platform\PlatformStaffController@postFeed
  POST      api/v1/platform/staff-feed/{id}/comments ........................ Platform\PlatformStaffController@commentFeed
  GET|HEAD  api/v1/platform/staff-tasks ........................................... Platform\PlatformStaffController@tasks
  POST      api/v1/platform/staff-tasks ....................................... Platform\PlatformStaffController@storeTask
  PUT       api/v1/platform/staff-tasks/{id} ................................. Platform\PlatformStaffController@updateTask
  GET|HEAD  api/v1/platform/students/{id}/versions ....................... Platform\PlatformCoreController@studentVersions
  GET|HEAD  api/v1/platform/system/health ............................. Platform\PlatformOperationsController@systemHealth
  GET|HEAD  api/v1/platform/vault ......................................... Platform\PlatformDocumentController@vaultIndex
  POST      api/v1/platform/vault ......................................... Platform\PlatformDocumentController@vaultStore
  GET|HEAD  api/v1/platform/vault/{id} ................................. Platform\PlatformDocumentController@vaultRetrieve
  GET|HEAD  api/v1/platform/workflow-definitions ..................... Platform\PlatformCoreController@workflowDefinitions
  POST      api/v1/platform/workflow-definitions .................. Platform\PlatformCoreController@saveWorkflowDefinition
  POST      api/v1/procurement/goods-receipts ......................................... ProcurementController@receiveGoods
  GET|HEAD  api/v1/procurement/requisitions ........................................... ProcurementController@requisitions
  POST      api/v1/procurement/requisitions ....................................... ProcurementController@storeRequisition
  PUT       api/v1/procurement/requisitions/{id} ................................. ProcurementController@updateRequisition
  POST      api/v1/procurement/requisitions/{id}/disburse ................................. ProcurementController@disburse
  POST      api/v1/procurement/requisitions/{id}/submit ..................................... ProcurementController@submit
  GET|HEAD  api/v1/procurement/vendors ..................................................... ProcurementController@vendors
  POST      api/v1/procurement/vendors ................................................. ProcurementController@storeVendor
  PUT       api/v1/procurement/vendors/{id} ........................................... ProcurementController@updateVendor
  GET|HEAD  api/v1/recruitment/applications ........................................... RecruitmentController@applications
  POST      api/v1/recruitment/applications ....................................... RecruitmentController@storeApplication
  PUT       api/v1/recruitment/applications/{id} ................................. RecruitmentController@updateApplication
  POST      api/v1/recruitment/applications/{id}/hire .............................. RecruitmentController@hireApplication
  POST      api/v1/recruitment/applications/{id}/interview .................... RecruitmentController@interviewApplication
  POST      api/v1/recruitment/applications/{id}/offer ............................ RecruitmentController@offerApplication
  POST      api/v1/recruitment/applications/{id}/reject .......................... RecruitmentController@rejectApplication
  POST      api/v1/recruitment/applications/{id}/shortlist .................... RecruitmentController@shortlistApplication
  GET|HEAD  api/v1/recruitment/jobs ........................................................... RecruitmentController@jobs
  POST      api/v1/recruitment/jobs ....................................................... RecruitmentController@storeJob
  PUT       api/v1/recruitment/jobs/{id} ................................................. RecruitmentController@updateJob
  POST      api/v1/recruitment/jobs/{id}/close ............................................ RecruitmentController@closeJob
  POST      api/v1/reports ........................................................................ ReportController@store
  GET|HEAD  api/v1/reports/academic-performance ..................................... ReportController@academicPerformance
  GET|HEAD  api/v1/reports/attendance ........................................................ ReportController@attendance
  GET|HEAD  api/v1/reports/class/{id} ....................................................... ReportController@classReport
  GET|HEAD  api/v1/reports/export ................................................................ ReportController@export
  GET|HEAD  api/v1/reports/financial .......................................................... ReportController@financial
  GET|HEAD  api/v1/reports/templates .......................................................... ReportController@templates
  GET|HEAD  api/v1/reports/{reportType} ........................................................ ReportController@generate
  GET|HEAD  api/v1/roles ............................................................................ RoleController@index
  POST      api/v1/roles ............................................................................ RoleController@store
  GET|HEAD  api/v1/roles/{id} ........................................................................ RoleController@show
  PUT       api/v1/roles/{id} ...................................................................... RoleController@update
  DELETE    api/v1/roles/{id} ..................................................................... RoleController@destroy
  GET|HEAD  api/v1/rooms ............................................................................ RoomController@index
  POST      api/v1/rooms ............................................................................ RoomController@store
  PUT       api/v1/rooms/{id} ...................................................................... RoomController@update
  DELETE    api/v1/rooms/{id} ..................................................................... RoomController@destroy
  GET|HEAD  api/v1/school ................................................................... Api\V1\SchoolController@show
  PUT       api/v1/school ................................................................. Api\V1\SchoolController@update
  GET|HEAD  api/v1/school-certificates ................................................. SchoolCertificateController@index
  GET|HEAD  api/v1/school-certificates/{id} ............................................. SchoolCertificateController@show
  GET|HEAD  api/v1/school-certificates/{id}/download ................................ SchoolCertificateController@download
  POST      api/v1/school-certificates/{id}/revoke .................................... SchoolCertificateController@revoke
  GET|HEAD  api/v1/school-currencies ...................................................... SchoolCurrencyController@index
  POST      api/v1/school-currencies ...................................................... SchoolCurrencyController@store
  GET|HEAD  api/v1/school-currencies/{id} .................................................. SchoolCurrencyController@show
  PUT       api/v1/school-currencies/{id} ................................................ SchoolCurrencyController@update
  DELETE    api/v1/school-currencies/{id} ............................................... SchoolCurrencyController@destroy
  GET|HEAD  api/v1/school-languages ....................................................... SchoolLanguageController@index
  POST      api/v1/school-languages ....................................................... SchoolLanguageController@store
  GET|HEAD  api/v1/school-languages/{id} ................................................... SchoolLanguageController@show
  PUT       api/v1/school-languages/{id} ................................................. SchoolLanguageController@update
  DELETE    api/v1/school-languages/{id} ................................................ SchoolLanguageController@destroy
  GET|HEAD  api/v1/school-trips ............................................................... SchoolTripController@index
  POST      api/v1/school-trips ............................................................... SchoolTripController@store
  PUT       api/v1/school-trips/{id} ......................................................... SchoolTripController@update
  POST      api/v1/school-trips/{id}/enroll .................................................. SchoolTripController@enroll
  GET|HEAD  api/v1/school-trips/{id}/enrollments ........................................ SchoolTripController@enrollments
  POST      api/v1/schools/register ..................................................... Api\V1\SchoolController@register
  GET|HEAD  api/v1/settings/config ................................................ Api\V1\SettingsController@publicConfig
  GET|HEAD  api/v1/settings/custom-fields ......................................... Api\V1\SettingsController@customFields
  POST      api/v1/settings/custom-fields ..................................... Api\V1\SettingsController@storeCustomField
  PUT       api/v1/settings/custom-fields/{customField} ...................... Api\V1\SettingsController@updateCustomField
  DELETE    api/v1/settings/custom-fields/{customField} ..................... Api\V1\SettingsController@destroyCustomField
  GET|HEAD  api/v1/settings/school .............................................. Api\V1\SettingsController@schoolSettings
  PUT       api/v1/settings/school ........................................ Api\V1\SettingsController@updateSchoolSettings
  GET|HEAD  api/v1/settings/terminology ............................................ Api\V1\SettingsController@terminology
  PUT       api/v1/settings/terminology ...................................... Api\V1\SettingsController@updateTerminology
  GET|HEAD  api/v1/staff-attendance ...................................................... StaffAttendanceController@index
  POST      api/v1/staff-attendance ...................................................... StaffAttendanceController@store
  GET|HEAD  api/v1/staff-attendance/roster .............................................. StaffAttendanceController@roster
  GET|HEAD  api/v1/staff-attendance/summary ............................................ StaffAttendanceController@summary
  GET|HEAD  api/v1/streams ........................................................... AcademicStructureController@streams
  POST      api/v1/streams ....................................................... AcademicStructureController@storeStream
  PUT       api/v1/streams/{id} ................................................. AcademicStructureController@updateStream
  GET|HEAD  api/v1/student-categories .................................................... StudentCategoryController@index
  POST      api/v1/student-categories .................................................... StudentCategoryController@store
  GET|HEAD  api/v1/student-categories/{id} ................................................ StudentCategoryController@show
  PUT       api/v1/student-categories/{id} .............................................. StudentCategoryController@update
  DELETE    api/v1/student-categories/{id} ............................................. StudentCategoryController@destroy
  GET|HEAD  api/v1/student-portal/announcements ............................. Api\V1\StudentPortalController@announcements
  GET|HEAD  api/v1/student-portal/assignments ................................. Api\V1\StudentPortalController@assignments
  GET|HEAD  api/v1/student-portal/assignments/{id} ......................... Api\V1\StudentPortalController@showAssignment
  POST      api/v1/student-portal/assignments/{id}/submit ................ Api\V1\StudentPortalController@submitAssignment
  GET|HEAD  api/v1/student-portal/attendance ................................... Api\V1\StudentPortalController@attendance
  GET|HEAD  api/v1/student-portal/cbt/available .............................. Api\V1\StudentPortalController@cbtAvailable
  GET|HEAD  api/v1/student-portal/cbt/sessions/{id} ........................ Api\V1\StudentPortalController@showCbtSession
  POST      api/v1/student-portal/cbt/sessions/{id}/anti-cheat ............... Api\V1\StudentPortalController@cbtAntiCheat
  POST      api/v1/student-portal/cbt/sessions/{id}/submit ...................... Api\V1\StudentPortalController@submitCbt
  POST      api/v1/student-portal/cbt/start ...................................... Api\V1\StudentPortalController@startCbt
  GET|HEAD  api/v1/student-portal/dashboard ..................................... Api\V1\StudentPortalController@dashboard
  GET|HEAD  api/v1/student-portal/fees ............................................... Api\V1\StudentPortalController@fees
  GET|HEAD  api/v1/student-portal/grades ........................................... Api\V1\StudentPortalController@grades
  GET|HEAD  api/v1/student-portal/me ................................................... Api\V1\StudentPortalController@me
  GET|HEAD  api/v1/student-portal/timetable ..................................... Api\V1\StudentPortalController@timetable
  GET|HEAD  api/v1/students ............................................................... Api\V1\StudentController@index
  POST      api/v1/students ............................................................... Api\V1\StudentController@store
  POST      api/v1/students/bulk/invoices ................................................. StudentController@bulkInvoices
  POST      api/v1/students/bulk/promote ................................................... StudentController@bulkPromote
  POST      api/v1/students/bulk/status ..................................................... StudentController@bulkStatus
  POST      api/v1/students/promote ............................................................ StudentController@promote
  GET|HEAD  api/v1/students/{student} ...................................................... Api\V1\StudentController@show
  PUT       api/v1/students/{student} .................................................... Api\V1\StudentController@update
  DELETE    api/v1/students/{student} ................................................... Api\V1\StudentController@destroy
  GET|HEAD  api/v1/students/{student}/documents .............................................. StudentController@documents
  POST      api/v1/students/{student}/documents ........................................ StudentController@uploadDocuments
  DELETE    api/v1/students/{student}/documents/{document} ............................. StudentController@destroyDocument
  GET|HEAD  api/v1/students/{student}/exams ...................................................... StudentController@exams
  GET|HEAD  api/v1/students/{student}/guardians ............................................ GuardianController@forStudent
  GET|HEAD  api/v1/students/{student}/id-card/print ........................................ StudentController@printIdCard
  GET|HEAD  api/v1/students/{student}/invoices ................................................ StudentController@invoices
  POST      api/v1/students/{student}/invoices ........................................... StudentController@createInvoice
  GET|HEAD  api/v1/students/{student}/lifecycle .......................................... StudentLifecycleController@show
  POST      api/v1/students/{student}/lifecycle/promote ............................ StudentLifecycleController@promoteOne
  POST      api/v1/students/{student}/lifecycle/transition ......................... StudentLifecycleController@transition
  GET|HEAD  api/v1/students/{student}/performance .......................................... StudentController@performance
  POST      api/v1/students/{student}/photo ................................................ StudentController@uploadPhoto
  POST      api/v1/students/{student}/placements ........................................ StudentLifecycleController@place
  GET|HEAD  api/v1/students/{student}/results/download ................................. StudentController@downloadResults
  GET|HEAD  api/v1/students/{student}/transcript ................................... StudentLifecycleController@transcript
  GET|HEAD  api/v1/students/{student}/transfer-certificate ................ StudentLifecycleController@transferCertificate
  GET|HEAD  api/v1/subject-packages .......................................... AcademicStructureController@subjectPackages
  POST      api/v1/subject-packages ...................................... AcademicStructureController@storeSubjectPackage
  GET|HEAD  api/v1/subject-packages/{id} .................................. AcademicStructureController@showSubjectPackage
  PUT       api/v1/subject-packages/{id} ................................ AcademicStructureController@updateSubjectPackage
  DELETE    api/v1/subject-packages/{id} ............................... AcademicStructureController@destroySubjectPackage
  GET|HEAD  api/v1/subjects ...................................................................... SubjectController@index
  POST      api/v1/subjects ...................................................................... SubjectController@store
  GET|HEAD  api/v1/subjects/{subject} ............................................................. SubjectController@show
  PUT       api/v1/subjects/{subject} ........................................................... SubjectController@update
  DELETE    api/v1/subjects/{subject} .......................................................... SubjectController@destroy
  GET|HEAD  api/v1/teacher-assignments ................................................. TeacherAssignmentController@index
  POST      api/v1/teacher-assignments ................................................. TeacherAssignmentController@store
  PUT       api/v1/teacher-assignments/{id} ........................................... TeacherAssignmentController@update
  DELETE    api/v1/teacher-assignments/{id} .......................................... TeacherAssignmentController@destroy
  POST      api/v1/teacher-portal/ai/generate .................................. Api\V1\TeacherPortalController@aiGenerate
  GET|HEAD  api/v1/teacher-portal/assignments/{assignmentId}/submissions Api\V1\TeacherPortalController@assignmentSubmiss…
  POST      api/v1/teacher-portal/attendance/lock .......................... Api\V1\TeacherPortalController@lockAttendance
  GET|HEAD  api/v1/teacher-portal/attendance/reports .................... Api\V1\TeacherPortalController@attendanceReports
  POST      api/v1/teacher-portal/attendance/submit ...................... Api\V1\TeacherPortalController@submitAttendance
  GET|HEAD  api/v1/teacher-portal/behaviour ............................... Api\V1\TeacherPortalController@behaviourPoints
  POST      api/v1/teacher-portal/behaviour ........................... Api\V1\TeacherPortalController@storeBehaviourPoint
  GET|HEAD  api/v1/teacher-portal/calendar ....................................... Api\V1\TeacherPortalController@calendar
  GET|HEAD  api/v1/teacher-portal/classes ....................................... Api\V1\TeacherPortalController@myClasses
  GET|HEAD  api/v1/teacher-portal/dashboard ..................................... Api\V1\TeacherPortalController@dashboard
  GET|HEAD  api/v1/teacher-portal/department ................................... Api\V1\TeacherPortalController@department
  GET|HEAD  api/v1/teacher-portal/export ........................................... Api\V1\TeacherPortalController@export
  GET|HEAD  api/v1/teacher-portal/interventions ............................. Api\V1\TeacherPortalController@interventions
  POST      api/v1/teacher-portal/interventions ......................... Api\V1\TeacherPortalController@storeIntervention
  GET|HEAD  api/v1/teacher-portal/leave ........................................... Api\V1\TeacherPortalController@myLeave
  POST      api/v1/teacher-portal/leave ........................................ Api\V1\TeacherPortalController@applyLeave
  GET|HEAD  api/v1/teacher-portal/lesson-plans ................................ Api\V1\TeacherPortalController@lessonPlans
  POST      api/v1/teacher-portal/lesson-plans ............................ Api\V1\TeacherPortalController@storeLessonPlan
  PUT       api/v1/teacher-portal/lesson-plans/{id} ...................... Api\V1\TeacherPortalController@updateLessonPlan
  GET|HEAD  api/v1/teacher-portal/notifications ............................. Api\V1\TeacherPortalController@notifications
  POST      api/v1/teacher-portal/notifications/read-all ......... Api\V1\TeacherPortalController@markAllNotificationsRead
  POST      api/v1/teacher-portal/notifications/{id}/read ............ Api\V1\TeacherPortalController@markNotificationRead
  GET|HEAD  api/v1/teacher-portal/online-lessons ............................ Api\V1\TeacherPortalController@onlineLessons
  POST      api/v1/teacher-portal/online-lessons ........................ Api\V1\TeacherPortalController@storeOnlineLesson
  GET|HEAD  api/v1/teacher-portal/participation ............................. Api\V1\TeacherPortalController@participation
  POST      api/v1/teacher-portal/participation ........................ Api\V1\TeacherPortalController@storeParticipation
  GET|HEAD  api/v1/teacher-portal/report-cards ................................ Api\V1\TeacherPortalController@reportCards
  POST      api/v1/teacher-portal/report-cards ............................ Api\V1\TeacherPortalController@storeReportCard
  GET|HEAD  api/v1/teacher-portal/resources ..................................... Api\V1\TeacherPortalController@resources
  POST      api/v1/teacher-portal/resources ................................. Api\V1\TeacherPortalController@storeResource
  GET|HEAD  api/v1/teacher-portal/students/{studentId} ................. Api\V1\TeacherPortalController@classStudentDetail
  POST      api/v1/teacher-portal/submissions ............................. Api\V1\TeacherPortalController@storeSubmission
  POST      api/v1/teacher-portal/submissions/{id}/grade .................. Api\V1\TeacherPortalController@gradeSubmission
  GET|HEAD  api/v1/teacher-portal/substitutions ............................. Api\V1\TeacherPortalController@substitutions
  POST      api/v1/teacher-portal/substitutions/{id}/accept ............ Api\V1\TeacherPortalController@acceptSubstitution
  GET|HEAD  api/v1/teacher-portal/syllabus-topics .......................... Api\V1\TeacherPortalController@syllabusTopics
  POST      api/v1/teacher-portal/syllabus-topics ...................... Api\V1\TeacherPortalController@storeSyllabusTopic
  PUT       api/v1/teacher-portal/syllabus-topics/{id} ................ Api\V1\TeacherPortalController@updateSyllabusTopic
  GET|HEAD  api/v1/teacher-portal/timetable/change-requests ....... Api\V1\TeacherPortalController@timetableChangeRequests
  POST      api/v1/teacher-portal/timetable/change-requests ... Api\V1\TeacherPortalController@storeTimetableChangeRequest
  GET|HEAD  api/v1/teacher-portal/timetable/exams ........................... Api\V1\TeacherPortalController@examTimetable
  GET|HEAD  api/v1/teacher-portal/timetable/free-periods ...................... Api\V1\TeacherPortalController@freePeriods
  GET|HEAD  api/v1/teachers ............................................................... Api\V1\TeacherController@index
  POST      api/v1/teachers ............................................................... Api\V1\TeacherController@store
  PATCH     api/v1/teachers/{id}/status ................................................... TeacherController@updateStatus
  GET|HEAD  api/v1/teachers/{teacher} ...................................................... Api\V1\TeacherController@show
  PUT       api/v1/teachers/{teacher} .................................................... Api\V1\TeacherController@update
  DELETE    api/v1/teachers/{teacher} ................................................... Api\V1\TeacherController@destroy
  GET|HEAD  api/v1/terms ............................................................................ TermController@index
  POST      api/v1/terms ............................................................................ TermController@store
  GET|HEAD  api/v1/terms/current .................................................................. TermController@current
  GET|HEAD  api/v1/terms/{id} ........................................................................ TermController@show
  PUT       api/v1/terms/{id} ...................................................................... TermController@update
  DELETE    api/v1/terms/{id} ..................................................................... TermController@destroy
  GET|HEAD  api/v1/tests ............................................................................ TestController@index
  POST      api/v1/tests ............................................................................ TestController@store
  GET|HEAD  api/v1/tests/{test} ...................................................................... TestController@show
  PUT       api/v1/tests/{test} .................................................................... TestController@update
  DELETE    api/v1/tests/{test} ................................................................... TestController@destroy
  POST      api/v1/tests/{test}/results ..................................................... TestController@recordResults
  GET|HEAD  api/v1/timetable ................................................................... TimetableController@index
  POST      api/v1/timetable ................................................................... TimetableController@store
  POST      api/v1/timetable/generate ....................................................... TimetableController@generate
  POST      api/v1/timetable/generate-bulk .............................................. TimetableController@generateBulk
  PUT       api/v1/timetable/{id} ............................................................. TimetableController@update
  DELETE    api/v1/timetable/{id} ............................................................ TimetableController@destroy
  GET|HEAD  api/v1/transactions .............................................................. TransactionController@index
  POST      api/v1/transactions .............................................................. TransactionController@store
  GET|HEAD  api/v1/transactions/summary .................................................... TransactionController@summary
  PUT       api/v1/transactions/{id} ........................................................ TransactionController@update
  DELETE    api/v1/transactions/{id} ....................................................... TransactionController@destroy
  POST      api/v1/transport/allocations ............................................. TransportController@allocateStudent
  GET|HEAD  api/v1/transport/drivers ......................................................... TransportController@drivers
  POST      api/v1/transport/drivers ..................................................... TransportController@storeDriver
  PUT       api/v1/transport/drivers/{id} ............................................... TransportController@updateDriver
  GET|HEAD  api/v1/transport/routes ........................................................... TransportController@routes
  POST      api/v1/transport/routes ....................................................... TransportController@storeRoute
  PUT       api/v1/transport/routes/{id} ................................................. TransportController@updateRoute
  GET|HEAD  api/v1/transport/vehicles ....................................................... TransportController@vehicles
  POST      api/v1/transport/vehicles ................................................... TransportController@storeVehicle
  PUT       api/v1/transport/vehicles/{id} ............................................. TransportController@updateVehicle
  POST      api/v1/uploads ................................................................. Api\V1\UploadController@store
  POST      api/v1/user/change-password ............................................. Api\V1\AuthController@changePassword
  GET|HEAD  api/v1/user/profile ............................................................ Api\V1\ProfileController@show
  PUT       api/v1/user/profile .......................................................... Api\V1\ProfileController@update
  GET|HEAD  api/v1/users ............................................................................ UserController@index
  POST      api/v1/users ............................................................................ UserController@store
  GET|HEAD  api/v1/users/{id} ........................................................................ UserController@show
  PUT       api/v1/users/{id} ...................................................................... UserController@update
  DELETE    api/v1/users/{id} ..................................................................... UserController@destroy
  PATCH     api/v1/users/{id}/activate ........................................................... UserController@activate
  PATCH     api/v1/users/{id}/deactivate ....................................................... UserController@deactivate
  POST      api/v1/users/{id}/reset-password ................................................ UserController@resetPassword
  PATCH     api/v1/users/{id}/role ............................................................. UserController@assignRole
  GET|HEAD  api/v1/visitors ...................................................................... VisitorController@index
  POST      api/v1/visitors .................................................................... VisitorController@checkIn
  POST      api/v1/visitors/check-in ........................................................... VisitorController@checkIn
  POST      api/v1/visitors/{id}/check-out .................................................... VisitorController@checkOut
  POST      api/v1/webhooks/payments/{provider} ................................... Api\V1\PaymentWebhookController@handle
  GET|HEAD  api/v1/webhooks/payments/{provider}/sandbox-checkout ......... Api\V1\PaymentWebhookController@sandboxCheckout
  GET|HEAD  api/v1/workflows/history .......................................................... WorkflowController@history
  GET|HEAD  api/v1/workflows/pending .......................................................... WorkflowController@pending
  GET|HEAD  api/v1/workflows/{id} ................................................................ WorkflowController@show
  POST      api/v1/workflows/{id}/approve ..................................................... WorkflowController@approve
  POST      api/v1/workflows/{id}/reject ....................................................... WorkflowController@reject
  GET|HEAD  docs.openapi ................................ scribe.openapi › vendor/knuckleswtf/scribe/routes/laravel.php:18

                                                                                                      Showing [683] routes

```

## 4. Eloquent Domain Models
### File: `app/Models/AbacPolicy.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AbacPolicy extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'resource', 'conditions', 'effect', 'is_active'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'is_active' => 'boolean'];
    }
}
```

### File: `app/Models/AcademicCalendarEntry.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AcademicCalendarEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'term_id', 'entry_type', 'title', 'start_date', 'end_date', 'is_holiday', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_holiday' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
```

### File: `app/Models/AdmissionScore.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AdmissionScore extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'enrollment_application_id', 'applicant_name',
        'academic_score', 'interview_score', 'total_score', 'recommendation',
    ];

    protected function casts(): array
    {
        return [
            'academic_score' => 'decimal:2',
            'interview_score' => 'decimal:2',
            'total_score' => 'decimal:2',
        ];
    }
}
```

### File: `app/Models/AlumniRecord.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AlumniRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'full_name', 'graduation_year',
        'email', 'phone', 'current_occupation', 'engagement_history',
    ];

    protected function casts(): array
    {
        return ['engagement_history' => 'array'];
    }
}
```

### File: `app/Models/Announcement.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'title',
        'message',
        'type',
        'target_audience',
        'date',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### File: `app/Models/ApiClient.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'client_id', 'client_secret_hash',
        'scopes', 'is_active', 'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }
}
```

### File: `app/Models/ArchivedRecord.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ArchivedRecord extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'school_id', 'source_table', 'source_id', 'snapshot', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'archived_at' => 'datetime',
        ];
    }
}
```

### File: `app/Models/AssessmentCategory.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentCategory extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'subject_id', 'name', 'type', 'weight', 'is_active'];

    protected function casts(): array
    {
        return ['weight' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(ContinuousAssessment::class, 'category_id');
    }
}
```

### File: `app/Models/Asset.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'asset_tag', 'name', 'category', 'purchase_date',
        'purchase_cost', 'location', 'custodian_user_id', 'status',
    ];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'purchase_cost' => 'decimal:2'];
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class);
    }
}
```

### File: `app/Models/AssetDisposal.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDisposal extends Model
{

    protected $guarded = [];
}
```

### File: `app/Models/AssetMaintenanceLog.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceLog extends Model
{
    protected $fillable = ['asset_id', 'maintenance_date', 'description', 'cost', 'performed_by'];

    protected function casts(): array
    {
        return ['maintenance_date' => 'date', 'cost' => 'decimal:2'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
```

### File: `app/Models/Assignment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'subject',
        'class_id',
        'teacher_id',
        'due_date',
        'total_marks',
        'status',
        'submissions_count',
        'instructions',
        'attachment_url',
        'submission_type',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'total_marks' => 'decimal:2',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/AssignmentSubmission.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'assignment_id', 'student_id', 'status', 'file_url', 'content',
        'score', 'teacher_comment', 'submitted_at', 'graded_at', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/Attendance.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'class_id',
        'teacher_id',
        'date',
        'status',
        'time_in',
        'time_out',
        'remarks',
        'marked_by',
        'school_id',
        'subject_id',
        'lesson_type',
        'parent_notified',
        'notification_sent_at',
        'submitted_at',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'parent_notified' => 'boolean',
            'notification_sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/AttendanceSession.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSession extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'teacher_id', 'date', 'period',
        'submitted_at', 'locked_at', 'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'submitted_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}
```

### File: `app/Models/AuditLog.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'school_id', 'user_id', 'module', 'action', 'auditable_type', 'auditable_id',
        'description', 'old_values', 'new_values', 'metadata',
        'ip_address', 'user_agent', 'device_type', 'platform', 'location',
        'request_method', 'request_path', 'created_at',
        'integrity_hash', 'previous_hash',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

### File: `app/Models/BankStatementLine.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'transaction_date', 'reference', 'description',
        'amount', 'currency', 'matched_payment_id', 'match_status',
    ];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'decimal:2'];
    }
}
```

### File: `app/Models/BehaviorPoint.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BehaviorPoint extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'points', 'category',
        'description', 'recorded_by', 'recorded_on',
    ];

    protected function casts(): array
    {
        return ['recorded_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/Budget.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'fiscal_year',
        'department',
        'allocated_amount',
        'spent_amount',
        'currency',
        'status',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'allocated_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function remainingAmount(): float
    {
        return (float) $this->allocated_amount - (float) $this->spent_amount;
    }
}
```

### File: `app/Models/CbtExamSession.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CbtExamSession extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'question_ids', 'started_at',
        'submitted_at', 'score', 'status',
    ];

    protected function casts(): array
    {
        return [
            'question_ids' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    public function responses(): HasMany
    {
        return $this->hasMany(CbtResponse::class, 'session_id');
    }
}
```

### File: `app/Models/CbtResponse.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CbtResponse extends Model
{
    protected $fillable = ['session_id', 'question_id', 'answer', 'is_correct'];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }
}
```

### File: `app/Models/Certificate.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'certificate_template_id', 'student_id', 'certificate_type', 'title',
        'verification_code', 'content_html', 'metadata',
        'issued_by', 'issued_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
```

### File: `app/Models/CertificateTemplate.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateTemplate extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'certificate_type',
        'title',
        'body_html',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/ChartOfAccount.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use BelongsToSchool;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'school_id', 'code', 'name', 'account_type', 'parent_id', 'normal_balance', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }
}
```

### File: `app/Models/ClassModel.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassModel extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'form',
        'school',
        'capacity',
        'current_enrollment',
        'teacher_id',
        'status',
        'school_id',
        'grade_level_id',
        'stream_id',
        'room_id',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /** @deprecated Prefer students() via class_id; kept for legacy string class column. */
    public function studentsByClassName(): HasMany
    {
        return $this->hasMany(Student::class, 'class', 'name');
    }

    /** @alias students() */
    public function studentsByClassId(): HasMany
    {
        return $this->students();
    }

    public function timetable(): HasMany
    {
        return $this->hasMany(Timetable::class, 'class_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get enrollment records for this class
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get students enrolled in this class (many-to-many via enrollments)
     */
    public function enrolledStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
            ->withPivot('academic_year', 'enrolled_at', 'left_at', 'status')
            ->withTimestamps();
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
```

### File: `app/Models/ClassParticipationRecord.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassParticipationRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'student_id', 'class_id', 'subject_id',
        'recorded_on', 'score', 'notes',
    ];

    protected function casts(): array
    {
        return ['recorded_on' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
```

### File: `app/Models/ClassSubstitution.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubstitution extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'class_id', 'subject_id', 'absent_teacher_id', 'substitute_teacher_id',
        'leave_request_id', 'date', 'period', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function absentTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'absent_teacher_id');
    }

    public function substituteTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'substitute_teacher_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/ClinicVisit.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicVisit extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/CommunicationMessage.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationMessage extends Model
{
    protected $fillable = ['thread_id', 'sender_id', 'body', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CommunicationThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
```

### File: `app/Models/CommunicationThread.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunicationThread extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'parent_user_id', 'staff_user_id', 'subject', 'status', 'last_message_at',
    ];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunicationMessage::class, 'thread_id');
    }
}
```

### File: `app/Models/ComplianceRequirement.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ComplianceRequirement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'authority', 'requirement_code', 'title', 'due_date', 'status', 'evidence',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'evidence' => 'array'];
    }
}
```

### File: `app/Models/ConsentForm.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentForm extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/ConsentResponse.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentResponse extends Model
{

    protected $guarded = [];
}
```

### File: `app/Models/ContinuousAssessment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContinuousAssessment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'subject_id', 'category_id', 'term_id',
        'title', 'score', 'max_score', 'assessed_on', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'assessed_on' => 'date',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssessmentCategory::class, 'category_id');
    }
}
```

### File: `app/Models/CrossSchoolTransfer.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrossSchoolTransfer extends Model
{
    protected $fillable = [
        'student_id', 'from_school_id', 'to_school_id', 'status', 'requested_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }
}
```

### File: `app/Models/CurriculumVersion.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumVersion extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'term_id', 'academic_year', 'version_number',
        'syllabus', 'learning_outcomes', 'status', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'learning_outcomes' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/CustomField.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    use BelongsToSchool;

    public const ENTITY_STUDENT = 'student';

    public const ENTITY_TEACHER = 'teacher';

    public const ENTITY_STAFF = 'staff';

    public const FIELD_TYPES = [
        'text', 'textarea', 'number', 'date', 'boolean', 'select', 'multiselect', 'email', 'phone',
    ];

    protected $fillable = [
        'school_id',
        'entity_type',
        'name',
        'slug',
        'field_type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}
```

### File: `app/Models/CustomFieldValue.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'custom_field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
```

### File: `app/Models/DataMaskingRule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DataMaskingRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'module', 'field', 'visible_roles', 'mask_pattern', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'visible_roles' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
```

### File: `app/Models/Department.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'head_teacher_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function headTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'head_teacher_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'department', 'name');
    }
}
```

### File: `app/Models/Designation.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
```

### File: `app/Models/DisciplinaryRecord.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryRecord extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'incident_date', 'category', 'severity',
        'description', 'action_taken', 'recorded_by', 'parent_notified', 'parent_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'parent_notified' => 'boolean',
            'parent_notified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
```

### File: `app/Models/DocumentSignature.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSignature extends Model
{
    protected $fillable = [
        'document_id', 'signer_id', 'signer_role', 'signature_hash',
        'ip_address', 'signed_at',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SignableDocument::class, 'document_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }
}
```

### File: `app/Models/Driver.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/Employee.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'employee_number',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'designation_id',
        'department_id',
        'joining_date',
        'employment_type',
        'status',
        'base_salary',
        'salary_currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'base_salary' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            if (empty($employee->name)) {
                $employee->name = trim($employee->first_name.' '.$employee->last_name);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
```

### File: `app/Models/Enrollment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'stream_id',
        'house_id',
        'academic_year',
        'enrolled_at',
        'left_at',
        'status',
        'reason',
        'changed_by',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/EnrollmentApplication.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentApplication extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'first_name',
        'surname',
        'date_of_birth',
        'gender',
        'national_id',
        'address',
        'suburb',
        'phone',
        'email',
        'previous_school',
        'grade_applying_for',
        'academic_year',
        'guardian_first_name',
        'guardian_surname',
        'guardian_relationship',
        'guardian_phone',
        'guardian_email',
        'guardian_address',
        'guardian_employer',
        'medical_conditions',
        'allergies',
        'emergency_contact',
        'emergency_phone',
        'birth_certificate',
        'report_cards',
        'medical_certificate',
        'passport_photo',
        'status',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'student_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'birth_certificate' => 'boolean',
            'report_cards' => 'boolean',
            'medical_certificate' => 'boolean',
            'passport_photo' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/Exam.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'term_id',
        'grade_level_id',
        'subject_id',
        'name',
        'description',
        'exam_date',
        'start_time',
        'end_time',
        'total_marks',
        'passing_marks',
        'academic_year',
        'is_published',
        'results_approved_at',
        'results_approved_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date:Y-m-d',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'total_marks' => 'decimal:2',
            'passing_marks' => 'decimal:2',
            'is_published' => 'boolean',
            'results_approved_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Get all students in the grade level for this exam
     */
    public function getStudentsAttribute()
    {
        return Student::where('school_id', $this->school_id)
            ->where('grade_level_id', $this->grade_level_id)
            ->where('status', 'active')
            ->get();
    }
}
```

### File: `app/Models/ExamAntiCheatLog.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAntiCheatLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['session_id', 'event_type', 'metadata', 'logged_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'logged_at' => 'datetime'];
    }
}
```

### File: `app/Models/ExamResult.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'exam_id',
        'student_id',
        'subject_id',
        'marks_obtained',
        'total_marks',
        'percentage',
        'grade',
        'remarks',
        'status',
        'entered_by',
        'approved_by',
        'approved_at',
        'is_locked',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
        'marks_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];
    }

    protected static function booted()
    {
        static::creating(function ($examResult) {
            // Calculate percentage
            if ($examResult->total_marks > 0) {
                $examResult->percentage = ($examResult->marks_obtained / $examResult->total_marks) * 100;
            }

            // Get grade from grading scale
            if ($examResult->percentage !== null) {
                $grade = \App\Models\GradingScale::getGradeForScore($examResult->school_id, $examResult->percentage);
                $examResult->grade = $grade;
            }
        });

        static::updating(function ($examResult) {
            if ($examResult->is_locked || ($examResult->getOriginal('is_locked') && $examResult->isDirty(['marks_obtained', 'total_marks', 'remarks', 'grade', 'percentage']))) {
                throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Exam marks are locked after moderation approval.');
            }

            // Recalculate percentage if marks changed
            if ($examResult->isDirty(['marks_obtained', 'total_marks']) && $examResult->total_marks > 0) {
                $examResult->percentage = ($examResult->marks_obtained / $examResult->total_marks) * 100;
            }

            // Recalculate grade if percentage changed
            if ($examResult->isDirty('percentage') && $examResult->percentage !== null) {
                $grade = GradingScale::getGradeForScore($examResult->school_id, $examResult->percentage);
                $examResult->grade = $grade;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/ExamSchedule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'exam_id',
        'class_id',
        'subject_id',
        'room_id',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'invigilator',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
```

### File: `app/Models/ExchangeRate.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'from_currency', 'to_currency', 'rate', 'effective_date'];

    protected function casts(): array
    {
        return ['rate' => 'decimal:8', 'effective_date' => 'date'];
    }
}
```

### File: `app/Models/ExpenseHead.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseHead extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/FeeCategory.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'fee_category_id');
    }

    /**
     * Legacy string-based structures that still match this category name.
     */
    public function feeStructuresByName(): HasMany
    {
        return $this->hasMany(FeeStructure::class, 'category', 'name');
    }
}
```

### File: `app/Models/FeeDiscount.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDiscount extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'discount_type',
        'value',
        'fee_category_id',
        'student_category_id',
        'starts_on',
        'ends_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function studentCategory(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class);
    }
}
```

### File: `app/Models/FeeGroup.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeeGroup extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(FeeCategory::class, 'fee_group_items')
            ->withTimestamps();
    }
}
```

### File: `app/Models/FeePenalty.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePenalty extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'rule_id', 'invoice_id', 'student_id',
        'amount', 'applied_on', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied_on' => 'date',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FeePenaltyRule::class, 'rule_id');
    }
}
```

### File: `app/Models/FeePenaltyRule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePenaltyRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'grace_days', 'penalty_type',
        'penalty_value', 'frequency', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'penalty_value' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(FeePenalty::class, 'rule_id');
    }
}
```

### File: `app/Models/FeeStructure.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'class_id',
        'class_name',
        'fee_category_id',
        'category',
        'amount',
        'currency',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'fee_category_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class, 'fee_structure_id');
    }
}
```

### File: `app/Models/GoodsReceipt.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/Grade.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'student_id',
        'test_id',
        'assignment_id',
        'subject',
        'subject_id',
        'assessment_type',
        'score',
        'total',
        'grade',
        'term',
        'year',
        'class_id',
        'teacher_id',
        'remarks',
        'school_id',
        'grade_level_id',
        'grading_scale_id',
    ];

    protected function casts(): array
    {
        return [
        'score' => 'decimal:2',
        'total' => 'decimal:2',
        'year' => 'integer',
    ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function gradingScale(): BelongsTo
    {
        return $this->belongsTo(GradingScale::class);
    }
}
```

### File: `app/Models/GradeLevel.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'order',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
```

### File: `app/Models/GradeLevelSubject.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeLevelSubject extends Model
{
    use BelongsToSchool;

    protected $table = 'grade_level_subjects';

    protected $fillable = [
        'school_id',
        'grade_level_id',
        'subject_id',
        'stream_id',
        'is_core',
    ];

    protected function casts(): array
    {
        return ['is_core' => 'boolean'];
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }
}
```

### File: `app/Models/GradebookRule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GradebookRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'grade_level_id', 'category_weights', 'pass_mark',
    ];

    protected function casts(): array
    {
        return ['category_weights' => 'array', 'pass_mark' => 'decimal:2'];
    }
}
```

### File: `app/Models/GradingScale.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScale extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'grade',
        'min_score',
        'max_score',
        'description',
        'order',
    ];

    protected function casts(): array
    {
        return [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the grade letter for a given score
     */
    public static function getGradeForScore($schoolId, $score): ?string
    {
        return static::where('school_id', $schoolId)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('order', 'desc')
            ->value('grade');
    }
}
```

### File: `app/Models/GroupPolicy.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupPolicy extends Model
{
    protected $fillable = ['parent_school_id', 'policy_code', 'name', 'rules', 'enforce_on_branches'];

    protected function casts(): array
    {
        return ['rules' => 'array', 'enforce_on_branches' => 'boolean'];
    }

    public function parentSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'parent_school_id');
    }
}
```

### File: `app/Models/Guardian.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'relationship',
        'address',
        'national_id',
        'occupation',
        'is_primary',
        'can_receive_notifications',
    ];

    protected function casts(): array
    {
        return [
        'is_primary' => 'boolean',
        'can_receive_notifications' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
            ->withPivot('relationship', 'is_primary', 'can_pickup', 'emergency_contact')
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
```

### File: `app/Models/HelpDeskTicket.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpDeskTicket extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### File: `app/Models/HolidayAttendance.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayAttendance extends Model
{
    use BelongsToSchool;

    protected $table = 'holiday_attendance';

    protected $fillable = [
        'school_id', 'holiday_program_id', 'student_id', 'date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(HolidayProgram::class, 'holiday_program_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/HolidayEnrollment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayEnrollment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'holiday_program_id', 'student_id', 'invoice_id',
        'status', 'enrolled_at',
    ];

    protected function casts(): array
    {
        return ['enrolled_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(HolidayProgram::class, 'holiday_program_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
```

### File: `app/Models/HolidayProgram.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HolidayProgram extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'academic_year', 'start_date', 'end_date',
        'fee_amount', 'currency', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'fee_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(HolidayEnrollment::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(HolidayAttendance::class);
    }
}
```

### File: `app/Models/Hostel.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class);
    }
}
```

### File: `app/Models/HostelAllocation.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['allocated_at' => 'date', 'left_at' => 'date'];
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HostelBed::class, 'bed_id');
    }
}
```

### File: `app/Models/HostelBed.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelBed extends Model
{
    protected $guarded = [];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }
}
```

### File: `app/Models/HostelRoom.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelRoom extends Model
{
    protected $guarded = [];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function beds(): HasMany
    {
        return $this->hasMany(HostelBed::class, 'room_id');
    }
}
```

### File: `app/Models/House.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class House extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'color',
        'teacher_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'house_id');
    }
}
```

### File: `app/Models/HubMessage.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HubMessage extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'sender_id', 'subject', 'body', 'channels',
        'audience_type', 'audience_ids', 'status', 'sent_at', 'delivery_stats',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience_ids' => 'array',
            'delivery_stats' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(HubMessageDelivery::class);
    }
}
```

### File: `app/Models/HubMessageDelivery.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HubMessageDelivery extends Model
{
    protected $fillable = [
        'hub_message_id', 'recipient_user_id', 'channel', 'recipient_address',
        'status', 'delivered_at', 'read_at', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(HubMessage::class, 'hub_message_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
```

### File: `app/Models/IncidentReport.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentReport extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/IncomeHead.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeHead extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/InstalmentPlan.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstalmentPlan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'invoice_id', 'total_amount', 'currency', 'status',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InstalmentScheduleItem::class, 'plan_id');
    }
}
```

### File: `app/Models/InstalmentScheduleItem.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalmentScheduleItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'plan_id', 'installment_number', 'due_date', 'amount', 'amount_paid', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstalmentPlan::class, 'plan_id');
    }
}
```

### File: `app/Models/IntegrationConnector.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class IntegrationConnector extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'provider', 'connector_type', 'config', 'is_active', 'last_sync_at',
    ];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean', 'last_sync_at' => 'datetime'];
    }
}
```

### File: `app/Models/InventoryItem.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'sku', 'type', 'size', 'unit_price', 'currency',
        'stock_quantity', 'reorder_level', 'billing_mode', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
```

### File: `app/Models/InventoryMovement.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'inventory_item_id', 'type', 'quantity_change',
        'quantity_after', 'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
```

### File: `app/Models/InventorySale.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySale extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'sale_number', 'student_id', 'total_amount', 'currency',
        'payment_method', 'status', 'invoice_id', 'sold_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventorySaleItem::class);
    }
}
```

### File: `app/Models/InventorySaleItem.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySaleItem extends Model
{
    protected $fillable = [
        'inventory_sale_id', 'inventory_item_id', 'quantity', 'unit_price', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(InventorySale::class, 'inventory_sale_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
```

### File: `app/Models/Invoice.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'parent_id',
        'fee_structure_id',
        'fee_discount_id',
        'fee_group_id',
        'description',
        'amount',
        'original_amount',
        'discount_amount',
        'amount_paid',
        'balance',
        'currency',
        'due_date',
        'status',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeDiscount(): BelongsTo
    {
        return $this->belongsTo(FeeDiscount::class);
    }

    public function feeGroup(): BelongsTo
    {
        return $this->belongsTo(FeeGroup::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
```

### File: `app/Models/JobApplication.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
```

### File: `app/Models/JobPosting.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
```

### File: `app/Models/JournalEntry.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'reference', 'entry_date', 'description', 'status', 'currency', 'created_by',
    ];

    protected function casts(): array
    {
        return ['entry_date' => 'date'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
```

### File: `app/Models/JournalLine.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'journal_entry_id', 'account_id', 'debit', 'credit', 'currency', 'memo',
    ];

    protected function casts(): array
    {
        return ['debit' => 'decimal:2', 'credit' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }
}
```

### File: `app/Models/LearningOutcomeRecord.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearningOutcomeRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'subject_id', 'outcome_code',
        'competency_level', 'score', 'evidence', 'assessed_by', 'assessed_on',
    ];

    protected function casts(): array
    {
        return ['assessed_on' => 'date', 'score' => 'decimal:2'];
    }
}
```

### File: `app/Models/LeaveRequest.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToSchool, Auditable, HasFactory;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'leave_type_id',
        'requested_by',
        'type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getAuditIdentifier(): string
    {
        $teacher = $this->relationLoaded('teacher')
            ? $this->teacher
            : $this->teacher()->first();

        $who = $teacher
            ? trim((string) ($teacher->name ?: ($teacher->first_name.' '.$teacher->last_name)))
            : 'staff member';

        $type = $this->type ? str_replace('_', ' ', (string) $this->type) : 'leave';
        $from = $this->start_date?->format('j M Y');
        $to = $this->end_date?->format('j M Y');

        if ($from && $to) {
            return ucfirst($type)." leave for {$who} ({$from} – {$to})";
        }

        return ucfirst($type)." leave for {$who}";
    }
}
```

### File: `app/Models/LeaveType.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveType extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'default_days',
        'is_paid',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'default_days' => 'integer',
            'is_paid' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/LessonPlan.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPlan extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'class_id', 'subject_id', 'title', 'plan_type',
        'planned_date', 'topic', 'objectives', 'outcomes', 'activities', 'resources',
        'status', 'completed_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'completed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/LibraryBook.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/LibraryLoan.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'due_at' => 'date',
            'returned_at' => 'date',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(LibraryMember::class, 'library_member_id');
    }
}
```

### File: `app/Models/LibraryMember.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryMember extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'member_type',
        'member_id',
        'member_number',
        'name',
        'email',
        'phone',
        'status',
        'joined_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_on' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/LicenseKey.php`
```php
<?php

namespace App\Models;

use App\Enums\LicenseKeyStatus;
use App\Enums\LicensePlanType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseKey extends Model
{
    protected $fillable = [
        'key_prefix',
        'key_hash',
        'plan_type',
        'duration_months',
        'amount',
        'currency',
        'status',
        'school_id',
        'activated_at',
        'paid_at',
        'expires_at',
        'revoked_at',
        'customer_name',
        'customer_email',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'plan_type' => LicensePlanType::class,
            'status' => LicenseKeyStatus::class,
            'amount' => 'decimal:2',
            'activated_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isLifetime(): bool
    {
        return $this->plan_type === LicensePlanType::Lifetime;
    }
}
```

### File: `app/Models/LoginHistory.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $table = 'login_history';

    protected $fillable = [
        'school_id', 'user_id', 'email', 'event', 'ip_address', 'user_agent',
        'device_type', 'platform', 'location', 'token_name', 'failure_reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### File: `app/Models/MessageCampaign.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MessageCampaign extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'channels', 'audience_filter', 'message_body',
        'scheduled_at', 'sent_at', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'audience_filter' => 'array',
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
```

### File: `app/Models/NotificationQueue.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationQueue extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'notification_queue';

    protected $fillable = [
        'school_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'guardian_id',
        'parent_user_id',
        'channel',
        'recipient_email',
        'recipient_phone',
        'subject',
        'message',
        'data',
        'status',
        'sent_at',
        'error_message',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
        'data' => 'array',
        'sent_at' => 'datetime',
        'retry_count' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
```

### File: `app/Models/OnlineLesson.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineLesson extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'class_id', 'subject_id', 'title', 'lesson_type',
        'scheduled_at', 'meeting_url', 'recording_url', 'description', 'status',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/OperationsAlert.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class OperationsAlert extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'severity', 'category', 'title', 'message',
        'metadata', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
```

### File: `app/Models/ParentNotification.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentNotification extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'parent_user_id', 'student_id', 'type', 'title', 'body', 'data', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/Payment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'student_id',
        'parent_id',
        'invoice_id',
        'amount',
        'currency',
        'method',
        'reference',
        'status',
        'date',
        'notes',
        'created_by',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
}
```

### File: `app/Models/PaymentGatewayConfig.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGatewayConfig extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'provider', 'credentials', 'is_active',
        'supports_cards', 'supports_mobile_money', 'supports_bank_transfer',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected $appends = [
        'credentials_configured',
    ];

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'is_active' => 'boolean',
            'supports_cards' => 'boolean',
            'supports_mobile_money' => 'boolean',
            'supports_bank_transfer' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentGatewayTransaction::class, 'config_id');
    }

    public function getCredentialsConfiguredAttribute(): bool
    {
        return ! empty($this->credentials);
    }
}
```

### File: `app/Models/PaymentGatewayTransaction.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayTransaction extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'config_id', 'invoice_id', 'student_id',
        'provider_reference', 'internal_reference', 'amount', 'currency',
        'payment_method', 'status', 'provider_response', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'provider_response' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewayConfig::class, 'config_id');
    }
}
```

### File: `app/Models/Payroll.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'payroll';

    protected $fillable = [
        'school_id',
        'teacher_id',
        'month',
        'year',
        'base_salary',
        'allowances',
        'allowances_total',
        'gross_salary',
        'deductions',
        'deductions_total',
        'net_salary',
        'amount_paid',
        'currency',
        'status',
        'paid_at',
        'payment_method',
        'payment_reference',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
        'base_salary' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'paid_at' => 'date',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
```

### File: `app/Models/PerformanceReview.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'staff_user_id', 'reviewer_id', 'period',
        'overall_score', 'summary', 'criteria_scores', 'status',
    ];

    protected function casts(): array
    {
        return ['criteria_scores' => 'array', 'overall_score' => 'decimal:2'];
    }
}
```

### File: `app/Models/Policy.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Policy extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/PolicyRule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PolicyRule extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'code', 'name', 'module', 'trigger_event',
        'conditions', 'actions', 'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
```

### File: `app/Models/PromotionRule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'conditions', 'action', 'is_active'];

    protected function casts(): array
    {
        return ['conditions' => 'array', 'is_active' => 'boolean'];
    }
}
```

### File: `app/Models/PurchaseRequisition.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisition extends Model
{
    use BelongsToSchool;

    public const SPEND_TYPES = [
        'procurement',
        'petty_cash',
        'utilities',
        'travel',
        'maintenance',
        'other',
    ];

    protected $fillable = [
        'school_id',
        'requested_by',
        'department_id',
        'vendor_id',
        'title',
        'description',
        'spend_type',
        'estimated_cost',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'disbursed_at',
        'disbursed_by',
        'transaction_id',
        'status',
        'workflow_instance_id',
    ];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'disbursed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'requisition_id');
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    public function ledgerCategory(): string
    {
        return match ($this->spend_type) {
            'petty_cash' => 'petty_cash',
            'utilities' => 'utilities',
            'travel' => 'travel',
            'maintenance' => 'maintenance',
            'other' => 'other',
            default => 'procurement',
        };
    }
}
```

### File: `app/Models/PurchaseRequisitionItem.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    protected $fillable = ['requisition_id', 'description', 'quantity', 'unit_cost'];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'requisition_id');
    }
}
```

### File: `app/Models/QuestionBankItem.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class QuestionBankItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'question_text', 'question_type', 'options',
        'correct_answer', 'difficulty', 'tags', 'marks',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'tags' => 'array'];
    }
}
```

### File: `app/Models/RecordVersion.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordVersion extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'school_id', 'versionable_type', 'versionable_id', 'version_number',
        'snapshot', 'changed_by', 'change_reason', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
```

### File: `app/Models/Refund.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'payment_id', 'student_id', 'amount', 'reason',
        'status', 'requested_by', 'approved_by', 'processed_at', 'reference',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
```

### File: `app/Models/RemarkRequest.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RemarkRequest extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'exam_result_id', 'student_id', 'reason', 'status',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }
}
```

### File: `app/Models/ReportCardNarrative.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardNarrative extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'student_id', 'class_id', 'term_id',
        'academic_comment', 'behaviour_comment', 'recommendations', 'strengths',
        'areas_for_improvement', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
```

### File: `app/Models/ReportTemplate.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTemplate extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'name',
        'description',
        'category',
        'frequency',
        'recipients',
        'parameters',
        'school_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
        'recipients' => 'array',
        'parameters' => 'array',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### File: `app/Models/RetentionPolicy.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RetentionPolicy extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'module', 'retain_years', 'archive_action', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
```

### File: `app/Models/RevenueRecognitionRule.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RevenueRecognitionRule extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'name', 'recognition_method', 'config', 'is_active'];

    protected function casts(): array
    {
        return ['config' => 'array', 'is_active' => 'boolean'];
    }
}
```

### File: `app/Models/Role.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'permission_ids'];

    protected function casts(): array
    {
        return [
        'permission_ids' => 'array',
    ];
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }
}
```

### File: `app/Models/Room.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'type',
        'capacity',
        'description',
        'location',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
        'capacity' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }
}
```

### File: `app/Models/Scholarship.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scholarship extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'name', 'type', 'amount', 'percentage',
        'criteria', 'application_deadline', 'slots', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'application_deadline' => 'date',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }
}
```

### File: `app/Models/ScholarshipApplication.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipApplication extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'scholarship_id', 'student_id', 'school_id', 'motivation',
        'supporting_data', 'status', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'supporting_data' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/School.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'parent_school_id',
        'branch_type',
        'address',
        'phone',
        'email',
        'status',
        'contact_person',
        'contact_phone',
        'contact_email',
        'currency_default',
        'currency',
        'currency_locked',
        'academic_year',
        'current_term',
        'settings',
        'principal_name',
        'website',
        'year_founded',
        'suburb',
        'city',
        'student_capacity',
        'timezone',
        'motto',
        'logo_path',
        'license_status',
        'license_plan',
        'license_expires_at',
    ];

    protected function casts(): array
    {
        return [
        'settings' => 'array',
        'currency_locked' => 'boolean',
        'license_expires_at' => 'datetime',
    ];
    }

    public function licenseKeys(): HasMany
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function reportTemplates(): HasMany
    {
        return $this->hasMany(ReportTemplate::class, 'school_id');
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payroll(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function gradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevel::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function gradingScales(): HasMany
    {
        return $this->hasMany(GradingScale::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function notificationQueue(): HasMany
    {
        return $this->hasMany(NotificationQueue::class);
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function enrollmentApplications(): HasMany
    {
        return $this->hasMany(EnrollmentApplication::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function schoolSettings(): HasMany
    {
        return $this->hasMany(SchoolSetting::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(SchoolDomain::class);
    }

    /**
     * Get the default currency for the school
     */
    public function getDefaultCurrency(): string
    {
        return $this->currency_default ?? $this->attributes['currency'] ?? 'USD';
    }

    /**
     * Get the default currency for the school
     */
    public function getCurrencyAttribute(): string
    {
        return $this->getDefaultCurrency();
    }

    /**
     * Validate currency is allowed (USD or ZWG)
     */
    public function validateCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), ['USD', 'ZWG']);
    }
}
```

### File: `app/Models/SchoolBackup.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBackup extends Model
{
    protected $fillable = [
        'school_id',
        'path',
        'status',
        'table_count',
        'row_count',
        'file_size',
        'created_by',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'restored_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### File: `app/Models/SchoolCurrency.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolCurrency extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'symbol',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/SchoolDomain.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'domain',
        'verification_token',
        'is_primary',
        'is_verified',
        'status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/SchoolEvent.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolEvent extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

### File: `app/Models/SchoolLanguage.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLanguage extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/SchoolSetting.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'group',
        'key',
        'value',
        'type',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
        'is_public' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function getCastedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->attributes['value'], FILTER_VALIDATE_BOOLEAN),
            'integer', 'number' => is_numeric($this->attributes['value']) ? (int) $this->attributes['value'] : 0,
            'float', 'decimal' => is_numeric($this->attributes['value']) ? (float) $this->attributes['value'] : 0.0,
            'json' => json_decode($this->attributes['value'] ?? 'null', true),
            'encrypted' => $this->decryptValue($this->attributes['value'] ?? null),
            default => $this->attributes['value'],
        };
    }

    public function setTypedValue(mixed $value): void
    {
        $this->value = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            'encrypted' => $value === null || $value === ''
                ? null
                : \Illuminate\Support\Facades\Crypt::encryptString((string) $value),
            default => (string) $value,
        };
    }

    private function decryptValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
```

### File: `app/Models/SchoolTrip.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolTrip extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'destination',
        'trip_date',
        'return_date',
        'fee_amount',
        'currency',
        'capacity',
        'is_active',
        'open_for_registration',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'return_date' => 'date',
            'fee_amount' => 'decimal:2',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'open_for_registration' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(SchoolTripEnrollment::class);
    }

    public function enrolledCount(): int
    {
        return $this->enrollments()->where('status', 'enrolled')->count();
    }

    public function spotsRemaining(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, (int) $this->capacity - $this->enrolledCount());
    }
}
```

### File: `app/Models/SchoolTripEnrollment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolTripEnrollment extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'school_trip_id',
        'student_id',
        'invoice_id',
        'status',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(SchoolTrip::class, 'school_trip_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
```

### File: `app/Models/SchoolUserMembership.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolUserMembership extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### File: `app/Models/SecureDocument.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecureDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'vault_type', 'title', 'encrypted_payload',
        'content_hash', 'access_roles', 'uploaded_by', 'exam_id',
    ];

    protected function casts(): array
    {
        return ['access_roles' => 'array'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(SecureDocumentAccessLog::class, 'document_id');
    }
}
```

### File: `app/Models/SecureDocumentAccessLog.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureDocumentAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_id', 'user_id', 'action', 'ip_address', 'accessed_at',
    ];

    protected function casts(): array
    {
        return ['accessed_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SecureDocument::class, 'document_id');
    }
}
```

### File: `app/Models/Setting.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Setting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'school_id',
    ];

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get setting value with type casting
     */
    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
            case 'integer':
                return is_numeric($value) ? (int)$value : 0;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Set setting value with type conversion
     */
    public function setValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                $this->attributes['value'] = $value ? '1' : '0';
                break;
            case 'json':
                $this->attributes['value'] = json_encode($value);
                break;
            default:
                $this->attributes['value'] = (string)$value;
        }
    }

    /**
     * Get setting by key (scoped to current user's school).
     */
    public static function get($key, $default = null)
    {
        $schoolId = Auth::user()?->school_id;
        if ($schoolId === null) {
            return $default;
        }
        $setting = self::withoutGlobalScopes()->where('school_id', $schoolId)->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting by key (per-school; requires school context).
     */
    public static function set($key, $value, $type = 'string', $category = 'general')
    {
        $schoolId = Auth::user()?->school_id;
        if ($schoolId === null) {
            throw new \RuntimeException('Settings require a school context. User must belong to a school.');
        }

        // Handle nested keys like "academic.currentTerm"
        $parts = explode('.', $key);
        if (count($parts) > 1) {
            $category = $parts[0];
            $key = $parts[1];
        }

        return self::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $schoolId, 'key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'category' => $category,
                'school_id' => $schoolId,
            ]
        );
    }
}
```

### File: `app/Models/SignableDocument.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignableDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'title', 'document_type', 'content', 'status',
        'created_by', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class, 'document_id');
    }
}
```

### File: `app/Models/StaffAttendance.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'date',
        'staff_type',
        'staff_id',
        'status',
        'remarks',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
```

### File: `app/Models/StaffCertification.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StaffCertification extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'certification_name', 'issuer', 'issued_on', 'expires_on',
    ];

    protected function casts(): array
    {
        return ['issued_on' => 'date', 'expires_on' => 'date'];
    }
}
```

### File: `app/Models/StaffContract.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StaffContract extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'contract_type', 'start_date', 'end_date', 'salary', 'status', 'renewal_due_at',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'salary' => 'decimal:2', 'renewal_due_at' => 'datetime'];
    }
}
```

### File: `app/Models/StaffFeedComment.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffFeedComment extends Model
{
    protected $fillable = ['post_id', 'author_id', 'body'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(StaffFeedPost::class, 'post_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
```

### File: `app/Models/StaffFeedPost.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffFeedPost extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'author_id', 'body', 'attachments', 'visibility',
    ];

    protected function casts(): array
    {
        return ['attachments' => 'array'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(StaffFeedComment::class, 'post_id');
    }
}
```

### File: `app/Models/StaffTask.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffTask extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'title', 'description', 'assigned_to', 'assigned_by',
        'priority', 'status', 'due_date', 'progress_percent', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
```

### File: `app/Models/Stream.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class, 'stream_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'stream_id');
    }
}
```

### File: `app/Models/Student.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use App\Models\Concerns\HasCustomFields;
use App\Models\Concerns\Versionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use Auditable, BelongsToSchool, HasCustomFields, HasFactory, Versionable;

    protected $fillable = [
        'student_number',
        'roll_number',         // Added for PreSkool UI parity
        'first_name',
        'last_name',
        'full_name',
        'date_of_birth',
        'gender',
        'blood_group',         // Added for PreSkool UI parity
        'national_id',
        'passport_number',     // Added for PreSkool UI parity
        'phone',
        'email',
        'photo_url',
        'address',
        'suburb',
        'social_links',        // Added for PreSkool UI parity (JSON)
        'class',
        'school',
        'status',
        'status_reason',
        'status_changed_at',
        'exited_at',
        'repetition_count',
        'previous_school',
        'balance',
        'currency',
        'user_id',
        'class_id',
        'stream_id',
        'house_id',
        'school_id',
        'student_category_id',
        'grade_level_id',
        'guardian_first_name',
        'guardian_last_name',
        'guardian_phone',
        'guardian_email',
        'guardian_relationship',
    ];

    protected $appends = ['guardian'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'exited_at' => 'date',
            'status_changed_at' => 'datetime',
            'balance' => 'decimal:2',
            'social_links' => 'array', // Automatically cast JSON to array
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentCategory(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function house(): BelongsTo
    {
        return $this->belongsTo(House::class);
    }

    public function statusEvents(): HasMany
    {
        return $this->hasMany(StudentStatusEvent::class);
    }

    public function medicalProfile(): HasOne
    {
        return $this->hasOne(StudentMedicalProfile::class);
    }

    /**
     * Get parents associated with this student
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship', 'is_primary', 'school_id')
            ->withTimestamps();
    }

    /**
     * Get enrollment records for this student
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get active enrollment record for current academic year
     */
    public function activeEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->where('status', 'active')->latestOfMany();
    }

    /**
     * Get classes this student is enrolled in (many-to-many via enrollments)
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassModel::class, 'enrollments', 'student_id', 'class_id')
            ->withPivot('academic_year', 'enrolled_at', 'left_at', 'status')
            ->withTimestamps();
    }

    /**
     * Guardian object for API responses (frontend expects nested guardian).
     * Safe when guardian_* columns do not exist yet (before migration).
     */
    public function getGuardianAttribute(): array
    {
        if ($this->relationLoaded('guardians') && $this->guardians->isNotEmpty()) {
            $guardian = $this->guardians->first(fn ($g) => (bool) ($g->pivot->is_primary ?? false))
                ?? $this->guardians->first();

            return [
                'id' => $guardian->id,
                'first_name' => $guardian->first_name,
                'last_name' => $guardian->last_name,
                'firstName' => $guardian->first_name,
                'surname' => $guardian->last_name,
                'full_name' => $guardian->full_name,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
                'relationship' => $guardian->pivot->relationship ?? $guardian->relationship ?? 'parent',
            ];
        }

        $fn = array_key_exists('guardian_first_name', $this->attributes) ? ($this->guardian_first_name ?? '') : '';
        $sn = array_key_exists('guardian_last_name', $this->attributes) ? ($this->guardian_last_name ?? '') : '';
        $phone = array_key_exists('guardian_phone', $this->attributes) ? ($this->guardian_phone ?? '') : '';
        $email = array_key_exists('guardian_email', $this->attributes) ? ($this->guardian_email ?? '') : '';
        $rel = array_key_exists('guardian_relationship', $this->attributes) ? ($this->guardian_relationship ?? 'parent') : 'parent';

        return [
            'first_name' => $fn,
            'last_name' => $sn,
            'firstName' => $fn,
            'surname' => $sn,
            'full_name' => trim("{$fn} {$sn}"),
            'phone' => $phone,
            'email' => $email,
            'relationship' => $rel,
        ];
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Get guardians associated with this student
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student')
            ->withPivot('relationship', 'is_primary', 'can_pickup', 'emergency_contact')
            ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    protected function getCustomFieldEntityType(): string
    {
        return CustomField::ENTITY_STUDENT;
    }
}
```

### File: `app/Models/StudentCategory.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCategory extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function feeDiscounts(): HasMany
    {
        return $this->hasMany(FeeDiscount::class);
    }
}
```

### File: `app/Models/StudentDocument.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'uploaded_by',
        'name',
        'type',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
```

### File: `app/Models/StudentIntervention.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIntervention extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'intervention_type', 'status',
        'summary', 'action_plan', 'assigned_to', 'created_by',
        'start_date', 'follow_up_date', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'follow_up_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/StudentMedicalProfile.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMedicalProfile extends Model
{
    protected $guarded = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/StudentStatusEvent.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentStatusEvent extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'student_id',
        'from_status',
        'to_status',
        'action',
        'reason',
        'effective_date',
        'performed_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
```

### File: `app/Models/StudentTimelineEvent.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentTimelineEvent extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'event_type', 'title',
        'description', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }
}
```

### File: `app/Models/StudentTransportAllocation.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransportAllocation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['allocated_at' => 'date'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/Subject.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
        'order',
        'class_id',
        'teacher_id'
    ];

    protected function casts(): array
    {
        return [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function timetables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Timetable::class);
    }
}
```

### File: `app/Models/SubjectPrerequisite.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SubjectPrerequisite extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'subject_id', 'prerequisite_subject_id', 'minimum_grade',
    ];

    protected function casts(): array
    {
        return ['minimum_grade' => 'decimal:2'];
    }
}
```

### File: `app/Models/SyllabusTopic.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusTopic extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'subject_id', 'class_id', 'title', 'chapter',
        'sort_order', 'objectives', 'status', 'coverage_percent', 'completed_on',
    ];

    protected function casts(): array
    {
        return ['completed_on' => 'date'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
```

### File: `app/Models/Teacher.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Models\Concerns\HasCustomFields;
use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use BelongsToSchool, HasCustomFields, HasFactory;

    protected $fillable = [
        'employee_id',
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        'gender',                  // PreSkool UI parity
        'blood_group',             // PreSkool UI parity
        'national_id',             // PreSkool UI parity
        'passport_number',         // PreSkool UI parity
        'address',
        'social_links',            // PreSkool UI parity (JSON)
        'emergency_contact_name',  // PreSkool UI parity
        'emergency_contact_phone', // PreSkool UI parity
        'subject',
        'department',
        'designation_id',
        'qualification',
        'joining_date',
        'status',
        'user_id',
        'school_id',
        'base_salary',
        'salary_currency',
        'allowances',
        'deductions',
        'bank_name',
        'bank_account_number',
        'payment_method',
        'employment_type',
        'period_rate',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'base_salary' => 'decimal:2',
            'period_rate' => 'decimal:2',
            'allowances' => 'array',
            'deductions' => 'array',
            'social_links' => 'array', // Automatically casts JSON to array for UI
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassModel::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function payroll(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    protected function getCustomFieldEntityType(): string
    {
        return CustomField::ENTITY_TEACHER;
    }
}
```

### File: `app/Models/TeacherAssignment.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAssignment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'grade_level_id',
        'class_id',
        'subject_id',
        'role',
        'assigned_at',
        'assigned_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'assigned_at' => 'date',
        'assigned_until' => 'date',
        'is_active' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/TeacherNotification.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherNotification extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'user_id', 'type', 'title', 'body', 'link', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### File: `app/Models/TeachingResource.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingResource extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'subject_id', 'class_id', 'title', 'resource_type',
        'topic', 'term', 'file_url', 'description', 'shared',
    ];

    protected function casts(): array
    {
        return ['shared' => 'boolean'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
```

### File: `app/Models/Term.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'academic_year',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
        'order',
        'description',
    ];

    protected function casts(): array
    {
        return [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'term', 'name');
    }

    /**
     * Scope to get current term for a school
     */
    public function scopeCurrent($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId)
            ->where('is_current', true)
            ->where('is_active', true);
    }

    /**
     * Scope to get terms for an academic year
     */
    public function scopeForAcademicYear($query, int $schoolId, string $academicYear)
    {
        return $query->where('school_id', $schoolId)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->orderBy('order');
    }
}
```

### File: `app/Models/TerminologyMapping.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminologyMapping extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'system_key',
        'custom_label',
        'locale',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
```

### File: `app/Models/Test.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'term_id',
        'name',
        'description',
        'test_date',
        'start_time',
        'end_time',
        'total_marks',
        'passing_marks',
        'academic_year',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
        'test_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
        'is_published' => 'boolean',
    ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }

    /**
     * Get all students in the class for this test
     */
    public function getStudentsAttribute()
    {
        return Student::where('school_id', $this->school_id)
            ->where('class_id', $this->class_id)
            ->where('status', 'active')
            ->get();
    }
}
```

### File: `app/Models/TestResult.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'school_id',
        'test_id',
        'student_id',
        'subject_id',
        'marks_obtained',
        'total_marks',
        'percentage',
        'grade',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
        'marks_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];
    }

    protected static function booted()
    {
        static::creating(function ($testResult) {
            // Calculate percentage
            if ($testResult->total_marks > 0) {
                $testResult->percentage = ($testResult->marks_obtained / $testResult->total_marks) * 100;
            }

            // Get grade from grading scale
            if ($testResult->percentage !== null) {
                $grade = \App\Models\GradingScale::getGradeForScore($testResult->school_id, $testResult->percentage);
                $testResult->grade = $grade;
            }
        });

        static::updating(function ($testResult) {
            // Recalculate percentage if marks changed
            if ($testResult->isDirty(['marks_obtained', 'total_marks']) && $testResult->total_marks > 0) {
                $testResult->percentage = ($testResult->marks_obtained / $testResult->total_marks) * 100;
            }

            // Recalculate grade if percentage changed
            if ($testResult->isDirty('percentage') && $testResult->percentage !== null) {
                $grade = \App\Models\GradingScale::getGradeForScore($testResult->school_id, $testResult->percentage);
                $testResult->grade = $grade;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
```

### File: `app/Models/Timetable.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timetable extends Model
{
    use BelongsToSchool, HasFactory;

    protected $table = 'timetable';

    protected $fillable = [
        'class_id',
        'teacher_id',
        'subject_id',
        'subject',
        'day',
        'start_time',
        'end_time',
        'room',
        'school_id',
        'room_id',
        'lesson_type',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
```

### File: `app/Models/TimetableChangeRequest.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableChangeRequest extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'teacher_id', 'request_type', 'details', 'preferred_slot',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
```

### File: `app/Models/Transaction.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = [
        'student_id',
        'payroll_id',
        'payment_id',
        'invoice_id',
        'income_head_id',
        'expense_head_id',
        'type',
        'category',
        'description',
        'reference',
        'debit',
        'credit',
        'balance',
        'currency',
        'status',
        'payment_method',
        'invoice_number',
        'created_by',
        'notes',
        'school_id',
    ];

    protected function casts(): array
    {
        return [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
    ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function incomeHead(): BelongsTo
    {
        return $this->belongsTo(IncomeHead::class);
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
```

### File: `app/Models/TransportRoute.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportRoute extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
```

### File: `app/Models/User.php`
```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Ai\Concerns\HasConversations;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasConversations, HasFactory, Notifiable, Auditable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permission_ids',
        'status',
        'platform_terms_version',
        'platform_terms_accepted_at',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'first_name',
        'last_name',
        'school_id',
        'avatar_url',
    ];

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SchoolUserMembership::class);
    }

    public function schools(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user_memberships')
            ->withPivot('role', 'is_default')
            ->withTimestamps();
    }

    public function teacher(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function reportTemplatesCreated(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReportTemplate::class, 'created_by');
    }

    /**
     * Get students associated with this user (when user is a parent)
     */
    public function students(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relationship', 'is_primary', 'school_id')
            ->withTimestamps();
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->students();
    }

    public function leaveRequestsReviewed(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'reviewed_by');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'two_factor_confirmed_at' => 'datetime',
            'platform_terms_accepted_at' => 'datetime',
            'role' => UserRole::class,
            'permission_ids' => 'array',
        ];
    }

    public function hasAcceptedCurrentPlatformTerms(): bool
    {
        $currentVersion = (string) config('platform_terms.version');

        return $this->platform_terms_accepted_at !== null
            && (string) $this->platform_terms_version === $currentVersion;
    }

    public function acceptCurrentPlatformTerms(): void
    {
        $this->forceFill([
            'platform_terms_version' => (string) config('platform_terms.version'),
            'platform_terms_accepted_at' => now(),
        ])->save();
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, ['inactive', 'suspended', 'disabled'], true);
    }

    public function roleValue(): string
    {
        return $this->role instanceof UserRole ? $this->role->apiValue() : (string) $this->role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function hasPermission(string $slug): bool
    {
        return app(\App\Services\PermissionService::class)->hasPermission($this, $slug);
    }

    public function hasCapability(string $capability): bool
    {
        return app(\App\Services\PermissionService::class)->hasCapability($this, $capability);
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        return app(\App\Services\PermissionService::class)->permissionSlugsForUser($this);
    }
}
```

### File: `app/Models/Vehicle.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/Vendor.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
```

### File: `app/Models/Visitor.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visitor extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
```

### File: `app/Models/WebhookDelivery.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use BelongsToSchool;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'school_id',
        'subscription_id',
        'event_type',
        'payload',
        'status',
        'attempts',
        'response_status',
        'response_body',
        'last_error',
        'next_retry_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
```

### File: `app/Models/WebhookSubscription.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'event_type',
        'target_url',
        'secret',
        'secret_hash',
        'is_active',
    ];

    protected $hidden = [
        'secret',
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    public function signingSecret(): ?string
    {
        return is_string($this->secret) && $this->secret !== '' ? $this->secret : null;
    }
}
```

### File: `app/Models/WorkflowApproval.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApproval extends Model
{
    public $timestamps = false;

    protected $fillable = ['instance_id', 'step_order', 'approver_id', 'action', 'comments', 'acted_at'];

    protected function casts(): array
    {
        return ['acted_at' => 'datetime'];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
```

### File: `app/Models/WorkflowDefinition.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'code', 'name', 'module', 'description', 'layout', 'is_active'];

    protected function casts(): array
    {
        return [
            'layout' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowDefinitionStep::class, 'definition_id')->orderBy('step_order');
    }
}
```

### File: `app/Models/WorkflowDefinitionStep.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowDefinitionStep extends Model
{
    protected $fillable = [
        'definition_id', 'step_order', 'name', 'approver_role',
        'approver_user_id', 'escalation_hours', 'position',
        'approval_mode', 'required_approvals',
    ];

    protected function casts(): array
    {
        return ['position' => 'array'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }
}
```

### File: `app/Models/WorkflowDelegation.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class WorkflowDelegation extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'delegator_id', 'delegate_id', 'starts_on', 'ends_on', 'scope', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
```

### File: `app/Models/WorkflowInstance.php`
```php
<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'definition_id', 'subject_type', 'subject_id', 'status',
        'current_step_order', 'initiated_by', 'metadata', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'completed_at' => 'datetime'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class, 'instance_id');
    }
}
```

## 5. API V1 Controllers
### File: `app/Http/Controllers/Api/V1/AdminLicenseController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LicenseKey;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminLicenseController extends Controller
{
    public function __construct(private LicenseService $licenses) {}

    public function index(Request $request)
    {
        $filters = Validator::make($request->all(), [
            'status' => 'nullable|string|in:unused,active,expired,revoked',
            'school_id' => 'nullable|integer|exists:schools,id',
        ])->validate();

        return $this->success([
            'summary' => $this->licenses->platformSummary(),
            'keys' => $this->licenses->listKeys($filters)->map(fn (LicenseKey $key) => [
                'id' => $key->id,
                'key_prefix' => $key->key_prefix,
                'plan_type' => $key->plan_type->value,
                'duration_months' => $key->duration_months,
                'amount' => $key->amount !== null ? (float) $key->amount : null,
                'currency' => $key->currency,
                'status' => $key->status->value,
                'school' => $key->school ? [
                    'id' => $key->school->id,
                    'name' => $key->school->name,
                    'code' => $key->school->code,
                ] : null,
                'customer_name' => $key->customer_name,
                'customer_email' => $key->customer_email,
                'activated_at' => $key->activated_at?->toIso8601String(),
                'expires_at' => $key->expires_at?->toIso8601String(),
                'created_at' => $key->created_at?->toIso8601String(),
                'created_by' => $key->creator ? [
                    'id' => $key->creator->id,
                    'name' => $key->creator->name,
                    'email' => $key->creator->email,
                ] : null,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'plan_type' => 'required|string|in:lifetime,monthly,quarterly,annual,custom',
            'duration_months' => 'nullable|integer|min:1|max:120',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ])->validate();

        $result = $this->licenses->generate($request->user(), $data);

        return $this->success([
            'license_key' => $result['license_key'],
            'record' => [
                'id' => $result['record']->id,
                'key_prefix' => $result['record']->key_prefix,
                'plan_type' => $result['record']->plan_type->value,
                'duration_months' => $result['record']->duration_months,
                'status' => $result['record']->status->value,
                'customer_name' => $result['record']->customer_name,
                'customer_email' => $result['record']->customer_email,
            ],
        ], 'License key generated. Store it securely — it cannot be retrieved again.', 201);
    }

    public function revoke(Request $request, int $id)
    {
        $key = LicenseKey::findOrFail($id);
        $key = $this->licenses->revoke($key);

        return $this->success([
            'id' => $key->id,
            'status' => $key->status->value,
        ], 'License key revoked.');
    }
}
```

### File: `app/Http/Controllers/Api/V1/AdminSchoolController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\School\ProvisionSchoolRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Models\School;
use App\Models\SchoolBackup;
use App\Models\SchoolDomain;
use App\Services\LicenseService;
use App\Services\SchoolProvisioningService;
use App\Services\Tenancy\SchoolBackupService;
use App\Services\Tenancy\SchoolDomainVerificationService;
use App\Services\Tenancy\SchoolUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminSchoolController extends Controller
{
    public function __construct(
        private LicenseService $licenses,
        private SchoolProvisioningService $provisioning,
        private SchoolBackupService $backups,
        private SchoolDomainVerificationService $domainVerification,
        private SchoolUsageService $usage,
    ) {}

    public function index(Request $request)
    {
        $filters = Validator::make($request->all(), [
            'license_status' => 'nullable|string|in:licensed,unlicensed,active,grace,expired,none',
            'search' => 'nullable|string|max:100',
        ])->validate();

        return $this->success([
            'schools' => $this->licenses->listSchools($filters),
            'summary' => $this->licenses->platformSummary(),
        ]);
    }

    /**
     * Super admin registers a school with profile + school admin account.
     */
    public function store(ProvisionSchoolRequest $request)
    {
        $result = $this->provisioning->provision($request->validated(), $request->user());

        return $this->success([
            'school' => (new SchoolResource($result['school']))->resolve(),
            'admin' => [
                'id' => $result['admin']->id,
                'name' => $result['admin']->name,
                'email' => $result['admin']->email,
                'role' => $result['admin']->roleValue(),
            ],
            'license_key' => $result['license_key'],
            'license_status' => $result['license_status'],
            'login_hint' => 'The school admin can sign in with the email and password you set.',
        ], 'School registered. The school admin can now sign in and manage the school.', 201);
    }

    public function show(School $school)
    {
        return $this->success([
            'school' => (new SchoolResource($school))->resolve(),
            'domains' => $school->domains()->orderByDesc('is_primary')->orderBy('domain')->get()->map(
                fn (SchoolDomain $domain) => $this->mapDomain($domain)
            )->values(),
            'usage' => $this->usage->snapshot($school),
        ]);
    }

    public function usage(School $school)
    {
        return $this->success([
            'usage' => $this->usage->snapshot($school),
        ]);
    }

    public function updateStatus(Request $request, School $school)
    {
        $data = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ])->validate();

        $school->update(['status' => $data['status']]);

        return $this->success([
            'school' => (new SchoolResource($school->fresh()))->resolve(),
        ], $data['status'] === 'active' ? 'School activated.' : 'School suspended.');
    }

    public function destroy(Request $request, School $school)
    {
        $data = Validator::make($request->all(), [
            'backup' => ['sometimes', 'boolean'],
        ])->validate();

        $backup = null;
        if (($data['backup'] ?? true) === true) {
            $backup = $this->backups->create($school, $request->user());
        }

        $school->update([
            'status' => 'deleted',
            'license_status' => 'expired',
        ]);
        $school->domains()->update(['status' => 'inactive']);

        return $this->success([
            'school' => (new SchoolResource($school->fresh()))->resolve(),
            'backup' => $backup ? $this->backups->mapBackup($backup) : null,
        ], 'School deprovisioned.');
    }

    public function listBackups(School $school)
    {
        return $this->success([
            'backups' => $this->backups->listForSchool($school),
        ]);
    }

    public function createBackup(Request $request, School $school)
    {
        $backup = $this->backups->create($school, $request->user());

        return $this->success([
            'backup' => $this->backups->mapBackup($backup),
        ], 'School backup created.', 201);
    }

    public function restoreBackup(School $school, SchoolBackup $backup)
    {
        abort_unless($backup->school_id === $school->id, 404);

        $restored = $this->backups->restore($backup);

        return $this->success([
            'backup' => $this->backups->mapBackup($restored),
        ], 'School backup restored.');
    }

    public function domains(School $school)
    {
        return $this->success([
            'domains' => $school->domains()->orderByDesc('is_primary')->orderBy('domain')->get()->map(
                fn (SchoolDomain $domain) => $this->mapDomain($domain)
            )->values(),
        ]);
    }

    public function storeDomain(Request $request, School $school)
    {
        $data = Validator::make($request->all(), [
            'domain' => ['required', 'string', 'max:255', 'unique:school_domains,domain'],
            'is_primary' => ['sometimes', 'boolean'],
        ])->validate();

        if (($data['is_primary'] ?? false) === true) {
            $school->domains()->update(['is_primary' => false]);
        }

        $domain = $school->domains()->create([
            'domain' => strtolower(trim($data['domain'])),
            'verification_token' => $this->domainVerification->generateToken(),
            'is_primary' => (bool) ($data['is_primary'] ?? false),
            'is_verified' => false,
            'status' => 'active',
        ]);

        return $this->success([
            'domain' => $this->mapDomain($domain),
        ], 'School domain added.', 201);
    }

    public function updateDomain(Request $request, School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);

        $data = Validator::make($request->all(), [
            'domain' => ['sometimes', 'string', 'max:255', Rule::unique('school_domains', 'domain')->ignore($domain->id)],
            'is_primary' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ])->validate();

        if (($data['is_primary'] ?? false) === true) {
            $school->domains()->where('id', '!=', $domain->id)->update(['is_primary' => false]);
        }

        if (isset($data['domain'])) {
            $data['domain'] = strtolower(trim($data['domain']));
            if ($data['domain'] !== $domain->domain) {
                $data['is_verified'] = false;
                $data['verified_at'] = null;
                $data['verification_token'] = $this->domainVerification->generateToken();
            }
        }

        $domain->update($data);

        return $this->success([
            'domain' => $this->mapDomain($domain->fresh()),
        ], 'School domain updated.');
    }

    public function checkDomainVerification(School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);

        return $this->success([
            'domain' => $this->mapDomain($domain),
            'dns_verified' => $this->domainVerification->verifyDns($domain),
        ]);
    }

    public function verifyDomain(Request $request, School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);

        $force = $request->boolean('force');
        if (! $force && ! $this->domainVerification->verifyDns($domain)) {
            return response()->json([
                'message' => 'DNS verification failed. Add the TXT record shown in verification instructions, or pass force=1 to verify manually.',
                'code' => 'domain_verification_failed',
                'data' => [
                    'domain' => $this->mapDomain($domain),
                    'verification' => $this->domainVerification->instructions($domain),
                ],
            ], 422);
        }

        $domain->update([
            'is_verified' => true,
            'verified_at' => now(),
            'status' => 'active',
        ]);

        return $this->success([
            'domain' => $this->mapDomain($domain->fresh()),
        ], 'School domain verified.');
    }

    public function destroyDomain(School $school, SchoolDomain $domain)
    {
        $this->assertDomainBelongsToSchool($school, $domain);
        $domain->delete();

        return $this->success(message: 'School domain deleted.');
    }

    private function assertDomainBelongsToSchool(School $school, SchoolDomain $domain): void
    {
        abort_unless($domain->school_id === $school->id, 404);
    }

    private function mapDomain(SchoolDomain $domain): array
    {
        if (! $domain->verification_token) {
            $domain->update([
                'verification_token' => $this->domainVerification->generateToken(),
            ]);
            $domain->refresh();
        }

        return [
            'id' => $domain->id,
            'school_id' => $domain->school_id,
            'domain' => $domain->domain,
            'is_primary' => (bool) $domain->is_primary,
            'is_verified' => (bool) $domain->is_verified,
            'status' => $domain->status,
            'verified_at' => $domain->verified_at?->toIso8601String(),
            'verification' => $this->domainVerification->instructions($domain),
            'created_at' => $domain->created_at?->toIso8601String(),
            'updated_at' => $domain->updated_at?->toIso8601String(),
        ];
    }
}
```

### File: `app/Http/Controllers/Api/V1/AssistantController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Assistant\ChatRequest;
use App\Models\User;
use App\Services\Assistant\SchoolAssistantService;
use Illuminate\Http\Request;
use Laravel\Ai\Models\Conversation;

class AssistantController extends Controller
{
    public function __construct(private SchoolAssistantService $assistant) {}

    public function status()
    {
        return $this->success($this->assistant->status());
    }

    public function chat(ChatRequest $request)
    {
        $user = $request->user();

        if (! $user->school_id) {
            return $this->forbidden('School context is required to use the assistant. Sign in with a school staff account.');
        }

        $result = $this->assistant->chat(
            $user,
            $request->string('message')->toString(),
            $request->filled('conversation_id')
                ? $request->string('conversation_id')->toString()
                : null,
        );

        return $this->success($result);
    }

    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->latest('updated_at')
            ->limit($request->integer('limit', 20))
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return $this->success($conversations);
    }

    public function showConversation(Request $request, string $conversationId)
    {
        $user = $request->user();

        $this->authorizeConversation($user, $conversationId);

        $conversation = Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->with(['messages' => fn ($query) => $query->orderBy('created_at')])
            ->firstOrFail();

        return $this->success([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $conversation->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => (string) $message->role,
                'content' => (string) $message->content,
                'created_at' => $message->created_at,
            ]),
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ]);
    }

    private function authorizeConversation(User $user, string $conversationId): void
    {
        $exists = Conversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            abort(404, 'Conversation not found.');
        }
    }
}
```

### File: `app/Http/Controllers/Api/V1/AuthController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\Auth\SecureAuthenticationService;
use App\Services\Tenancy\TenantSwitchService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private SecureAuthenticationService $authService,
        private TenantSwitchService $tenantSwitch,
    ) {}

    public function login(LoginRequest $request)
    {
        $user = $this->authService->authenticate(
            $request,
            $request->email,
            $request->password,
            $request->role
        );

        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $challengeToken = app(TwoFactorAuthController::class)->issueChallengeToken($user);

            return $this->success([
                'two_factor_required' => true,
                'challenge_token' => $challengeToken,
            ], 'Two-factor authentication required');
        }

        $token = $this->authService->createApiToken($user);
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new UserResource($user))->resolve(),
                $context,
                ['schools' => $this->tenantSwitch->availableSchools($user)->all()],
            ),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $this->authService->revokeCurrentToken($request->user());

        return $this->success(message: 'Logged out successfully');
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $this->tenantSwitch->ensureMembership($user);
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new UserResource($user))->resolve(),
                $context,
                ['schools' => $this->tenantSwitch->availableSchools($user)->all()],
            ),
        ]);
    }

    public function schools(Request $request)
    {
        $user = $request->user();
        $this->tenantSwitch->ensureMembership($user);

        return $this->success([
            'schools' => $this->tenantSwitch->availableSchools($user)->all(),
        ]);
    }

    public function switchSchool(Request $request)
    {
        $data = $request->validate([
            'school_id' => ['required', 'integer'],
        ]);

        $user = $this->tenantSwitch->switchTo($request->user(), (int) $data['school_id']);
        $token = $this->authService->createApiToken($user);
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new UserResource($user))->resolve(),
                $context,
                ['schools' => $this->tenantSwitch->availableSchools($user)->all()],
            ),
            'token' => $token,
        ], 'School context switched');
    }

    public function platformTerms()
    {
        return $this->success([
            'version' => (string) config('platform_terms.version'),
            'title' => (string) config('platform_terms.title'),
            'summary' => (string) config('platform_terms.summary'),
            'content' => (string) config('platform_terms.content'),
        ]);
    }

    public function privacyPolicy()
    {
        return $this->success([
            'version' => (string) config('platform_privacy.version'),
            'title' => (string) config('platform_privacy.title'),
            'summary' => (string) config('platform_privacy.summary'),
            'content' => (string) config('platform_privacy.content'),
        ]);
    }

    public function acceptPlatformTerms(Request $request)
    {
        $data = $request->validate([
            'accepted' => ['required', 'accepted'],
            'version' => ['required', 'string'],
        ]);

        $currentVersion = (string) config('platform_terms.version');
        if ($data['version'] !== $currentVersion) {
            throw ValidationException::withMessages([
                'version' => ['These terms have been updated. Refresh and accept the latest version.'],
            ]);
        }

        $user = $request->user();
        $user->acceptCurrentPlatformTerms();
        $fresh = $user->fresh();
        $context = $this->authService->resolveRoleContext($fresh);

        return $this->success([
            'user' => array_merge(
                (new UserResource($fresh))->resolve(),
                $context,
                ['schools' => $this->tenantSwitch->availableSchools($fresh)->all()],
            ),
        ], 'Platform terms accepted');
    }

    public function changePassword(ChangePasswordRequest $request, UpdateUserPassword $updater)
    {
        $updater->update($request->user(), $request->only([
            'current_password',
            'password',
            'password_confirmation',
        ]));

        return $this->success(message: 'Password changed successfully. Please log in again.');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::broker(config('fortify.passwords'))->sendResetLink(
            $request->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(message: __($status));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::broker(config('fortify.passwords'))->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $this->success(message: __($status));
    }
}
```

### File: `app/Http/Controllers/Api/V1/Controller.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use ApiResponse, AuthorizesRequests;
}
```

### File: `app/Http/Controllers/Api/V1/LicenseController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $licenses) {}

    public function status(Request $request)
    {
        $school = $request->user()->school;

        if (! $school) {
            return $this->error('Your account is not linked to a school.', 403);
        }

        return $this->success($this->licenses->status($school));
    }

    public function activate(Request $request)
    {
        $school = $request->user()->school;

        if (! $school) {
            return $this->error('Your account is not linked to a school.', 403);
        }

        $data = Validator::make($request->all(), [
            'license_key' => ['required', 'string', 'min:10'],
        ])->validate();

        $key = $this->licenses->activate($school, $data['license_key']);

        return $this->success([
            'license' => $this->licenses->status($school->fresh()),
            'activated_key' => [
                'id' => $key->id,
                'plan_type' => $key->plan_type->value,
                'expires_at' => $key->expires_at?->toIso8601String(),
            ],
        ], 'License activated successfully.');
    }
}
```

### File: `app/Http/Controllers/Api/V1/PaymentWebhookController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewayTransaction;
use App\Services\Platform\PaymentGatewayService;
use App\Services\Platform\PaynowGatewayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private PaymentGatewayService $gateway,
        private PaynowGatewayClient $paynow,
    ) {}

    public function handle(Request $request, string $provider)
    {
        $data = Validator::make($request->all(), [
            'reference' => 'required_without:ref|string',
            'ref' => 'required_without:reference|string',
            'status' => 'nullable|string',
            'provider_reference' => 'nullable|string',
            'hash' => 'nullable|string',
            'paynowreference' => 'nullable|string',
        ])->validate();

        $internalReference = $data['reference'] ?? $data['ref'];
        $status = strtolower((string) ($data['status'] ?? 'paid'));

        $txn = PaymentGatewayTransaction::where('internal_reference', $internalReference)->firstOrFail();
        $config = $txn->config;
        $credentials = $config->credentials ?? [];

        if ($config && ! $this->verifySignature($request, $provider, $credentials)) {
            return response()->json(['message' => 'Invalid webhook signature'], 403);
        }

        if (! $this->isSuccessfulStatus($provider, $status)) {
            return response()->json([
                'message' => 'Webhook ignored — payment not successful.',
                'status' => $status,
            ]);
        }

        $completed = $this->gateway->completeWebhook($internalReference, [
            'provider' => $provider,
            'reference' => $data['provider_reference']
                ?? $data['paynowreference']
                ?? $request->input('paynowreference')
                ?? $internalReference,
            'status' => $status,
            'raw' => $request->except(['hash', 'integration_key', 'key']),
        ]);

        return response()->json([
            'message' => 'Payment completed',
            'data' => [
                'internal_reference' => $completed->internal_reference,
                'status' => $completed->status,
                'invoice_id' => $completed->invoice_id,
            ],
        ]);
    }

    /**
     * Sandbox browser checkout — completes the payment then redirects (or returns JSON).
     */
    public function sandboxCheckout(Request $request, string $provider)
    {
        $ref = (string) $request->query('ref', '');
        if ($ref === '') {
            abort(404);
        }

        $completed = $this->gateway->completeWebhook($ref, [
            'provider' => $provider,
            'reference' => 'SANDBOX-'.$ref,
            'status' => 'paid',
            'mode' => 'sandbox',
        ]);

        $returnUrl = $request->query('return_url');
        if (is_string($returnUrl) && $returnUrl !== '') {
            $separator = str_contains($returnUrl, '?') ? '&' : '?';

            return redirect()->away($returnUrl.$separator.'payment_ref='.$completed->internal_reference.'&payment_status=completed');
        }

        return response()->json([
            'message' => 'Sandbox payment completed',
            'data' => [
                'internal_reference' => $completed->internal_reference,
                'status' => $completed->status,
            ],
        ]);
    }

    public function status(string $reference)
    {
        $txn = PaymentGatewayTransaction::where('internal_reference', $reference)->firstOrFail();
        $txn = $this->gateway->refreshStatus($txn);

        return response()->json([
            'data' => [
                'internal_reference' => $txn->internal_reference,
                'status' => $txn->status,
                'amount' => $txn->amount,
                'currency' => $txn->currency,
                'invoice_id' => $txn->invoice_id,
                'completed_at' => $txn->completed_at?->toIso8601String(),
                'instructions' => $txn->provider_response['instructions'] ?? null,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function verifySignature(Request $request, string $provider, array $credentials): bool
    {
        if (strtolower($provider) === 'paynow') {
            $integrationKey = $credentials['integration_key'] ?? $credentials['key'] ?? null;
            if (! $integrationKey) {
                return true;
            }

            $payload = [];
            foreach ($request->all() as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $payload[strtolower((string) $key)] = (string) $value;
                }
            }

            return $this->paynow->verifyHash($payload, (string) $integrationKey);
        }

        $expected = $credentials['webhook_secret'] ?? $credentials['integration_key'] ?? null;
        if (! $expected) {
            return true;
        }

        $provided = $request->header('X-Webhook-Secret')
            ?? $request->input('hash')
            ?? $request->input('signature');

        if (! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals((string) $expected, $provided);
    }

    protected function isSuccessfulStatus(string $provider, string $status): bool
    {
        if (strtolower($provider) === 'paynow') {
            return $this->paynow->isPaidStatus($status);
        }

        return in_array($status, ['paid', 'completed', 'success', 'ok'], true);
    }
}
```

### File: `app/Http/Controllers/Api/V1/PeopleImportController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Import\PeopleCsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeopleImportController extends Controller
{
    public function __construct(
        private PeopleCsvImportService $imports,
    ) {}

    public function template(Request $request, string $type): StreamedResponse
    {
        $this->authorizeImport($request, $type);

        $headers = $this->imports->templateHeaders($type);
        $filename = "{$type}-import-template.csv";

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request, string $type)
    {
        $this->authorizeImport($request, $type);

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'create_login_users' => ['sometimes', 'boolean'],
            'dry_run' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = (int) $request->user()->school_id;
        $file = $request->file('file');
        $createLogins = $request->boolean('create_login_users');
        $dryRun = $request->boolean('dry_run');

        try {
            $result = match ($type) {
                'students' => $this->imports->importStudents($file, $schoolId, $request->user()?->id, $dryRun),
                'teachers' => $this->imports->importTeachers($file, $schoolId, $createLogins, $dryRun),
                'employees' => $this->imports->importEmployees($file, $schoolId, $dryRun),
                'guardians' => $this->imports->importGuardians($file, $schoolId, $dryRun),
                default => null,
            };
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($result === null) {
            return response()->json(['message' => 'Unknown import type.'], 404);
        }

        $prefix = $dryRun ? 'Preview' : 'Import finished';

        return response()->json([
            'message' => sprintf(
                '%s: %d created, %d updated, %d failed.',
                $prefix,
                $result['created'],
                $result['updated'],
                $result['failed'],
            ),
            'data' => [
                ...$result,
                'dry_run' => $dryRun,
            ],
        ]);
    }

    private function authorizeImport(Request $request, string $type): void
    {
        $map = [
            'students' => [
                'capabilities' => ['canManageStudents'],
                'permissions' => ['students.manage', 'people.manage'],
            ],
            'teachers' => [
                'capabilities' => ['canManageTeachers'],
                'permissions' => ['teachers.manage', 'people.manage'],
            ],
            'employees' => [
                'capabilities' => ['canManageTeachers'],
                'permissions' => ['hr.manage', 'people.manage'],
            ],
            'guardians' => [
                'capabilities' => ['canManageTeachers', 'canManageStudents'],
                'permissions' => ['people.manage', 'students.manage'],
            ],
        ];

        if (! isset($map[$type])) {
            abort(404, 'Unknown import type.');
        }

        $this->authorizeModuleAccess(
            $request,
            capabilities: $map[$type]['capabilities'],
            permissionSlugs: $map[$type]['permissions'],
        );
    }
}
```

### File: `app/Http/Controllers/Api/V1/ProfileController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return $this->success(new ProfileResource($request->user()));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if ($request->has('firstName') || $request->has('surname')) {
            $firstName = $request->input('firstName', $user->first_name ?? explode(' ', $user->name)[0] ?? '');
            $surname = $request->input('surname', $user->last_name ?? '');
            $user->name = trim("{$firstName} {$surname}");
            $user->first_name = $firstName;
            $user->last_name = $surname;
        }

        $user->fill($request->only(['email', 'phone', 'address', 'gender']));

        if ($request->has('dateOfBirth')) {
            $user->date_of_birth = $request->dateOfBirth;
        }

        if ($request->has('avatarUrl')) {
            $user->avatar_url = $request->input('avatarUrl') ?: null;
        }

        $user->save();

        return $this->success(
            new ProfileResource($user->fresh()),
            'Profile updated successfully'
        );
    }
}
```

### File: `app/Http/Controllers/Api/V1/SchoolController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\School\RegisterSchoolRequest;
use App\Http\Requests\Api\V1\School\UpdateSchoolRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Models\School;
use App\Services\SchoolConfigurationService;
use App\Services\SchoolProvisioningService;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function __construct(
        private SchoolProvisioningService $provisioning,
        private SchoolConfigurationService $configuration,
    ) {}

    public function register(RegisterSchoolRequest $request)
    {
        $result = $this->provisioning->provision($request->validated());

        return response()->json([
            'message' => 'School registered successfully. You can now log in.',
            'school_id' => $result['school']->id,
            'data' => [
                'school_id' => $result['school']->id,
                'school' => (new SchoolResource($result['school']))->resolve(),
            ],
        ], 201);
    }

    public function show(Request $request)
    {
        $school = School::with(['gradeLevels', 'gradingScales', 'rooms'])
            ->findOrFail($request->user()->school_id);

        $this->authorize('view', $school);

        return $this->success(new SchoolResource($school));
    }

    public function update(UpdateSchoolRequest $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $this->authorize('update', $school);

        $data = $request->validated();
        $currency = $data['currency'] ?? null;
        unset($data['currency']);

        if ($data !== []) {
            $school->update($data);
        }

        if ($currency !== null && $currency !== '') {
            $this->configuration->setSchoolCurrency($school->fresh(), (string) $currency);
        }

        return $this->success(new SchoolResource($school->fresh()), 'School updated successfully');
    }
}
```

### File: `app/Http/Controllers/Api/V1/SettingsController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Settings\StoreCustomFieldRequest;
use App\Http\Requests\Api\V1\Settings\UpdateCustomFieldRequest;
use App\Http\Requests\Api\V1\Settings\UpdateSchoolSettingsRequest;
use App\Http\Requests\Api\V1\Settings\UpdateTerminologyRequest;
use App\Http\Resources\Api\V1\CustomFieldResource;
use App\Models\CustomField;
use App\Models\School;
use App\Services\CustomFieldService;
use App\Services\SchoolSettingsService;
use App\Services\TerminologyService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private SchoolSettingsService $settingsService,
        private TerminologyService $terminologyService,
        private CustomFieldService $customFieldService,
    ) {}

    public function schoolSettings(Request $request)
    {
        $school = School::findOrFail($request->user()->school_id);

        return $this->success($this->settingsService->getAll($school));
    }

    public function updateSchoolSettings(UpdateSchoolSettingsRequest $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $this->settingsService->bulkSet($school, $request->validated('settings'));

        return $this->success(
            $this->settingsService->getAll($school),
            'School settings updated successfully'
        );
    }

    public function publicConfig(Request $request)
    {
        $school = $request->attributes->get('currentSchool');

        if (! $school instanceof School && $request->user()?->school_id !== null) {
            $school = School::find($request->user()->school_id);
        }

        if (! $school instanceof School) {
            return $this->success([
                'settings' => [],
                'terminology' => [],
                'custom_fields' => [],
                'school' => null,
            ]);
        }

        $locale = $request->get('locale', 'en');

        return $this->success([
            'settings' => $this->settingsService->getAll($school, publicOnly: true),
            'terminology' => $this->terminologyService->getForSchool($school, $locale),
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'website' => $school->website,
                'logo_path' => $school->logo_path,
            ],
        ]);
    }

    public function terminology(Request $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $locale = $request->get('locale', 'en');

        return $this->success([
            'locale' => $locale,
            'mappings' => $this->terminologyService->getForSchool($school, $locale),
        ]);
    }

    public function updateTerminology(UpdateTerminologyRequest $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $locale = $request->get('locale', 'en');

        $this->terminologyService->bulkSet($school, $request->validated('mappings'), $locale);

        return $this->success([
            'locale' => $locale,
            'mappings' => $this->terminologyService->getForSchool($school, $locale),
        ], 'Terminology updated successfully');
    }

    public function customFields(Request $request)
    {
        $this->authorize('viewAny', CustomField::class);

        $school = School::findOrFail($request->user()->school_id);
        $entityType = $request->get('entity_type');

        // No entity_type → return all school fields (admin settings list).
        // With entity_type → scoped list for student/teacher forms.
        $fields = $this->customFieldService->listForEntity(
            $school,
            is_string($entityType) && $entityType !== '' ? $entityType : null,
        );

        return $this->success(CustomFieldResource::collection($fields));
    }

    public function storeCustomField(StoreCustomFieldRequest $request)
    {
        $school = School::findOrFail($request->user()->school_id);
        $field = $this->customFieldService->create($school, $request->validated());

        return $this->created(new CustomFieldResource($field), 'Custom field created successfully');
    }

    public function updateCustomField(UpdateCustomFieldRequest $request, CustomField $customField)
    {
        $field = $this->customFieldService->update($customField, $request->validated());

        return $this->success(new CustomFieldResource($field), 'Custom field updated successfully');
    }

    public function destroyCustomField(CustomField $customField)
    {
        $this->authorize('delete', $customField);
        $customField->delete();

        return $this->success(message: 'Custom field deleted successfully');
    }
}
```

### File: `app/Http/Controllers/Api/V1/StudentController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Requests\Api\V1\Student\StoreStudentRequest;
use App\Http\Requests\Api\V1\Student\UpdateStudentRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\CustomField;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\CustomFieldService;
use App\Services\GuardianService;
use App\Services\StudentAdmissionService;
use App\Services\StudentPlacementService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class StudentController extends Controller
{
    use HandlesResourceQueries;

    public function __construct(
        private CustomFieldService $customFieldService,
        private GuardianService $guardianService,
        private StudentAdmissionService $admissionService,
        private StudentPlacementService $placementService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);

        return $this->paginateResource($request, Student::class, StudentResource::class, [
            'filters' => [
                'status',
                'class_id',
                'grade_level_id',
                'gender',
                'blood_group',
                'class',
                AllowedFilter::callback('guardian_id', function ($query, $value) {
                    $query->whereHas('guardians', fn ($q) => $q->where('guardians.id', (int) $value));
                }),
                'search',
            ],
            'search_columns' => [
                'full_name',
                'first_name',
                'last_name',
                'student_number',
                'roll_number',
                'national_id',
                'passport_number',
                'email',
            ],
            'sorts' => ['full_name', 'created_at', 'student_number', 'roll_number', 'status'],
            'includes' => ['guardians', 'classModel', 'gradeLevel', 'studentCategory', 'stream', 'house', 'medicalProfile'],
            'fields' => [
                'students.id',
                'students.full_name',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.roll_number',
                'students.status',
                'students.gender',
                'students.blood_group',
                'students.national_id',
                'students.passport_number',
                'students.photo_url',
                'students.social_links',
                'students.class_id',
                'students.grade_level_id',
                'students.student_category_id',
                'students.email',
                'students.phone',
                'students.school_id',
                'students.created_at',
            ],
            'default_sort' => '-created_at',
            'with' => ['guardians', 'classModel', 'gradeLevel', 'studentCategory'],
        ]);
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);

        return $this->success(
            new StudentResource($student->load(['guardians', 'classModel', 'stream', 'house', 'studentCategory', 'medicalProfile', 'documents']))
        );
    }

    public function store(StoreStudentRequest $request)
    {
        $guardian = $request->input('guardian', []);
        $schoolId = $request->user()->school_id;

        $student = Student::create([
            'first_name' => $request->firstName,
            'last_name' => $request->surname,
            'full_name' => trim($request->firstName.' '.$request->surname),
            'student_number' => $this->admissionService->generateStudentNumber((int) $schoolId),
            'roll_number' => $request->input('roll_number'),
            'class' => $request->class,
            'class_id' => $request->class_id,
            'stream_id' => $request->input('stream_id'),
            'house_id' => $request->input('house_id'),
            'grade_level_id' => $request->grade_level_id,
            'student_category_id' => $request->input('student_category_id'),
            'date_of_birth' => $request->dateOfBirth,
            'gender' => $request->gender,
            'blood_group' => $request->input('blood_group'),
            'national_id' => $request->input('national_id'),
            'passport_number' => $request->input('passport_number'),
            'phone' => $request->phone,
            'email' => $request->email,
            'photo_url' => $request->input('photo_url'),
            'address' => $request->address,
            'suburb' => $request->suburb,
            'social_links' => $request->input('social_links'),
            'school' => $request->school,
            'status' => 'active',
            'school_id' => $schoolId,
            'guardian_first_name' => $guardian['firstName'] ?? $guardian['first_name'] ?? null,
            'guardian_last_name' => $guardian['surname'] ?? $guardian['last_name'] ?? null,
            'guardian_phone' => $guardian['phone'] ?? null,
            'guardian_email' => $guardian['email'] ?? null,
            'guardian_relationship' => $guardian['relationship'] ?? null,
        ]);

        if ($request->filled('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $student,
                CustomField::ENTITY_STUDENT,
                $request->input('custom_fields', [])
            );
        }

        $this->syncStudentGuardian(
            $student,
            $guardian,
            $request->input('guardian_id'),
            $schoolId,
        );

        if ($request->filled('class_id')) {
            $academicYear = (string) ($request->input('academic_year')
                ?? $request->user()?->school?->academic_year
                ?? date('Y'));
            $this->placementService->place($student->fresh(), [
                'class_id' => (int) $request->class_id,
                'stream_id' => $request->input('stream_id'),
                'house_id' => $request->input('house_id'),
                'academic_year' => $academicYear,
                'reason' => 'admission',
                'apply_fees' => false,
            ], $request->user()?->id);
        }

        return $this->created(
            new StudentResource($student->fresh()->load(['guardians', 'classModel', 'stream', 'house', 'medicalProfile'])),
            'Student created successfully',
        );
    }

    public function update(UpdateStudentRequest $request, Student $student)
    {
        if ($request->has('firstName') || $request->has('surname')) {
            $student->first_name = $request->firstName ?? $student->first_name;
            $student->last_name = $request->surname ?? $student->last_name;
            $student->full_name = trim($student->first_name.' '.$student->last_name);
        }

        $student->fill($request->only([
            'class',
            'class_id',
            'stream_id',
            'house_id',
            'grade_level_id',
            'student_category_id',
            'roll_number',
            'blood_group',
            'national_id',
            'passport_number',
            'photo_url',
            'phone',
            'email',
            'address',
            'suburb',
            'social_links',
            'status',
        ]));

        if ($request->has('dateOfBirth')) {
            $student->date_of_birth = $request->dateOfBirth;
        }
        if ($request->has('gender')) {
            $student->gender = $request->gender;
        }

        $guardian = $request->input('guardian', []);
        if ($guardian !== []) {
            $student->guardian_first_name = $guardian['firstName'] ?? $guardian['first_name'] ?? $student->guardian_first_name;
            $student->guardian_last_name = $guardian['surname'] ?? $guardian['last_name'] ?? $student->guardian_last_name;
            $student->guardian_phone = $guardian['phone'] ?? $student->guardian_phone;
            $student->guardian_email = $guardian['email'] ?? $student->guardian_email;
            $student->guardian_relationship = $guardian['relationship'] ?? $student->guardian_relationship;
        }

        $student->save();

        if ($request->has('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $student,
                CustomField::ENTITY_STUDENT,
                $request->input('custom_fields', [])
            );
        }

        if ($request->has('guardian_id') || $guardian !== []) {
            $this->syncStudentGuardian(
                $student,
                $guardian,
                $request->input('guardian_id'),
                $request->user()->school_id,
            );
        }

        return $this->success(
            new StudentResource($student->fresh()->load(['guardians', 'classModel', 'stream', 'house', 'medicalProfile'])),
            'Student updated successfully',
        );
    }

    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);
        $student->delete();

        return $this->success(message: 'Student deleted successfully');
    }

    /**
     * Link an existing guardian or create/find one, then attach to the student pivot.
     */
    private function syncStudentGuardian(
        Student $student,
        array $guardianInput,
        mixed $guardianId,
        int $schoolId,
    ): void {
        if ($guardianId) {
            $guardian = Guardian::where('school_id', $schoolId)->findOrFail((int) $guardianId);
            $pivot = [
                'relationship' => $guardianInput['relationship'] ?? $guardian->relationship ?? 'parent',
                'is_primary' => true,
                'can_pickup' => true,
                'emergency_contact' => false,
            ];

            $guardian->students()->syncWithoutDetaching([$student->id => $pivot]);
            $this->guardianService->syncParentUserLink($guardian, $student->id, $pivot);
            $this->syncDenormalizedGuardianFields($student, $guardian, $pivot['relationship']);

            return;
        }

        $hasData = ! empty($guardianInput['firstName'])
            || ! empty($guardianInput['first_name'])
            || ! empty($guardianInput['phone']);

        if (! $hasData) {
            return;
        }

        $guardianData = [
            'first_name' => $guardianInput['firstName'] ?? $guardianInput['first_name'] ?? '',
            'last_name' => $guardianInput['surname'] ?? $guardianInput['last_name'] ?? '',
            'phone' => $guardianInput['phone'] ?? '',
            'email' => $guardianInput['email'] ?? null,
            'relationship' => $guardianInput['relationship'] ?? 'parent',
            'is_primary' => true,
        ];

        $guardian = $this->guardianService->createOrFindGuardian($guardianData, $schoolId, $student->id);
        $this->syncDenormalizedGuardianFields(
            $student,
            $guardian,
            $guardianData['relationship'] ?? 'parent',
        );
    }

    private function syncDenormalizedGuardianFields(Student $student, Guardian $guardian, ?string $relationship): void
    {
        $student->update([
            'guardian_first_name' => $guardian->first_name,
            'guardian_last_name' => $guardian->last_name,
            'guardian_phone' => $guardian->phone,
            'guardian_email' => $guardian->email,
            'guardian_relationship' => $relationship ?? $guardian->relationship ?? 'parent',
        ]);
    }
}
```

### File: `app/Http/Controllers/Api/V1/StudentPortalController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Grade;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\CbtExamSession;
use App\Models\QuestionBankItem;
use App\Models\Subject;
use App\Services\Enterprise\ExaminationEnterpriseService;
use App\Services\FileUploadService;
use App\Services\StudentResolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StudentPortalController extends Controller
{
    public function __construct(
        private StudentResolutionService $students,
        private FileUploadService $uploads,
        private ExaminationEnterpriseService $cbt,
    ) {}

    public function dashboard(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;
        $today = now()->toDateString();

        $recentAttendance = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get(['id', 'date', 'status', 'remarks']);

        $upcomingExams = Exam::query()
            ->where('school_id', $schoolId)
            ->whereDate('exam_date', '>=', $today)
            ->when($student->grade_level_id, fn ($q) => $q->where(function ($inner) use ($student) {
                $inner->whereNull('grade_level_id')->orWhere('grade_level_id', $student->grade_level_id);
            }))
            ->orderBy('exam_date')
            ->limit(8)
            ->get(['id', 'name', 'exam_date', 'is_published']);

        $openInvoices = Invoice::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderByDesc('due_date')
            ->limit(8)
            ->get(['id', 'invoice_number', 'amount', 'balance', 'status', 'due_date', 'currency']);

        $recentGrades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get(['id', 'subject', 'subject_id', 'assessment_type', 'score', 'total', 'grade', 'term', 'year', 'updated_at']);

        $openAssignments = $this->assignmentsForStudent($student, $schoolId)
            ->limit(8)
            ->get(['id', 'title', 'subject', 'due_date', 'total_marks', 'status']);

        $openAssignments = $this->attachSubmissions($openAssignments, $student->id);

        $announcements = Announcement::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('target_audience', 'all')
                    ->orWhere('target_audience', 'students');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'title', 'message', 'type', 'date', 'created_at']);

        $attendanceStats = $this->attendanceStats($student->id);

        return $this->success([
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'student_number' => $student->student_number,
                'class_id' => $student->class_id,
                'class_name' => $student->classModel?->name ?? $student->class,
                'status' => $student->status,
                'balance' => (float) $student->balance,
                'currency' => $student->currency,
            ],
            'attendance_recent' => $recentAttendance,
            'attendance_summary' => $attendanceStats,
            'upcoming_exams' => $upcomingExams,
            'open_invoices' => $openInvoices,
            'recent_grades' => $recentGrades,
            'assignments' => $openAssignments,
            'announcements' => $announcements,
            'stats' => [
                'balance' => (float) $student->balance,
                'open_invoices' => $openInvoices->count(),
                'upcoming_exams' => $upcomingExams->count(),
                'open_assignments' => count($openAssignments),
                'absent_last_30' => Attendance::query()
                    ->where('student_id', $student->id)
                    ->where('status', 'absent')
                    ->whereDate('date', '>=', now()->subDays(30))
                    ->count(),
                'attendance_rate' => $attendanceStats['attendance_rate'],
            ],
        ]);
    }

    public function me(Request $request)
    {
        $student = $this->requireStudent($request);
        $student->loadMissing(['classModel:id,name', 'gradeLevel:id,name']);

        return $this->success($student);
    }

    public function attendance(Request $request)
    {
        $student = $this->requireStudent($request);

        $rows = Attendance::query()
            ->where('student_id', $student->id)
            ->orderByDesc('date')
            ->limit(90)
            ->get(['id', 'date', 'status', 'remarks', 'time_in']);

        return $this->success([
            'summary' => $this->attendanceStats($student->id),
            'rows' => $rows,
        ]);
    }

    public function grades(Request $request)
    {
        $student = $this->requireStudent($request);

        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        $examResults = ExamResult::query()
            ->where('student_id', $student->id)
            ->with('exam:id,name,exam_date,status')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return $this->success([
            'grades' => $grades,
            'exam_results' => $examResults,
        ]);
    }

    public function fees(Request $request)
    {
        $student = $this->requireStudent($request);

        $invoices = Invoice::query()
            ->where('student_id', $student->id)
            ->orderByDesc('due_date')
            ->limit(50)
            ->get();

        return $this->success([
            'balance' => (float) $student->balance,
            'currency' => $student->currency,
            'invoices' => $invoices,
        ]);
    }

    public function timetable(Request $request)
    {
        $student = $this->requireStudent($request);

        if (! $student->class_id) {
            return $this->success([]);
        }

        $slots = Timetable::query()
            ->where('school_id', $request->user()->school_id)
            ->where('class_id', $student->class_id)
            ->with(['teacher:id,name,first_name,last_name', 'classModel:id,name'])
            ->orderBy('day')
            ->orderBy('start_time')
            ->limit(100)
            ->get();

        return $this->success($slots);
    }

    public function assignments(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $rows = $this->assignmentsForStudent($student, $schoolId)
            ->limit(100)
            ->get();

        return $this->success($this->attachSubmissions($rows, $student->id));
    }

    public function showAssignment(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $assignment = $this->assignmentsForStudent($student, $schoolId)
            ->findOrFail($id);

        $submission = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        return $this->success([
            'assignment' => $assignment,
            'submission' => $submission,
        ]);
    }

    public function submitAssignment(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $assignment = $this->assignmentsForStudent($student, $schoolId)
            ->findOrFail($id);

        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:20000'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,txt,jpg,jpeg,png,webp'],
        ]);

        if (empty($data['content']) && ! $request->hasFile('file')) {
            throw ValidationException::withMessages([
                'content' => ['Provide written work or attach a file before submitting.'],
            ]);
        }

        $existing = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing && in_array($existing->status, ['submitted', 'graded'], true)) {
            throw ValidationException::withMessages([
                'assignment' => ['This assignment has already been submitted.'],
            ]);
        }

        $fileUrl = $existing?->file_url;
        if ($request->hasFile('file')) {
            $upload = $this->uploads->store(
                $request->file('file'),
                'assignments/submissions',
                'public',
                $schoolId,
            );
            $fileUrl = $upload['url'];
        }

        $submission = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'school_id' => $schoolId,
                'content' => $data['content'] ?? $existing?->content,
                'file_url' => $fileUrl,
                'status' => 'submitted',
                'submitted_at' => now(),
                'score' => null,
                'teacher_comment' => null,
                'graded_at' => null,
                'returned_at' => null,
            ],
        );

        return $this->created([
            'assignment' => $assignment->fresh(),
            'submission' => $submission->fresh(),
        ], 'Assignment submitted');
    }

    public function announcements(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $rows = Announcement::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('target_audience', 'all')
                    ->orWhere('target_audience', 'students');
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'title', 'message', 'type', 'date', 'created_at']);

        return $this->success($rows);
    }

    protected function assignmentsForStudent(Student $student, int $schoolId)
    {
        return Assignment::query()
            ->where('school_id', $schoolId)
            ->when($student->class_id, fn ($q) => $q->where(function ($inner) use ($student) {
                $inner->whereNull('class_id')->orWhere('class_id', $student->class_id);
            }))
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhereNotIn('status', ['closed', 'archived', 'cancelled']);
            })
            ->orderBy('due_date')
            ->orderByDesc('id');
    }

    /**
     * @param  Collection<int, Assignment>  $assignments
     * @return list<array<string, mixed>>
     */
    protected function attachSubmissions($assignments, int $studentId): array
    {
        if ($assignments->isEmpty()) {
            return [];
        }

        $submissions = AssignmentSubmission::query()
            ->where('student_id', $studentId)
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->get()
            ->keyBy('assignment_id');

        return $assignments->map(function (Assignment $assignment) use ($submissions) {
            $submission = $submissions->get($assignment->id);

            return array_merge($assignment->toArray(), [
                'submission_status' => $submission?->status,
                'submission_score' => $submission?->score,
                'submitted_at' => $submission?->submitted_at?->toIso8601String(),
                'teacher_comment' => $submission?->teacher_comment,
                'submission_file_url' => $submission?->file_url,
            ]);
        })->values()->all();
    }

    public function cbtAvailable(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $subjects = QuestionBankItem::query()
            ->where('school_id', $schoolId)
            ->selectRaw('subject_id, COUNT(*) as question_count')
            ->whereNotNull('subject_id')
            ->groupBy('subject_id')
            ->havingRaw('COUNT(*) >= 1')
            ->get();

        $names = Subject::query()
            ->where('school_id', $schoolId)
            ->whereIn('id', $subjects->pluck('subject_id'))
            ->pluck('name', 'id');

        $active = CbtExamSession::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        return $this->success([
            'subjects' => $subjects->map(fn ($row) => [
                'subject_id' => (int) $row->subject_id,
                'subject_name' => $names[(int) $row->subject_id] ?? 'Subject '.$row->subject_id,
                'question_count' => (int) $row->question_count,
            ])->values()->all(),
            'active_session' => $active ? [
                'id' => $active->id,
                'exam_id' => $active->exam_id,
                'started_at' => $active->started_at?->toIso8601String(),
                'question_count' => is_array($active->question_ids) ? count($active->question_ids) : 0,
            ] : null,
        ]);
    }

    public function startCbt(Request $request)
    {
        $student = $this->requireStudent($request);
        $schoolId = (int) $request->user()->school_id;

        $data = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'count' => ['nullable', 'integer', 'min:1', 'max:40'],
            'exam_id' => ['nullable', 'integer', 'exists:exams,id'],
        ]);

        $existing = CbtExamSession::query()
            ->where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($existing) {
            return $this->success($this->cbt->sessionPayloadForStudent($existing), 'Resuming in-progress session');
        }

        $count = (int) ($data['count'] ?? 10);
        $questions = $this->cbt->generateRandomPaper($schoolId, (int) $data['subject_id'], $count);
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'subject_id' => ['No questions are available for this subject yet.'],
            ]);
        }

        $session = $this->cbt->startCbtSession(
            $schoolId,
            $student->id,
            $questions->pluck('id')->all(),
            $data['exam_id'] ?? null,
        );

        return $this->created($this->cbt->sessionPayloadForStudent($session), 'CBT session started');
    }

    public function showCbtSession(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $session = CbtExamSession::query()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->findOrFail($id);

        return $this->success($this->cbt->sessionPayloadForStudent($session));
    }

    public function submitCbt(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $session = CbtExamSession::query()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->findOrFail($id);

        if ($session->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'session' => ['This exam session is already submitted.'],
            ]);
        }

        $data = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.answer' => ['required', 'string'],
        ]);

        $submitted = $this->cbt->submitCbtSession($session, $data['answers']);

        return $this->success([
            'id' => $submitted->id,
            'status' => $submitted->status,
            'score' => $submitted->score,
            'submitted_at' => $submitted->submitted_at?->toIso8601String(),
        ], 'Exam submitted');
    }

    public function cbtAntiCheat(Request $request, int $id)
    {
        $student = $this->requireStudent($request);
        $session = CbtExamSession::query()
            ->where('school_id', $request->user()->school_id)
            ->where('student_id', $student->id)
            ->findOrFail($id);

        $data = $request->validate([
            'event_type' => ['required', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $this->cbt->logAntiCheat($session->id, $data['event_type'], $data['metadata'] ?? null);

        return $this->success(message: 'Anti-cheat event logged');
    }

    /**
     * @return array{present:int,late:int,absent:int,excused:int,total_days:int,attendance_rate:float}
     */
    protected function attendanceStats(int $studentId): array
    {
        $rows = Attendance::query()
            ->where('student_id', $studentId)
            ->whereDate('date', '>=', now()->subDays(90))
            ->get(['status']);

        $present = $rows->where('status', 'present')->count();
        $late = $rows->where('status', 'late')->count();
        $absent = $rows->where('status', 'absent')->count();
        $excused = $rows->where('status', 'excused')->count();
        $total = $rows->count();

        return [
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'excused' => $excused,
            'total_days' => $total,
            'attendance_rate' => $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0.0,
        ];
    }

    protected function requireStudent(Request $request): Student
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->role !== UserRole::Student) {
            throw new AccessDeniedHttpException('Student portal access is restricted to student accounts.');
        }

        $student = $this->students->resolveForUser($user);
        if (! $student) {
            throw new AccessDeniedHttpException('Student profile is not linked to this account.');
        }

        $student->loadMissing('classModel:id,name');

        return $student;
    }
}
```

### File: `app/Http/Controllers/Api/V1/TeacherController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\HandlesResourceQueries;
use App\Http\Requests\Api\V1\Teacher\StoreTeacherRequest;
use App\Http\Requests\Api\V1\Teacher\UpdateTeacherRequest;
use App\Http\Resources\Api\V1\TeacherResource;
use App\Models\CustomField;
use App\Models\Teacher;
use App\Services\CustomFieldService;
use App\Services\StaffNumberService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use HandlesResourceQueries;

    public function __construct(
        private CustomFieldService $customFieldService,
        private StaffNumberService $staffNumbers,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

        return $this->paginateResource($request, Teacher::class, TeacherResource::class, [
            'filters' => ['status', 'department', 'subject', 'search'],
            'search_columns' => ['name', 'email', 'employee_id', 'first_name', 'last_name'],
            'sorts' => ['name', 'created_at', 'employee_id', 'status', 'department'],
            'includes' => ['designation'],
            'fields' => [
                'teachers.id',
                'teachers.name',
                'teachers.first_name',
                'teachers.last_name',
                'teachers.email',
                'teachers.phone',
                'teachers.employee_id',
                'teachers.department',
                'teachers.designation_id',
                'teachers.subject',
                'teachers.status',
                'teachers.school_id',
                'teachers.created_at',
            ],
            'default_sort' => 'name',
            'with' => ['designation:id,name,code'],
        ]);
    }

    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);

        return $this->success(new TeacherResource($teacher->load('designation')));
    }

    public function store(StoreTeacherRequest $request)
    {
        $firstName = $request->firstName ?? explode(' ', $request->name)[0] ?? '';
        $surname = $request->surname ?? (count(explode(' ', $request->name ?? '') ?? []) > 1
            ? implode(' ', array_slice(explode(' ', $request->name), 1))
            : '');

        $school = $request->user()->school;
        $defaultCurrency = $school?->getDefaultCurrency() ?? 'USD';

        $teacher = Teacher::create([
            'name' => trim($firstName.' '.$surname) ?: $request->name,
            'first_name' => $firstName,
            'last_name' => $surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'subject' => $request->subject,
            'department' => $request->department,
            'designation_id' => $request->input('designation_id'),
            'qualification' => $request->qualification,
            'joining_date' => $request->joiningDate,
            'employee_id' => $this->staffNumbers->generateEmployeeNumber((int) $request->user()->school_id),
            'status' => $request->input('status', 'active'),
            'employment_type' => $request->input('employment_type', 'full_time'),
            'base_salary' => $request->input('base_salary'),
            'period_rate' => $request->input('period_rate'),
            'salary_currency' => $request->input('salary_currency', $defaultCurrency),
            'allowances' => $request->input('allowances'),
            'deductions' => $request->input('deductions'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_number' => $request->input('bank_account_number'),
            'payment_method' => $request->input('payment_method', 'bank_transfer'),
            'school_id' => $request->user()->school_id,
        ]);

        if ($request->filled('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $teacher,
                CustomField::ENTITY_TEACHER,
                $request->input('custom_fields', [])
            );
        }

        return $this->created(new TeacherResource($teacher->fresh()), 'Teacher created successfully');
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        if ($request->has('firstName') || $request->has('surname')) {
            $teacher->first_name = $request->firstName ?? $teacher->first_name;
            $teacher->last_name = $request->surname ?? $teacher->last_name;
            $teacher->name = trim($teacher->first_name.' '.$teacher->last_name);
        }

        $teacher->fill($request->only([
            'email',
            'phone',
            'address',
            'subject',
            'department',
            'designation_id',
            'qualification',
            'status',
            'employment_type',
            'base_salary',
            'period_rate',
            'salary_currency',
            'allowances',
            'deductions',
            'bank_name',
            'bank_account_number',
            'payment_method',
        ]));
        if ($request->has('joiningDate')) {
            $teacher->joining_date = $request->joiningDate;
        }
        $teacher->save();

        if ($request->has('custom_fields')) {
            $this->customFieldService->validateAndSync(
                $teacher,
                CustomField::ENTITY_TEACHER,
                $request->input('custom_fields', [])
            );
        }

        return $this->success(new TeacherResource($teacher->fresh()), 'Teacher updated successfully');
    }

    public function destroy(Teacher $teacher)
    {
        $this->authorize('delete', $teacher);
        $teacher->delete();

        return $this->success(message: 'Teacher deleted successfully');
    }
}
```

### File: `app/Http/Controllers/Api/V1/TeacherPortalController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\TeachingAssistant;
use App\Models\AcademicCalendarEntry;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\BehaviorPoint;
use App\Models\ClassModel;
use App\Models\ClassParticipationRecord;
use App\Models\ClassSubstitution;
use App\Models\DisciplinaryRecord;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LessonPlan;
use App\Models\OnlineLesson;
use App\Models\ReportCardNarrative;
use App\Models\SchoolEvent;
use App\Models\Student;
use App\Models\StudentIntervention;
use App\Models\SyllabusTopic;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherNotification;
use App\Models\TeachingResource;
use App\Models\Timetable;
use App\Models\TimetableChangeRequest;
use App\Services\Domain\SchoolDomainRules;
use App\Services\Export\ExportService;
use App\Services\PermissionService;
use App\Services\TeacherResolutionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherPortalController extends Controller
{
    public function __construct(
        private TeachingAssistant $assistant,
        private ExportService $exports,
        private TeacherResolutionService $teachers,
    ) {}

    protected function requireTeacher(Request $request, array $capabilities = ['isStaff']): Teacher
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->authorizeModuleAccess($request, capabilities: $capabilities);

        $teacher = $this->teachers->resolveForUser($user);
        abort_unless($teacher, 403, 'Teacher profile not linked to this account.');

        return $teacher;
    }

    /**
     * Teachers manage their own LMS items; school admins manage the whole school LMS.
     *
     * @return array{0: ?Teacher, 1: bool} [resolved teacher or null, is school LMS manager]
     */
    protected function resolveLmsActor(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->authorizeModuleAccess($request, capabilities: ['isStaff', 'canManageTeachers']);

        $teacher = $this->teachers->resolveForUser($user);
        $isSchoolManager = app(PermissionService::class)->hasCapability($user, 'canManageTeachers');

        abort_unless($teacher || $isSchoolManager, 403, 'Teacher profile not linked to this account.');

        return [$teacher, $isSchoolManager];
    }

    /**
     * Teachers submit/lock their own class registers; school managers may do so for any class.
     *
     * @return array{0: ?Teacher, 1: bool}
     */
    protected function resolveAttendanceActor(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 401);

        $this->authorizeModuleAccess($request, capabilities: ['canManageStudents']);

        $teacher = $this->teachers->resolveForUser($user);
        $isSchoolManager = app(PermissionService::class)->hasCapability($user, 'canManageTeachers');

        abort_unless($teacher || $isSchoolManager, 403, 'Teacher profile not linked to this account.');

        return [$teacher, $isSchoolManager];
    }

    protected function schoolId(Request $request): int
    {
        return (int) $request->user()->school_id;
    }

    /** Dashboard widgets for the teacher portal. */
    public function dashboard(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);
        $today = now()->toDateString();

        $classIds = $this->teachers->assignedClassIds($teacher);

        $pendingAttendance = $classIds->filter(function ($classId) use ($schoolId, $today) {
            return ! Attendance::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->whereDate('date', $today)
                ->exists();
        })->count();

        $upcomingExams = Exam::query()
            ->where('school_id', $schoolId)
            ->whereDate('exam_date', '>=', $today)
            ->orderBy('exam_date')
            ->limit(8)
            ->get(['id', 'name', 'exam_date', 'status']);

        $announcements = Announcement::query()
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'created_at']);

        $unread = TeacherNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        $studentCount = Student::query()
            ->where('school_id', $schoolId)
            ->whereIn('class_id', $classIds)
            ->count();

        return $this->success([
            'classes_count' => $classIds->count(),
            'students_count' => $studentCount,
            'pending_attendance_classes' => $pendingAttendance,
            'upcoming_exams' => $upcomingExams,
            'announcements' => $announcements,
            'unread_notifications' => $unread,
            'workload' => [
                'lesson_plans_draft' => LessonPlan::where('teacher_id', $teacher->id)->where('status', 'draft')->count(),
                'assignments_open' => Assignment::where('teacher_id', $teacher->id)->where('status', '!=', 'closed')->count(),
                'submissions_to_grade' => AssignmentSubmission::query()
                    ->whereHas('assignment', fn ($q) => $q->where('teacher_id', $teacher->id))
                    ->where('status', 'submitted')
                    ->count(),
            ],
        ]);
    }

    /** My Classes with students and behaviour history. */
    public function myClasses(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $assignments = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->whereNotNull('class_id')
            ->with(['classModel:id,name,teacher_id', 'subject:id,name'])
            ->get();

        $classes = $assignments->groupBy('class_id')->map(function ($rows, $classId) use ($schoolId) {
            $first = $rows->first();
            $classId = (int) $classId;
            $students = Student::query()
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where(function ($q) {
                    $q->whereNull('status')
                        ->orWhereNotIn('status', SchoolDomainRules::inactiveStudentStatuses());
                })
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'student_number', 'status', 'class_id']);

            return [
                'class_id' => $classId,
                'class_name' => $first->classModel?->name ?? 'Unnamed class',
                'subjects' => $rows->pluck('subject.name')->filter()->unique()->values(),
                'student_count' => $students->count(),
                'students' => $students,
            ];
        })->values();

        return $this->success($classes);
    }

    public function classStudentDetail(Request $request, int $studentId)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $student = Student::query()->where('school_id', $schoolId)->findOrFail($studentId);
        $this->assertTeacherOwnsClass($teacher, $student->class_id);

        $attendance = Attendance::query()
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->limit(30)
            ->get(['id', 'date', 'status', 'remarks']);

        $behaviour = BehaviorPoint::query()
            ->where('student_id', $studentId)
            ->orderByDesc('recorded_on')
            ->limit(20)
            ->get();

        $discipline = DisciplinaryRecord::query()
            ->where('student_id', $studentId)
            ->orderByDesc('incident_date')
            ->limit(20)
            ->get();

        $canViewParents = $request->user()?->role?->canManageTeachers()
            || app(PermissionService::class)->hasCapability($request->user(), 'canManageTeachers');

        return $this->success([
            'student' => $student->only(['id', 'full_name', 'student_number', 'status', 'class_id', 'gender', 'date_of_birth']),
            'attendance_history' => $attendance,
            'behaviour_history' => $behaviour,
            'discipline_history' => $discipline,
            'parent_contacts' => $canViewParents ? [
                'guardian' => $student->guardian,
                'phone' => $student->guardian_phone ?? null,
                'email' => $student->guardian_email ?? null,
            ] : null,
        ]);
    }

    // —— Attendance extras ——

    public function submitAttendance(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveAttendanceActor($request);
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'string', 'max:50'],
        ]);

        $classId = (int) $data['class_id'];
        $this->assertClassInSchool($request, $classId);

        if (! $isSchoolManager) {
            $this->assertTeacherOwnsClass($teacher, $classId);
            if (! empty($data['subject_id'])) {
                $this->assertTeacherOwnsSubject($teacher, $data['subject_id'], $classId);
            }
        }

        $session = AttendanceSession::query()->updateOrCreate(
            [
                'school_id' => $this->schoolId($request),
                'class_id' => $data['class_id'],
                'date' => $data['date'],
                'subject_id' => $data['subject_id'] ?? null,
                'period' => $data['period'] ?? null,
            ],
            [
                'teacher_id' => $teacher?->id,
                'submitted_at' => now(),
            ]
        );

        Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->where('class_id', $data['class_id'])
            ->whereDate('date', $data['date'])
            ->whereNull('submitted_at')
            ->update(['submitted_at' => now()]);

        return $this->success($session, 'Attendance submitted');
    }

    public function lockAttendance(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveAttendanceActor($request);
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer'],
            'period' => ['nullable', 'string', 'max:50'],
        ]);

        $classId = (int) $data['class_id'];
        $this->assertClassInSchool($request, $classId);

        if (! $isSchoolManager) {
            $this->assertTeacherOwnsClass($teacher, $classId);
            if (! empty($data['subject_id'])) {
                $this->assertTeacherOwnsSubject($teacher, $data['subject_id'], $classId);
            }
        }

        $session = AttendanceSession::query()->updateOrCreate(
            [
                'school_id' => $this->schoolId($request),
                'class_id' => $data['class_id'],
                'date' => $data['date'],
                'subject_id' => $data['subject_id'] ?? null,
                'period' => $data['period'] ?? null,
            ],
            [
                'teacher_id' => $teacher?->id,
                'submitted_at' => now(),
                'locked_at' => now(),
                'locked_by' => $request->user()->id,
            ]
        );

        $attendanceQuery = Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->where('class_id', $data['class_id'])
            ->whereDate('date', $data['date']);

        // Set submitted_at only when missing (avoid MySQL-only NOW() for SQLite).
        (clone $attendanceQuery)
            ->whereNull('submitted_at')
            ->update(['submitted_at' => now()]);

        $attendanceQuery->update([
            'locked_at' => now(),
            'locked_by' => $request->user()->id,
        ]);

        return $this->success($session, 'Attendance locked');
    }

    public function attendanceReports(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveAttendanceActor($request);
        $data = $request->validate([
            'class_id' => ['required', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'class', 'student', 'chronic'])],
            'student_id' => ['nullable', 'integer'],
            'format' => ['nullable', Rule::in(['json', 'csv', 'print'])],
        ]);

        $classId = (int) $data['class_id'];
        $this->assertClassInSchool($request, $classId);

        if (! $isSchoolManager) {
            $this->assertTeacherOwnsClass($teacher, $classId);
        }

        $from = $data['from'] ?? now()->subDays(30)->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $type = $data['type'] ?? 'class';

        $query = Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->where('class_id', $data['class_id'])
            ->whereBetween('date', [$from, $to])
            ->with('student:id,full_name,student_number');

        if (! empty($data['student_id'])) {
            $query->where('student_id', $data['student_id']);
        }

        $rows = $query->orderBy('date')->get();

        if ($type === 'chronic') {
            $totals = $rows->groupBy('student_id')->map(function ($group) {
                $absent = $group->whereIn('status', ['absent', 'sick'])->count();
                $total = $group->count();
                $rate = $total > 0 ? round(($absent / $total) * 100, 1) : 0;

                return [
                    'student_id' => $group->first()->student_id,
                    'student' => $group->first()->student?->full_name,
                    'absent_days' => $absent,
                    'total_days' => $total,
                    'absence_rate' => $rate,
                    'chronic' => $rate >= 20,
                ];
            })->values()->filter(fn ($r) => $r['chronic'])->values();

            return $this->exportOrJson($request, $data['format'] ?? 'json', 'chronic-absence', [
                'Student', 'Absent days', 'Total days', 'Absence %',
            ], $totals->map(fn ($r) => [$r['student'], $r['absent_days'], $r['total_days'], $r['absence_rate']])->all(), $totals);
        }

        $payload = $rows->map(fn ($r) => [
            'date' => $r->date?->toDateString(),
            'student' => $r->student?->full_name,
            'status' => $r->status,
            'remarks' => $r->remarks,
        ]);

        return $this->exportOrJson($request, $data['format'] ?? 'json', 'attendance-report', [
            'Date', 'Student', 'Status', 'Remarks',
        ], $payload->map(fn ($r) => array_values($r))->all(), $payload);
    }

    // —— Timetable ——

    public function freePeriods(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $slots = Timetable::query()
            ->where('school_id', $this->schoolId($request))
            ->where('teacher_id', $teacher->id)
            ->get(['day', 'start_time', 'end_time']);

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00'];
        $busy = $slots->map(fn ($s) => $s->day.'|'.substr((string) $s->start_time, 0, 5))->flip();

        $free = [];
        foreach ($days as $day) {
            foreach ($periods as $p) {
                if (! $busy->has($day.'|'.$p)) {
                    $free[] = ['day' => $day, 'start_time' => $p];
                }
            }
        }

        return $this->success($free);
    }

    public function examTimetable(Request $request)
    {
        $this->requireTeacher($request);
        $exams = Exam::query()
            ->where('school_id', $this->schoolId($request))
            ->whereDate('exam_date', '>=', now()->subDays(7))
            ->orderBy('exam_date')
            ->limit(50)
            ->get();

        return $this->success($exams);
    }

    public function timetableChangeRequests(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            TimetableChangeRequest::query()
                ->where('teacher_id', $teacher->id)
                ->latest()
                ->limit(50)
                ->get()
        );
    }

    public function storeTimetableChangeRequest(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'request_type' => ['required', Rule::in(['change', 'conflict'])],
            'details' => ['required', 'string', 'max:5000'],
            'preferred_slot' => ['nullable', 'string', 'max:255'],
        ]);

        $row = TimetableChangeRequest::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            ...$data,
            'status' => 'pending',
        ]);

        return $this->created($row, 'Timetable request submitted');
    }

    // —— Calendar ——

    public function calendar(Request $request)
    {
        $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $events = SchoolEvent::query()
            ->where('school_id', $schoolId)
            ->orderBy('starts_at')
            ->limit(100)
            ->get()
            ->map(fn (SchoolEvent $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'type' => $event->type,
                'starts_at' => optional($event->starts_at)?->toIso8601String(),
                'ends_at' => optional($event->ends_at)?->toIso8601String(),
                'location' => $event->location,
                'status' => $event->status,
            ]);

        $academic = AcademicCalendarEntry::query()
            ->where('school_id', $schoolId)
            ->whereDate('start_date', '>=', now()->subMonths(1)->toDateString())
            ->orderBy('start_date')
            ->limit(100)
            ->get()
            ->map(fn (AcademicCalendarEntry $entry) => [
                'id' => $entry->id,
                'title' => $entry->title,
                'entry_type' => $entry->entry_type,
                'start_date' => optional($entry->start_date)?->toDateString(),
                'end_date' => optional($entry->end_date)?->toDateString(),
                'is_holiday' => (bool) $entry->is_holiday,
            ]);

        $deadlines = Assignment::query()
            ->where('teacher_id', $request->user()->teacher?->id ?? 0)
            ->whereNotNull('due_date')
            ->orderBy('due_date')
            ->limit(50)
            ->get(['id', 'title', 'due_date', 'class_id']);

        return $this->success([
            'events' => $events,
            'academic_entries' => $academic,
            'assignment_deadlines' => $deadlines,
            'exams' => Exam::query()->where('school_id', $schoolId)->whereDate('exam_date', '>=', now())->limit(30)->get(),
        ]);
    }

    // —— Lesson plans ——

    public function lessonPlans(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            LessonPlan::query()
                ->where('teacher_id', $teacher->id)
                ->with(['classModel:id,name', 'subject:id,name'])
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function storeLessonPlan(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'plan_type' => ['nullable', Rule::in(['lesson', 'weekly', 'term', 'topic'])],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'planned_date' => ['nullable', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'resources' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'completed'])],
            'copy_from_id' => ['nullable', 'integer'],
        ]);

        if (! empty($data['copy_from_id'])) {
            $source = LessonPlan::query()->where('teacher_id', $teacher->id)->findOrFail($data['copy_from_id']);
            $data = array_merge($source->only([
                'class_id', 'subject_id', 'plan_type', 'topic', 'objectives', 'outcomes', 'activities', 'resources',
            ]), array_filter($data, fn ($v) => $v !== null), [
                'title' => $data['title'] ?: ($source->title.' (copy)'),
            ]);
            unset($data['copy_from_id']);
        }

        if (! empty($data['class_id'])) {
            $this->assertTeacherOwnsClass($teacher, $data['class_id']);
        }
        if (! empty($data['subject_id'])) {
            $this->assertTeacherOwnsSubject(
                $teacher,
                $data['subject_id'],
                ! empty($data['class_id']) ? (int) $data['class_id'] : null,
            );
        }

        $plan = LessonPlan::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'plan_type' => $data['plan_type'] ?? 'lesson',
            'status' => $data['status'] ?? 'draft',
            ...collect($data)->except(['copy_from_id', 'plan_type', 'status'])->all(),
        ]);

        return $this->created($plan->load(['classModel:id,name', 'subject:id,name']));
    }

    public function updateLessonPlan(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request);
        $plan = LessonPlan::query()->where('teacher_id', $teacher->id)->findOrFail($id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'plan_type' => ['sometimes', Rule::in(['lesson', 'weekly', 'term', 'topic'])],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'planned_date' => ['nullable', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
            'objectives' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'activities' => ['nullable', 'string'],
            'resources' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['draft', 'submitted', 'completed'])],
        ]);

        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
        }
        if (($data['status'] ?? null) === 'submitted') {
            $data['submitted_at'] = now();
        }

        $plan->update($data);

        return $this->success($plan->fresh()->load(['classModel:id,name', 'subject:id,name']));
    }

    // —— Syllabus ——

    public function syllabusTopics(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $q = SyllabusTopic::query()->where('teacher_id', $teacher->id)->with(['subject:id,name', 'classModel:id,name']);
        if ($request->filled('subject_id')) {
            $q->where('subject_id', $request->integer('subject_id'));
        }

        return $this->success($q->orderBy('sort_order')->limit(200)->get());
    }

    public function storeSyllabusTopic(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'subject_id' => ['required', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'objectives' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['planned', 'in_progress', 'completed'])],
            'coverage_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        if (! empty($data['class_id'])) {
            $this->assertTeacherOwnsClass($teacher, $data['class_id']);
        }
        $this->assertTeacherOwnsSubject(
            $teacher,
            $data['subject_id'],
            ! empty($data['class_id']) ? (int) $data['class_id'] : null,
        );

        $topic = SyllabusTopic::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'status' => $data['status'] ?? 'planned',
            'coverage_percent' => $data['coverage_percent'] ?? 0,
            ...collect($data)->except(['status', 'coverage_percent'])->all(),
        ]);

        return $this->created($topic);
    }

    public function updateSyllabusTopic(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request);
        $topic = SyllabusTopic::query()->where('teacher_id', $teacher->id)->findOrFail($id);
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'chapter' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'objectives' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['planned', 'in_progress', 'completed'])],
            'coverage_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ]);
        if (($data['status'] ?? null) === 'completed') {
            $data['completed_on'] = now()->toDateString();
            $data['coverage_percent'] = $data['coverage_percent'] ?? 100;
        }
        $topic->update($data);

        return $this->success($topic->fresh());
    }

    // —— Resources ——

    public function resources(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $q = TeachingResource::query()
            ->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)->orWhere('shared', true);
            })
            ->where('school_id', $this->schoolId($request))
            ->with(['subject:id,name', 'classModel:id,name'])
            ->latest();

        foreach (['subject_id', 'class_id', 'resource_type', 'topic', 'term'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->input($f));
            }
        }

        return $this->success($q->limit(200)->get());
    }

    public function storeResource(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'resource_type' => ['nullable', 'string', 'max:50'],
            'subject_id' => ['nullable', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'topic' => ['nullable', 'string', 'max:255'],
            'term' => ['nullable', 'string', 'max:100'],
            'file_url' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'shared' => ['nullable', 'boolean'],
        ]);

        $row = TeachingResource::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'resource_type' => $data['resource_type'] ?? 'notes',
            'shared' => (bool) ($data['shared'] ?? false),
            ...collect($data)->except(['resource_type', 'shared'])->all(),
        ]);

        return $this->created($row);
    }

    // —— Homework submissions ——

    public function assignmentSubmissions(Request $request, int $assignmentId)
    {
        $teacher = $this->requireTeacher($request);
        Assignment::query()->where('teacher_id', $teacher->id)->findOrFail($assignmentId);

        return $this->success(
            AssignmentSubmission::query()
                ->where('assignment_id', $assignmentId)
                ->with('student:id,full_name,student_number')
                ->latest()
                ->get()
        );
    }

    public function gradeSubmission(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request, ['canEnterExamResults']);
        $sub = AssignmentSubmission::query()
            ->whereHas('assignment', fn ($q) => $q->where('teacher_id', $teacher->id))
            ->findOrFail($id);

        $data = $request->validate([
            'score' => ['nullable', 'numeric'],
            'teacher_comment' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['graded', 'returned', 'resubmit'])],
        ]);

        $sub->update([
            ...$data,
            'graded_at' => now(),
            'returned_at' => in_array($data['status'], ['returned', 'resubmit'], true) ? now() : $sub->returned_at,
        ]);

        return $this->success($sub->fresh()->load('student:id,full_name'));
    }

    public function storeSubmission(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'assignment_id' => ['required', 'integer'],
            'student_id' => ['required', 'integer'],
            'file_url' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['submitted', 'graded', 'returned', 'resubmit'])],
        ]);
        Assignment::query()->where('teacher_id', $teacher->id)->findOrFail($data['assignment_id']);

        $sub = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $data['assignment_id'],
                'student_id' => $data['student_id'],
            ],
            [
                'school_id' => $this->schoolId($request),
                'file_url' => $data['file_url'] ?? null,
                'content' => $data['content'] ?? null,
                'status' => $data['status'] ?? 'submitted',
                'submitted_at' => now(),
            ]
        );

        return $this->created($sub);
    }

    // —— LMS / Online lessons ——

    public function onlineLessons(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveLmsActor($request);
        $schoolId = $this->schoolId($request);

        $query = OnlineLesson::query()
            ->where('school_id', $schoolId)
            ->with(['classModel:id,name', 'subject:id,name', 'teacher:id,name,email']);

        if (! $isSchoolManager) {
            $query->where('teacher_id', $teacher->id);
        }

        return $this->success(
            $query->latest('scheduled_at')->limit(100)->get()
        );
    }

    public function storeOnlineLesson(Request $request)
    {
        [$teacher, $isSchoolManager] = $this->resolveLmsActor($request);
        $schoolId = $this->schoolId($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'lesson_type' => ['nullable', Rule::in(['live', 'recorded', 'discussion', 'quiz', 'poll'])],
            'teacher_id' => [
                $isSchoolManager && ! $teacher ? 'required' : 'nullable',
                'integer',
                Rule::exists('teachers', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'scheduled_at' => ['nullable', 'date'],
            'meeting_url' => ['nullable', 'string', 'max:2048'],
            'recording_url' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);

        $teacherId = isset($data['teacher_id']) ? (int) $data['teacher_id'] : null;
        if (! $teacherId && $teacher) {
            $teacherId = $teacher->id;
        }
        abort_unless($teacherId, 422, 'Select a teacher for this online lesson.');

        $row = OnlineLesson::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacherId,
            'lesson_type' => $data['lesson_type'] ?? 'live',
            'status' => $data['status'] ?? 'scheduled',
            // Stub meeting URL when live lesson has none — real video later.
            'meeting_url' => $data['meeting_url'] ?? (($data['lesson_type'] ?? 'live') === 'live'
                ? 'https://meet.example.local/room/'.uniqid('lesson_')
                : null),
            ...collect($data)->except(['lesson_type', 'status', 'meeting_url', 'teacher_id'])->all(),
        ]);

        return $this->created($row->load(['teacher:id,name', 'classModel:id,name', 'subject:id,name']));
    }

    // —— Report cards ——

    public function reportCards(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            ReportCardNarrative::query()
                ->where('teacher_id', $teacher->id)
                ->with(['student:id,full_name', 'classModel:id,name', 'term:id,name'])
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function storeReportCard(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canEnterExamResults']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'term_id' => ['nullable', 'integer'],
            'academic_comment' => ['nullable', 'string'],
            'behaviour_comment' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted'])],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $data['class_id'] ?? $student->class_id);

        $row = ReportCardNarrative::query()->updateOrCreate(
            [
                'teacher_id' => $teacher->id,
                'student_id' => $data['student_id'],
                'term_id' => $data['term_id'] ?? null,
            ],
            [
                'school_id' => $this->schoolId($request),
                'class_id' => $data['class_id'] ?? $student->class_id,
                'academic_comment' => $data['academic_comment'] ?? null,
                'behaviour_comment' => $data['behaviour_comment'] ?? null,
                'recommendations' => $data['recommendations'] ?? null,
                'strengths' => $data['strengths'] ?? null,
                'areas_for_improvement' => $data['areas_for_improvement'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'submitted_at' => ($data['status'] ?? null) === 'submitted' ? now() : null,
            ]
        );

        return $this->success($row->load(['student:id,full_name']));
    }

    // —— Behaviour & support ——

    public function behaviourPoints(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $classIds = $this->teacherClassIds($teacher);

        $q = BehaviorPoint::query()
            ->where('school_id', $this->schoolId($request))
            ->whereHas('student', fn ($s) => $s->whereIn('class_id', $classIds))
            ->with('student:id,full_name,class_id')
            ->latest('recorded_on');

        if ($request->filled('student_id')) {
            $q->where('student_id', $request->integer('student_id'));
        }

        return $this->success($q->limit(100)->get());
    }

    public function storeBehaviourPoint(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'points' => ['required', 'integer'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'recorded_on' => ['nullable', 'date'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $student->class_id);

        $row = BehaviorPoint::create([
            'school_id' => $this->schoolId($request),
            'student_id' => $data['student_id'],
            'points' => $data['points'],
            'category' => $data['category'],
            'description' => $data['description'],
            'recorded_by' => $request->user()->id,
            'recorded_on' => $data['recorded_on'] ?? now()->toDateString(),
        ]);

        return $this->created($row);
    }

    public function interventions(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $classIds = $this->teacherClassIds($teacher);

        return $this->success(
            StudentIntervention::query()
                ->where('school_id', $this->schoolId($request))
                ->whereHas('student', fn ($s) => $s->whereIn('class_id', $classIds))
                ->with('student:id,full_name')
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function storeIntervention(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canManageStudents']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'intervention_type' => ['required', 'string', 'max:100'],
            'summary' => ['required', 'string'],
            'action_plan' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'follow_up_date' => ['nullable', 'date'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $student->class_id);

        $row = StudentIntervention::create([
            'school_id' => $this->schoolId($request),
            'student_id' => $data['student_id'],
            'intervention_type' => $data['intervention_type'],
            'summary' => $data['summary'],
            'action_plan' => $data['action_plan'] ?? null,
            'assigned_to' => $request->user()->id,
            'created_by' => $request->user()->id,
            'start_date' => $data['start_date'] ?? now()->toDateString(),
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'status' => 'open',
        ]);

        return $this->created($row);
    }

    public function participation(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success(
            ClassParticipationRecord::query()
                ->where('teacher_id', $teacher->id)
                ->with('student:id,full_name')
                ->latest('recorded_on')
                ->limit(100)
                ->get()
        );
    }

    public function storeParticipation(Request $request)
    {
        $teacher = $this->requireTeacher($request, ['canEnterExamResults']);
        $data = $request->validate([
            'student_id' => ['required', 'integer'],
            'class_id' => ['nullable', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'recorded_on' => ['nullable', 'date'],
            'score' => ['required', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId($request))->findOrFail($data['student_id']);
        $this->assertTeacherOwnsClass($teacher, $data['class_id'] ?? $student->class_id);

        $row = ClassParticipationRecord::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'recorded_on' => $data['recorded_on'] ?? now()->toDateString(),
            ...collect($data)->except(['recorded_on'])->all(),
        ]);

        return $this->created($row);
    }

    // —— Department ——

    public function department(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $schoolId = $this->schoolId($request);

        $peers = Teacher::query()
            ->where('school_id', $schoolId)
            ->when($teacher->department, fn ($q) => $q->where('department', $teacher->department))
            ->get(['id', 'name', 'department', 'subject', 'email']);

        $shared = TeachingResource::query()
            ->where('school_id', $schoolId)
            ->where('shared', true)
            ->latest()
            ->limit(30)
            ->get();

        return $this->success([
            'department' => $teacher->department,
            'peers' => $peers,
            'shared_resources' => $shared,
        ]);
    }

    // —— Leave ——

    public function myLeave(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        $rows = LeaveRequest::query()
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->limit(50)
            ->get();

        $used = LeaveRequest::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->sum('days');

        return $this->success([
            'balance' => [
                'annual_allowance' => 21,
                'used' => (int) $used,
                'remaining' => max(0, 21 - (int) $used),
            ],
            'requests' => $rows,
        ]);
    }

    public function applyLeave(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $data = $request->validate([
            'leave_type_id' => [
                'nullable',
                'integer',
                Rule::exists('leave_types', 'id')->where(fn ($q) => $q->where('school_id', $this->schoolId($request))->where('is_active', true)),
            ],
            'type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'request_replacement' => ['nullable', 'boolean'],
        ]);

        if (empty($data['leave_type_id']) && empty($data['type'])) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['type' => ['Select a leave type.']],
            ], 422);
        }

        $leaveType = null;
        if (! empty($data['leave_type_id'])) {
            $leaveType = LeaveType::query()
                ->where('school_id', $this->schoolId($request))
                ->findOrFail($data['leave_type_id']);
        }

        $start = $request->date('start_date');
        $end = $request->date('end_date');
        $days = $start->diffInDays($end) + 1;

        $leave = LeaveRequest::create([
            'school_id' => $this->schoolId($request),
            'teacher_id' => $teacher->id,
            'leave_type_id' => $leaveType?->id,
            'requested_by' => $request->user()->id,
            'type' => $leaveType?->code ?: ($leaveType?->name ?: $data['type']),
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        if ($request->boolean('request_replacement')) {
            $classIds = $this->teacherClassIds($teacher);
            foreach ($classIds->take(5) as $classId) {
                ClassSubstitution::create([
                    'school_id' => $this->schoolId($request),
                    'class_id' => $classId,
                    'absent_teacher_id' => $teacher->id,
                    'leave_request_id' => $leave->id,
                    'date' => $start->toDateString(),
                    'status' => 'open',
                    'notes' => 'Auto-created from leave request',
                ]);
            }
        }

        return $this->created($leave, 'Leave request submitted');
    }

    // —— Substitutions ——

    public function substitutions(Request $request)
    {
        $teacher = $this->requireTeacher($request);

        return $this->success([
            'open' => ClassSubstitution::query()
                ->where('school_id', $this->schoolId($request))
                ->where('status', 'open')
                ->where('absent_teacher_id', '!=', $teacher->id)
                ->with(['classModel:id,name', 'absentTeacher:id,name', 'subject:id,name'])
                ->orderBy('date')
                ->limit(50)
                ->get(),
            'mine' => ClassSubstitution::query()
                ->where(function ($q) use ($teacher) {
                    $q->where('substitute_teacher_id', $teacher->id)
                        ->orWhere('absent_teacher_id', $teacher->id);
                })
                ->with(['classModel:id,name', 'absentTeacher:id,name', 'substituteTeacher:id,name'])
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function acceptSubstitution(Request $request, int $id)
    {
        $teacher = $this->requireTeacher($request);
        $row = ClassSubstitution::query()
            ->where('school_id', $this->schoolId($request))
            ->where('status', 'open')
            ->findOrFail($id);

        $row->update([
            'substitute_teacher_id' => $teacher->id,
            'status' => 'accepted',
        ]);

        return $this->success($row->fresh(), 'Cover lesson accepted');
    }

    // —— Notifications ——

    public function notifications(Request $request)
    {
        $this->requireTeacher($request);

        return $this->success(
            TeacherNotification::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(100)
                ->get()
        );
    }

    public function markNotificationRead(Request $request, int $id)
    {
        $this->requireTeacher($request);
        $n = TeacherNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $n->update(['read_at' => now()]);

        return $this->success($n);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $this->requireTeacher($request);
        TeacherNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'All notifications marked read');
    }

    // —— AI ——

    public function aiGenerate(Request $request)
    {
        $this->requireTeacher($request);
        $data = $request->validate([
            'task' => ['required', 'string', 'max:100'],
            'context' => ['nullable', 'array'],
        ]);

        return $this->success($this->assistant->generate($data['task'], $data['context'] ?? []));
    }

    // —— Export suite ——

    public function export(Request $request)
    {
        $teacher = $this->requireTeacher($request);
        $permissions = app(PermissionService::class);
        abort_unless(
            $permissions->hasPermission($request->user(), 'reports.view')
                || $permissions->hasPermission($request->user(), 'reports.generate'),
            403,
            'You do not have permission to export reports.'
        );
        $data = $request->validate([
            'type' => ['required', Rule::in([
                'attendance', 'marksheet', 'class_list', 'assignments', 'exams', 'progress', 'lesson_plans',
            ])],
            'class_id' => ['nullable', 'integer'],
            'format' => ['nullable', Rule::in(['csv', 'print'])],
        ]);
        $format = $data['format'] ?? 'csv';
        $schoolId = $this->schoolId($request);

        return match ($data['type']) {
            'class_list' => $this->exportClassList($request, $teacher, $data['class_id'] ?? null, $format),
            'lesson_plans' => $this->exportOrJson($request, $format, 'lesson-plans', [
                'Title', 'Type', 'Topic', 'Status', 'Date',
            ], LessonPlan::where('teacher_id', $teacher->id)->latest()->limit(200)->get()
                ->map(fn ($p) => [$p->title, $p->plan_type, $p->topic, $p->status, $p->planned_date?->toDateString()])->all()),
            'assignments' => $this->exportOrJson($request, $format, 'assignments', [
                'Title', 'Due', 'Marks', 'Status',
            ], Assignment::where('teacher_id', $teacher->id)->latest()->limit(200)->get()
                ->map(fn ($a) => [$a->title, $a->due_date?->toDateString(), $a->total_marks, $a->status])->all()),
            'exams' => $this->exportOrJson($request, $format, 'exams', [
                'Name', 'Date', 'Published',
            ], Exam::where('school_id', $schoolId)->orderByDesc('exam_date')->limit(200)->get()
                ->map(fn ($e) => [$e->name, $e->exam_date?->toDateString(), $e->is_published ? 'yes' : 'no'])->all()),
            'attendance' => $this->exportTeacherAttendance($request, $teacher, $data['class_id'] ?? null, $format),
            'marksheet' => $this->exportTeacherMarksheet($request, $teacher, $data['class_id'] ?? null, $format),
            'progress' => $this->exportTeacherProgress($request, $teacher, $data['class_id'] ?? null, $format),
            default => $this->exportClassList($request, $teacher, $data['class_id'] ?? null, $format),
        };
    }

    protected function exportTeacherAttendance(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        $classIds = $classId
            ? tap(collect([(int) $classId]), fn ($ids) => $this->assertTeacherOwnsClass($teacher, $classId))
            : $this->teacherClassIds($teacher);

        $rows = Attendance::query()
            ->where('school_id', $this->schoolId($request))
            ->whereIn('class_id', $classIds->isEmpty() ? [0] : $classIds->all())
            ->with(['student:id,full_name,student_number', 'classModel:id,name'])
            ->orderByDesc('date')
            ->limit(500)
            ->get()
            ->map(fn ($a) => [
                $a->date?->toDateString() ?? $a->date,
                $a->classModel?->name,
                $a->student?->full_name,
                $a->student?->student_number,
                $a->status,
                $a->remarks,
            ])
            ->all();

        return $this->exportOrJson($request, $format, 'attendance', [
            'Date', 'Class', 'Student', 'Student number', 'Status', 'Remarks',
        ], $rows);
    }

    protected function exportTeacherMarksheet(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        $classIds = $classId
            ? tap(collect([(int) $classId]), fn ($ids) => $this->assertTeacherOwnsClass($teacher, $classId))
            : $this->teacherClassIds($teacher);

        $rows = Grade::query()
            ->whereIn('class_id', $classIds->isEmpty() ? [0] : $classIds->all())
            ->when($teacher->school_id, fn ($q) => $q->where('school_id', $teacher->school_id))
            ->with('student:id,full_name,student_number')
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($g) => [
                $g->student?->full_name,
                $g->student?->student_number,
                $g->subject,
                $g->score,
                $g->total,
                $g->grade,
                $g->term,
                $g->year,
            ])
            ->all();

        return $this->exportOrJson($request, $format, 'marksheet', [
            'Student', 'Student number', 'Subject', 'Score', 'Total', 'Grade', 'Term', 'Year',
        ], $rows);
    }

    protected function exportTeacherProgress(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        $classIds = $classId
            ? tap(collect([(int) $classId]), fn ($ids) => $this->assertTeacherOwnsClass($teacher, $classId))
            : $this->teacherClassIds($teacher);

        $rows = SyllabusTopic::query()
            ->where('teacher_id', $teacher->id)
            ->when($classId, fn ($q) => $q->where('class_id', $classId))
            ->when(! $classId && $classIds->isNotEmpty(), fn ($q) => $q->whereIn('class_id', $classIds->all()))
            ->orderBy('title')
            ->limit(500)
            ->get()
            ->map(fn ($t) => [
                $t->title,
                $t->chapter,
                $t->status,
                $t->coverage_percent,
                $t->class_id,
                $t->subject_id,
            ])
            ->all();

        return $this->exportOrJson($request, $format, 'progress', [
            'Topic', 'Chapter', 'Status', 'Coverage %', 'Class ID', 'Subject ID',
        ], $rows);
    }

    protected function exportClassList(Request $request, Teacher $teacher, ?int $classId, string $format)
    {
        if ($classId) {
            $this->assertTeacherOwnsClass($teacher, $classId);
            $students = Student::query()
                ->where('school_id', $this->schoolId($request))
                ->where('class_id', $classId)
                ->orderBy('full_name')
                ->get(['full_name', 'student_number', 'status']);
        } else {
            $students = Student::query()
                ->where('school_id', $this->schoolId($request))
                ->whereIn('class_id', $this->teacherClassIds($teacher))
                ->orderBy('full_name')
                ->get(['full_name', 'student_number', 'status']);
        }

        return $this->exportOrJson($request, $format, 'class-list', [
            'Name', 'Student number', 'Status',
        ], $students->map(fn ($s) => [$s->full_name, $s->student_number, $s->status])->all());
    }

    protected function exportOrJson(Request $request, string $format, string $filename, array $headers, array $rows, mixed $jsonPayload = null)
    {
        if ($format === 'csv') {
            return $this->exports->csv($filename, $headers, $rows);
        }
        if ($format === 'print') {
            return $this->exports->printableHtml(ucwords(str_replace('-', ' ', $filename)), $this->exports->htmlTable($headers, $rows));
        }

        return $this->success($jsonPayload ?? $rows);
    }

    protected function teacherClassIds(Teacher $teacher)
    {
        return $this->teachers->assignedClassIds($teacher);
    }

    protected function assertClassInSchool(Request $request, int $classId): void
    {
        $exists = ClassModel::query()
            ->where('school_id', $this->schoolId($request))
            ->whereKey($classId)
            ->exists();

        abort_unless($exists, 422, 'Class not found in this school.');
    }

    protected function assertTeacherOwnsClass(Teacher $teacher, mixed $classId): void
    {
        $this->teachers->assertOwnsClass($teacher, $classId);
    }

    protected function assertTeacherOwnsSubject(Teacher $teacher, mixed $subjectId, ?int $classId = null): void
    {
        $this->teachers->assertOwnsSubject($teacher, $subjectId, $classId);
    }
}
```

### File: `app/Http/Controllers/Api/V1/TwoFactorAuthController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use App\Services\Auth\SecureAuthenticationService;
use App\Services\Tenancy\TenantSwitchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthController extends Controller
{
    public function __construct(
        private SecureAuthenticationService $authService,
        private TenantSwitchService $tenantSwitch,
        private TwoFactorAuthenticationProvider $provider,
    ) {}

    public function challenge(Request $request)
    {
        $data = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $userId = Cache::pull($this->challengeKey($data['challenge_token']));
        if (! $userId) {
            throw ValidationException::withMessages([
                'challenge_token' => ['This two-factor challenge has expired. Please sign in again.'],
            ]);
        }

        $user = User::query()->find($userId);
        if (! $user || ! $user->two_factor_secret) {
            throw ValidationException::withMessages([
                'code' => ['Unable to verify two-factor authentication.'],
            ]);
        }

        $code = str_replace(' ', '', $data['code']);
        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $valid = $this->provider->verify($secret, $code)
            || $this->validRecoveryCode($user, $code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code was invalid.'],
            ]);
        }

        $token = $this->authService->createApiToken($user);
        $context = $this->authService->resolveRoleContext($user);

        return $this->success([
            'user' => array_merge(
                (new \App\Http\Resources\Api\V1\UserResource($user))->resolve(),
                $context,
                ['schools' => $this->tenantSwitch->availableSchools($user)->all()],
            ),
            'token' => $token,
        ], 'Login successful');
    }

    public function status(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success([
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'confirmed' => $user->two_factor_confirmed_at !== null,
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable)
    {
        /** @var User $user */
        $user = $request->user();
        $enable($user);

        $user->refresh();

        return $this->success([
            'enabled' => true,
            'confirmed' => false,
            'qr_code_svg' => $user->twoFactorQrCodeSvg(),
            'setup_key' => Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
            'recovery_codes' => $user->recoveryCodes(),
        ], 'Two-factor authentication enabled. Confirm with an authenticator code.');
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $confirm($user, $data['code']);

        return $this->success([
            'enabled' => true,
            'confirmed' => true,
        ], 'Two-factor authentication confirmed.');
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable)
    {
        $request->validate([
            'password' => ['required', 'current_password:sanctum'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $disable($user);

        return $this->success([
            'enabled' => false,
            'confirmed' => false,
        ], 'Two-factor authentication disabled.');
    }

    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generate)
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $generate($user);
        $user->refresh();

        return $this->success([
            'recovery_codes' => $user->recoveryCodes(),
        ], 'Recovery codes regenerated.');
    }

    public function issueChallengeToken(User $user): string
    {
        $token = Str::random(64);
        Cache::put($this->challengeKey($token), $user->id, now()->addMinutes(10));

        return $token;
    }

    private function challengeKey(string $token): string
    {
        return 'two_factor_challenge:'.$token;
    }

    private function validRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->recoveryCodes();
        if (! in_array($code, $codes, true)) {
            return false;
        }

        $user->replaceRecoveryCode($code);

        return true;
    }
}
```

### File: `app/Http/Controllers/Api/V1/UploadController.php`
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,mp3,mp4,wav';

    /** @var list<string> */
    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'phar', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'svg', 'cgi', 'pl',
    ];

    public function __construct(private FileUploadService $uploads) {}

    /**
     * Generic authenticated-staff file upload used by resources, homework,
     * report cards, and profile avatars.
     */
    public function store(Request $request)
    {
        $this->authorizeModuleAccess($request, capabilities: ['isStaff']);

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:'.self::ALLOWED_MIMES],
            'directory' => ['sometimes', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\/\-]+$/'],
        ]);

        $file = $request->file('file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($ext, self::BLOCKED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => ['This file type is not allowed.'],
            ]);
        }

        $directory = (string) $request->input('directory', 'uploads');
        $schoolId = $request->user()?->school_id;

        if (str_contains($directory, '..') || str_starts_with($directory, '/')) {
            return response()->json([
                'message' => 'Invalid upload directory.',
                'errors' => ['directory' => ['Directory path is not allowed.']],
            ], 422);
        }

        return $this->created(
            $this->uploads->store($file, $directory, 'public', $schoolId),
            'File uploaded'
        );
    }
}
```

## 6. Architectural Traits & Concerns
### File: `app/Models/Concerns/Auditable.php`
```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

trait Auditable
{
    public static function bootAuditable(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        Event::listen("eloquent.created: {$modelClass}", function (Model $model) {
            app(\App\Services\AuditService::class)->logModelEvent($model, 'created');
        });

        Event::listen("eloquent.updated: {$modelClass}", function (Model $model) {
            if ($model->getChanges() !== []) {
                app(\App\Services\AuditService::class)->logModelEvent($model, 'updated');
            }
        });

        Event::listen("eloquent.deleted: {$modelClass}", function (Model $model) {
            app(\App\Services\AuditService::class)->logModelEvent($model, 'deleted');
        });
    }

    public function getAuditModule(): string
    {
        return config('audit.model_modules')[static::class] ?? 'system';
    }
}
```

### File: `app/Models/Concerns/BelongsToSchool.php`
```php
<?php

namespace App\Models\Concerns;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\Scopes\SchoolScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        $modelClass::addGlobalScope(new SchoolScope());

        Event::listen("eloquent.creating: {$modelClass}", function (Model $model) {
            if ($model->school_id !== null) {
                return;
            }

            $user = Auth::user();
            if ($user?->school_id) {
                $model->school_id = $user->school_id;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $query = $this->newModelQuery();
        $field = $field ?? $this->getRouteKeyName();
        $user = Auth::user();

        if ($user && $user->role !== UserRole::SuperAdmin && $user->school_id !== null) {
            $query->where($this->getTable() . '.school_id', $user->school_id);
        }

        return $query->where($field, $value)->first();
    }

    public function resolveChildRouteBinding($childType, $value, $field = null)
    {
        return $this->newModelQuery()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
```

### File: `app/Models/Concerns/HasCustomFields.php`
```php
<?php

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'entity');
    }

    public function getCustomFieldsAttribute(): array
    {
        $entityType = $this->getCustomFieldEntityType();

        $fields = CustomField::query()
            ->where('entity_type', $entityType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $values = $this->customFieldValues()
            ->whereIn('custom_field_id', $fields->pluck('id'))
            ->get()
            ->keyBy('custom_field_id');

        return $fields->mapWithKeys(function (CustomField $field) use ($values) {
            return [$field->slug => $values->get($field->id)?->value];
        })->all();
    }

    public function syncCustomFields(array $customFields): void
    {
        if ($customFields === []) {
            return;
        }

        $entityType = $this->getCustomFieldEntityType();

        $definitions = CustomField::query()
            ->where('entity_type', $entityType)
            ->whereIn('slug', array_keys($customFields))
            ->get()
            ->keyBy('slug');

        foreach ($customFields as $slug => $value) {
            $field = $definitions->get($slug);
            if (! $field) {
                continue;
            }

            $this->customFieldValues()->updateOrCreate(
                ['custom_field_id' => $field->id],
                ['value' => is_array($value) ? json_encode($value) : (string) $value]
            );
        }
    }

    abstract protected function getCustomFieldEntityType(): string;
}
```

### File: `app/Models/Concerns/Versionable.php`
```php
<?php

namespace App\Models\Concerns;

use App\Services\Platform\RecordVersionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

trait Versionable
{
    public static function bootVersionable(): void
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = static::class;

        Event::listen("eloquent.updated: {$modelClass}", function (Model $model) {
            if ($model->getChanges() === [] || app()->runningInConsole()) {
                return;
            }

            app(RecordVersionService::class)->snapshot(
                $model,
                Auth::user(),
                'auto_snapshot',
            );
        });
    }
}
```

